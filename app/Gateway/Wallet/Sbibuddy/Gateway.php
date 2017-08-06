<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Models\Payment\Entity as Payment;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_sbibuddy';

    protected $map = [
        RequestFields::MERCHANT_ID          => Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT               => Entity::AMOUNT,
        ResponseFields::STATUS_CODE         => Entity::STATUS_CODE,
        ResponseFields::ERROR_DESCRIPTION   => Entity::ERROR_MESSAGE,
        ResponseFields::TRANSACTION_ID      => Entity::GATEWAY_PAYMENT_ID,
        Entity::CONTACT                     => Entity::CONTACT,
        Entity::RECEIVED                    => Entity::RECEIVED,
        Entity::DATE                        => Entity::DATE,
        Entity::EMAIL                       => Entity::EMAIL
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $input['payment'][Payment::AMOUNT] = $this->formatAmount($input['payment'][Payment::AMOUNT]);

        $request = $this->getPayloadForAuth($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        $contentToSave = [
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            Entity::PAYMENT_ID          => $input['payment'][Payment::ID],
            RequestFields::AMOUNT       => $input['payment'][Payment::AMOUNT],
            Entity::EMAIL               => $input['payment'][Payment::EMAIL],
            Entity::CONTACT             => $this->getFormattedContact($input['payment'][Payment::CONTACT]),
            Entity::RECEIVED            => false
        ];

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    public function callback(array $input)
    {
        $data = $this->parseResponse($input['gateway']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $data);

        $this->assertPaymentId($input['payment']['id'], $data[ResponseFields::ORDER_ID]);

        $this->saveWalletEntity($data);

        // If status code is not success code, throw exception
        if ($data[ResponseFields::STATUS_CODE] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->handleCallbackFailure($data);
        }

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::refund($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $response = json_decode($response->body, true);

        $content = $this->parseResponse($response);

        $this->createWalletEntityFromRefundResponse($content, $input);

        if ($content[ResponseFields::STATUS_CODE] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->handleRefundFailure($content);
        }
    }

    //----------------Auth helper methods----------------------

    protected function getPayloadForAuth($input)
    {
        $payment = $input['payment'];

        $data = [
            RequestFields::EXTERNAL_TRANSACTION_ID  => $payment[Payment::ID],
            RequestFields::ORDER_ID                 => $payment[Payment::ID],
            RequestFields::AMOUNT                   => $payment[Payment::AMOUNT],
            RequestFields::CURRENCY                 => $payment[Payment::CURRENCY],
            RequestFields::CALLBACK_URL             => $input['callbackUrl'],
            RequestFields::BACK_URL                 => $input['callbackUrl'],
            RequestFields::DESCRIPTION              => "WAPO",
            RequestFields::PROCESSOR_ID             => 'ALL',
        ];

        $encodedData = http_build_query($data);

        $cryptor = $this->getEncryptor();

        $encrypted = $cryptor->encryptString($encodedData);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    //----------------Auth helper methods ends------------------

    //----------------Callback helper methods-------------------

    /**
     * If the callback gives a success status, update the wallet entity
     */
    protected function saveWalletEntity($data)
    {
        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        $contentToSave = $data + [
            Entity::RECEIVED                => true,
            Entity::DATE                    => $date
        ];

        // Order ID in the wallet API is mapped to our payment ID
        $wallet = $this->repo->findByPaymentIdAndAction(
            $data[ResponseFields::ORDER_ID],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function handleCallbackFailure($content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            $content[ResponseFields::STATUS_CODE],
            $content[ResponseFields::ERROR_DESCRIPTION]
        );
    }
    //----------------Callback helper methods end--------------

    //----------------Refund helper methods--------------------

    protected function getRefundRequest($input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $content = $this->getRefundRequestContent($input, $wallet);

        return $content;
    }

    protected function getRefundRequestContent($input, $wallet)
    {
        $payment = $input['payment'];

        $data = [
            RequestFields::ORDER_ID             => $payment[Payment::ID],
            RequestFields::TRANSACTION_ID       => $wallet[Entity::GATEWAY_PAYMENT_ID],
            RequestFields::AMOUNT               => $this->formatAmount($payment[Payment::AMOUNT]),
            RequestFields::REFUND_FEE           => ResponseCodeMap::REFUND_FEE,
            // This is optional
            // RequestFields::REFUND_REQUEST_ID    => ResponseCodeMap::REFUND_FEE,
        ];

        $encodedData = http_build_query($data);

        $cryptor = $this->getEncryptor();

        $encrypted = $cryptor->encryptString($encodedData);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function createWalletEntityFromRefundResponse($data, $input)
    {
        $refundAttributes = $this->getWalletEntityAttributesFromRefundResponse($data, $input);

        $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getWalletEntityAttributesFromRefundResponse($data, $input)
    {
        $contentToSave = [
            Entity::PAYMENT_ID            => $input['payment']['id'],
            Entity::ACTION                => $this->action,
            Entity::AMOUNT                => $input['refund']['amount'],
            Entity::WALLET                => $input['payment']['wallet'],
            Entity::EMAIL                 => $input['payment']['email'],
            Entity::RECEIVED              => true,
            Entity::CONTACT               => $this->getFormattedContact($input['payment']['contact']),
            Entity::GATEWAY_MERCHANT_ID   => $this->getMerchantId(),
            Entity::STATUS_CODE           => $data[ResponseFields::STATUS_CODE],
            Entity::REFUND_ID             => $input['refund']['id'],
        ];

        return $contentToSave;
    }

    /**
     * Handles the failures by checking the response of refund call
     *
     * @param $data Parsed data from the response of transaction
     */
    protected function handleRefundFailure($data)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_REFUND_FAILED,
            $data[ResponseFields::STATUS_CODE],
            $data[ResponseFields::ERROR_DESCRIPTION]
        );
    }

    //----------------Refund helper methods end-----------------

    //-----------------Verify request helpers-------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        sd($verify);
        $data = $this->getVerifyRequestData($verify);

        $verify->verifyResponse = $this->sendSoapRequest($data,
                                                   SoapAction::QUERY_API,
                                                   SoapMethod::QUERY_PAYMENT_TRANSACTION);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $verify->verifyResponse,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponseContent = $verify->verifyResponse[ResponseFields::UCF_RESPONSE];
    }

    protected function getVerifyRequestData($verify)
    {

    }

    //-----------------Verify request helpers end---------------

    //----------------General helper methods-------------------

    protected function parseResponse($input)
    {
        $cryptor = $this->getEncryptor();

        $decryptedInput = $cryptor->decryptString($input[ResponseFields::ENCRYPTED_DATA]);

        parse_str($decryptedInput, $data);

        return $data;
    }

    public function getEncryptor()
    {
        $secret = base64_decode($this->getSecret());

        assert($secret !== null);

        return new Encryptor(AES::MODE_ECB, $secret);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    //----------------General helper methods ends---------------

}
