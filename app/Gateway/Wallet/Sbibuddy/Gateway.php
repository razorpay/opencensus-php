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

        $request = $this->getAuthorizeRequestArray($input);

        $contentToSave = $this->getAuthorizeWalletContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $data = $this->decryptResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'payment_id'     => $input['payment']['id'],
                'decrypted_data' => $data,
                'gateway_name'   => $this->gateway
            ]
        );

        $this->assertPaymentId($input['payment']['id'], $data[ResponseFields::ORDER_ID]);

        $this->saveCallbackResponse($input, $data);

        // If status code is not success code, throw exception
        if ($data[ResponseFields::STATUS_CODE] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->handleFailure($data);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount   = number_format((float) $data[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

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

        $paymentId = $input['payment']['id'];

        $wallet = $this->repo->fetchWalletByPaymentId($paymentId);

        $request = $this->getRefundRequestArray($input, $wallet);

        list($content, $response) = $this->sendRequest($request, $paymentId);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $paymentId,
                'refund_id'  => $input['refund']['id'],
                'content'    => $content,
                'response'   => $response
            ]
        );

        $this->handleRefundResponse($content, $input, $wallet);

        if (($this->isStatusCodeSuccess($content) === false) and
            ($this->isAlreadyRefunded($content) === false)
        )
        {
            $this->handleFailure($content);
        }
    }

    // Here, we mark all the verify refund requests as failed and thus forcing
    // the retry of refunds which are marked as failed.
    // If a duplicate refund request is sent, the gateway would throw a duplicate
    // transaction code and then we mark the corresponding refund as success.
    public function verifyRefund(array $input)
    {
        return false;
    }

    //----------------Auth helper methods----------------------

    protected function getAuthorizeRequestArray(array $input): array
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

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'payload' => $data
            ]
        );

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
    protected function saveCallbackResponse(array $input, array $response)
    {
        $this->isStatusCodeMissing($response);

        $content = [
            Entity::RECEIVED                        => true,
            ResponseFields::ORDER_ID                => $response[ResponseFields::ORDER_ID],
            ResponseFields::STATUS_CODE             => $response[ResponseFields::STATUS_CODE],
            ResponseFields::EXTERNAL_TRANSACTION_ID => $response[ResponseFields::EXTERNAL_TRANSACTION_ID] ?? null,
            ResponseFields::TRANSACTION_ID          => $response[ResponseFields::TRANSACTION_ID] ?? null,
            ResponseFields::ERROR_DESCRIPTION       => $response[ResponseFields::ERROR_DESCRIPTION] ?? null,
        ];

        // These fields are available based on whether the transaction was success or not
        if ($response[ResponseFields::STATUS_CODE] === ResponseCodeMap::SUCCESS_CODE)
        {
            $this->validateResponseAttributeNotNull($content, ResponseFields::EXTERNAL_TRANSACTION_ID);
            $this->validateResponseAttributeNotNull($content, ResponseFields::TRANSACTION_ID);
        }
        else
        {
            $this->validateResponseAttributeNotNull($content, ResponseFields::ERROR_DESCRIPTION);
        }

        // Order ID in the wallet API is mapped to our payment ID
        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($wallet, $content);
    }
    //----------------Callback helper methods end--------------

    //----------------Refund helper methods--------------------

    protected function getRefundRequestArray(array $input, $wallet)
    {
        $payment = $input['payment'];

        // Here either the order ID or the transaction ID is requred.
        // We're sending both, since it would be easier to trace both these values.
        $data = [
            RequestFields::ORDER_ID             => $payment[Payment::ID],
            RequestFields::TRANSACTION_ID       => $wallet[Entity::GATEWAY_PAYMENT_ID],
            RequestFields::AMOUNT               => $this->formatAmount($input['refund']['amount']),
            RequestFields::REFUND_FEE           => Constants::REFUND_FEE,
            RequestFields::REFUND_REQUEST_ID    => $input['refund']['id'],
        ];

        $encryptedData = $this->getEncryptedStringFromData($data);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encryptedData
        ];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'refund_id'  => $input['refund']['id'],
                'content'    => $data,
                'request'    => $request
            ]
        );

        return $request;
    }

    protected function handleRefundResponse(array $content, array $input, Entity $wallet)
    {
        $refundId = $input['refund']['id'];

        $walletAttributes = $this->getGatewayRefundEntityData(
            $content,
            $input,
            $wallet
        );

        $this->updateOrCreateRefundEntity($walletAttributes, $refundId);
    }

    protected function updateOrCreateRefundEntity(array $refundFields, string $refundId)
    {
        $gatewayRefundEntity = $this->repo->findByRefundId($refundId);

        if ($gatewayRefundEntity === null)
        {
            $gatewayRefundEntity = $this->getNewGatewayPaymentEntity();

            $gatewayRefundEntity->setAction(Action::REFUND);
        }

        $gatewayRefundEntity->fill($refundFields);

        $this->repo->saveOrFail($gatewayRefundEntity);
    }

    protected function isAlreadyRefunded(array $content): bool
    {
        if ($content[ResponseFields::STATUS_CODE] === ResponseCodeMap::DUPLICATE_TRANSACTION)
        {
            return true;
        }

        return false;
    }

    protected function getGatewayRefundEntityData(array $content, array $input, Entity $wallet): array
    {
        $gatewayRefundId = null;

        // Only if refund is success would the refund id be returned from gateway
        if (isset($content[ResponseFields::REFUND_ID]) === true)
        {
            // They return all the refund ids comma separated in every
            // refund request. So, we're taking the last one out of those
            // and associate that with the current refund request.
            $gatewayRefundIds = explode(',', $content[ResponseFields::REFUND_ID]);

            $gatewayRefundId = end($gatewayRefundIds);
        }

        $contentToSave = [
            Entity::PAYMENT_ID          => $input['payment']['id'],
            Entity::AMOUNT              => $input['refund']['amount'],
            Entity::WALLET              => $input['payment']['wallet'],
            Entity::EMAIL               => $input['payment']['email'],
            Entity::RECEIVED            => true,
            Entity::CONTACT             => $this->getFormattedContact($input['payment']['contact']),
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::GATEWAY_PAYMENT_ID  => $wallet[Entity::GATEWAY_PAYMENT_ID],
            Entity::GATEWAY_REFUND_ID   => $gatewayRefundId,
            Entity::STATUS_CODE         => $content[ResponseFields::STATUS_CODE],
            Entity::REFUND_ID           => $input['refund']['id'],
            Entity::ERROR_MESSAGE       => $content[ResponseFields::ERROR_DESCRIPTION] ?? null,
        ];

        return $contentToSave;
    }
    //----------------Refund helper methods end-----------------

    //-----------------Verify request helpers-------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestArray($verify);

        list($content, $response) = $this->sendRequest($request, $verify->input['payment'][Payment::ID]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment']['id'],
                'content'    => $content
            ]
        );

        $verify->verifyResponseBody = $response;

        $verify->setVerifyResponseContent($content);

        return $content;
    }

    protected function getVerifyRequestArray(Verify $verify)
    {
        $payment = $verify->input['payment'];

        // Don't need to sent transaction id here, since we might not always have it
        // Like in cases where we do not receive a callback for auth request
        $data = [
            RequestFields::ORDER_ID => $payment[Payment::ID]
        ];

        $encrypted = $this->getEncryptedStringFromData($data);

        $content = [
            RequestFields::MERCHANT_ID    => $this->getMerchantId(),
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'content'    => $data,
                'payment_id' => $payment[Payment::ID]
            ]
        );

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

        // If the verify status is success, we need to check the amount from verify response
        // And if the amount from verify does not match the amount in our payment entity,
        // we should throw amount mismatch critical error
        if (($verify->gatewaySuccess === true) and
            ($this->formatAmount($input['payment'][Payment::AMOUNT]) !== $content[ResponseFields::AMOUNT]))
        {
            $verify->amountMismatch = true;
        }

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

        assert($secret !== null); // nosemgrep : razorpay:assert-fix-false-positives

        return (new AESCrypto(AES::MODE_ECB, $secret));
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
            $content[ResponseFields::ERROR_DESCRIPTION] ?? null
        );
    }

    // Checks if status code is not present in response
    protected function isStatusCodeMissing(array $response)
    {
        if (isset($response[ResponseFields::STATUS_CODE]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                '',
                'Status Code is missing'
            );
        }
    }

    protected function isStatusCodeSuccess(array $content): bool
    {
        return in_array($content[ResponseFields::STATUS_CODE], ResponseCodeMap::$successCodes, true);
    }

    protected function validateResponseAttributeNotNull(array $response, string $attr)
    {
        if (empty($response[$attr]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $attr,
                'invalid value'
            );
        }
    }

    protected function decryptResponse(array $input): array
    {
        $decryptedInput = $this->getEncryptor()->decryptString($input[ResponseFields::ENCRYPTED_DATA]);

        if(empty($decryptedInput) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                null,
                null,
                ['encrypted_data' => $input[ResponseFields::ENCRYPTED_DATA]]
            );
        }

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

    protected function sendRequest(array $request, string $paymentId)
    {
        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_RESPONSE,
            [
                'content'    => $response->body,
                'gateway'    => $this->gateway,
                'payment_id' => $paymentId
            ]);

        $responseContent = [];

        parse_str($response->body, $responseContent);

        return [$this->decryptResponse($responseContent), $responseContent];
    }
    //----------------General helper methods ends---------------
}
