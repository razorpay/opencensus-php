<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Entity as Payment;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_sbibuddy';

    protected $map = [
        RequestFields::MERCHANT_ID          => Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT               => Entity::AMOUNT,
        ResponseFields::STATUS_CODE         => Entity::STATUS_CODE,
        ResponseFields::ERROR_DESCRIPTION   => Entity::ERROR_MESSAGE,
        ResponseFields::TRANSACTION_ID      => Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::REFUND_ID           => Entity::GATEWAY_REFUND_ID,
        Entity::CONTACT                     => Entity::CONTACT,
        Entity::RECEIVED                    => Entity::RECEIVED,
        Entity::DATE                        => Entity::DATE,
        Entity::EMAIL                       => Entity::EMAIL
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getPayloadForAuth($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $contentToSave = $this->getAuthorizeWalletContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    public function callback(array $input)
    {
        $data = $this->parseResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'payment_id'     => $input['payment']['id'],
                'decrypted_data' => $data,
                'gateway_name'   => $this->gateway
            ]
        );

        $this->assertPaymentId($input['payment']['id'], $data[ResponseFields::ORDER_ID]);

        $this->saveWalletEntity($data);

        // If status code is not success code, throw exception
        if ($data[ResponseFields::STATUS_CODE] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->handleFailure($data);
        }

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $request = $this->getRefundRequest($input, $wallet);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        list($content, $response) = $this->sendRequest($request);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $this->createWalletEntityFromRefundResponse($content, $input, $wallet);

        if ($this->isStatusCodeSuccess($content) !== true)
        {
            $this->handleFailure($content);
        }
    }

    //----------------Auth helper methods----------------------

    protected function getPayloadForAuth(array $input): array
    {
        $payment = $input['payment'];

        $data = [
            RequestFields::EXTERNAL_TRANSACTION_ID  => $payment[Payment::ID],
            RequestFields::ORDER_ID                 => $payment[Payment::ID],
            RequestFields::AMOUNT                   => $this->formatAmount($input['payment'][Payment::AMOUNT]),
            RequestFields::CURRENCY                 => $payment[Payment::CURRENCY],
            RequestFields::CALLBACK_URL             => $input['callbackUrl'],
            RequestFields::BACK_URL                 => $input['callbackUrl'],
            RequestFields::DESCRIPTION              => 'WAPO',
            RequestFields::PROCESSOR_ID             => 'ALL',
        ];

        $encrypted = $this->getEncryptedStringFromData($data);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function getAuthorizeWalletContentToSave($payment): array
    {
        return [
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            RequestFields::AMOUNT       => $payment[Payment::AMOUNT],
            Entity::EMAIL               => $payment[Payment::EMAIL],
            Entity::CONTACT             => $this->getFormattedContact($payment[Payment::CONTACT]),
            Entity::RECEIVED            => false
        ];
    }

    //----------------Auth helper methods ends------------------

    //----------------Callback helper methods-------------------

    /**
     * If the callback gives a success status, update the wallet entity
     */
    protected function saveWalletEntity(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('d/m/Y H:m:s');

        $contentToSave = $data + [
            Entity::RECEIVED    => true,
            Entity::DATE        => $date
        ];

        $contentToSave[ResponseFields::AMOUNT] = $contentToSave[ResponseFields::AMOUNT] * 100;

        // Order ID in the wallet API is mapped to our payment ID
        $wallet = $this->repo->findByPaymentIdAndAction(
            $data[ResponseFields::ORDER_ID],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }
    //----------------Callback helper methods end--------------

    //----------------Refund helper methods--------------------

    protected function getRefundRequest(array $input, $wallet)
    {
        $payment = $input['payment'];

        // Here either the order ID or the transaction ID is requred.
        // We're sending both, since it would be easier to trace both these values.
        $data = [
            RequestFields::ORDER_ID             => $payment[Payment::ID],
            RequestFields::TRANSACTION_ID       => $wallet[Entity::GATEWAY_PAYMENT_ID],
            RequestFields::AMOUNT               => $this->formatAmount($input['refund']['amount']),
            RequestFields::REFUND_FEE           => ResponseCodeMap::REFUND_FEE,
            // This is optional
            RequestFields::REFUND_REQUEST_ID    => $input['refund']['id'],
        ];

        $encryptedData = $this->getEncryptedStringFromData($data);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encryptedData
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function createWalletEntityFromRefundResponse(array $data, array $input, $wallet)
    {
        $refundAttributes = $this->getGatewayRefundEntityData($data, $input, $wallet);

        $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getGatewayRefundEntityData(array $data, array $input, $wallet): array
    {
        // They return all the refund ids comma separated in every
        // refund request. So, we're taking the last one out of those
        // and associate that with the current refund request.
        $exploded = explode(',', $data[ResponseFields::REFUND_ID]);
        $refundId = end($exploded);

        $contentToSave = [
            Entity::PAYMENT_ID          => $input['payment']['id'],
            Entity::ACTION              => $this->action,
            Entity::AMOUNT              => $input['refund']['amount'],
            Entity::WALLET              => $input['payment']['wallet'],
            Entity::EMAIL               => $input['payment']['email'],
            Entity::RECEIVED            => true,
            Entity::CONTACT             => $this->getFormattedContact($input['payment']['contact']),
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::GATEWAY_PAYMENT_ID  => $wallet[Entity::GATEWAY_PAYMENT_ID],
            Entity::GATEWAY_REFUND_ID   => $refundId,
            Entity::STATUS_CODE         => $data[ResponseFields::STATUS_CODE],
            Entity::REFUND_ID           => $input['refund']['id'],
            Entity::DATE                => Carbon::now(Timezone::IST)->format('d/m/Y H:m:s'),
        ];

        // Since error description is optional
        if(isset($data[ResponseFields::ERROR_DESCRIPTION]))
        {
            $contentToSave[Entity::ERROR_MESSAGE] = $data[ResponseFields::ERROR_DESCRIPTION];
        }

        return $contentToSave;
    }
    //----------------Refund helper methods end-----------------

    //-----------------Verify request helpers-------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        list($content, $response) = $this->sendRequest($request);

        $verify->verifyResponseBody = $response;

        $verify->setVerifyResponseContent($content);

        return $content;
    }

    protected function getVerifyRequestData(Verify $verify)
    {
        $payment = $verify->input['payment'];

        $wallet = $verify->payment;

        $data = [
            RequestFields::ORDER_ID         => $payment[Payment::ID],
            RequestFields::TRANSACTION_ID   => $wallet[Entity::GATEWAY_PAYMENT_ID]
        ];

        $encrypted = $this->getEncryptedStringFromData($data);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $input          = $verify->input;
        $content        = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $this->setVerifyApiSuccess($verify, $input['payment']);

        $this->setVerifyGatewaySuccess($verify, $content);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->saveVerifyContentIfNeeded($gatewayPayment, $input['payment']);
    }

    protected function setVerifyApiSuccess($verify, $payment)
    {
        $verify->apiSuccess = true;

        // apiSuccess is false if the payment entity is in failed or created state
        if (($payment['status'] === Status::FAILED) or
            ($payment['status'] === Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function setVerifyGatewaySuccess($verify, $content)
    {
        // Initially assume the gatewaySuccess if false
        $verify->gatewaySuccess = false;

        if ($this->isStatusCodeSuccess($content) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContentIfNeeded($gatewayPayment, $payment)
    {
        $this->action = Action::AUTHORIZE;

        $walletAttributes = $this->getAuthorizeWalletContentToSave($payment);

        if ($gatewayPayment === null)
        {
            $gatewayPayment = $this->createGatewayPaymentEntity($walletAttributes, Action::AUTHORIZE);
        }
        else if ($gatewayPayment['received'] === false)
        {
            $gatewayPayment->fill($walletAttributes);
            $gatewayPayment->saveOrFail();
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }
    //-----------------Verify request helpers end---------------

    //----------------General helper methods-------------------

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public function getEncryptor(): AESCrypto
    {
        $secret = base64_decode($this->getSecret());

        assert($secret !== null);

        return new AESCrypto(AES::MODE_ECB, $secret);
    }

    public function getEncryptedStringFromData($data)
    {
        $encodedData = http_build_query($data);

        $cryptor = $this->getEncryptor();

        return $cryptor->encryptString($encodedData);
    }

    /**
     * Handles the failures by checking the response of refund call
     *
     * @param $data Parsed data from the response of refund
     */
    protected function handleFailure(array $content)
    {
        throw new Exception\GatewayErrorException(
            ResponseCodeMap::getApiErrorCode($content[ResponseFields::STATUS_CODE]),
            $content[ResponseFields::STATUS_CODE],
            $content[ResponseFields::ERROR_DESCRIPTION]
        );
    }

    protected function isStatusCodeSuccess(array $data): bool
    {
        return in_array($data[ResponseFields::STATUS_CODE], ResponseCodeMap::$successCodes, true);
    }

    protected function parseResponse(array $input): array
    {
        $decryptedInput = $this->getEncryptor()->decryptString($input[ResponseFields::ENCRYPTED_DATA]);

        parse_str($decryptedInput, $data);

        return $data;
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    protected function sendRequest($request)
    {
        $response = $this->sendGatewayRequest($request);

        $responseContent = [];

        parse_str($response->body, $responseContent);

        return [$this->parseResponse($responseContent), $responseContent];
    }
    //----------------General helper methods ends---------------
}
