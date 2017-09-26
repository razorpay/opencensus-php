<?php

namespace RZP\Gateway\Netbanking\Corporation;

use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Payment\Action;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'netbanking_corporation';

    protected $bank = 'corporation';

    protected $map = [
        ResponseFields::CUSTOMER_ID      => NetbankingEntity::CUSTOMER_ID,
        ResponseFields::MERCHANT_CODE    => NetbankingEntity::MERCHANT_CODE,
        ResponseFields::AMOUNT           => NetbankingEntity::AMOUNT,
        ResponseFields::BANK_REF_NUMBER  => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::PAYMENT_ID       => NetbankingEntity::PAYMENT_ID,
        ResponseFields::STATUS           => NetbankingEntity::STATUS,
        NetbankingEntity::RECEIVED       => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $this->verifyCallback($input, $content);

        // Saving callback response only if the verification passes
        $gatewayPayment = $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------------- Auth helper methods ------------------------------

    protected function getAuthRequestData($input)
    {
        $data = [
            // Setting this as the merchant code shared with us
            RequestFields::CUSTOMER_ID          => $this->getMerchantId(),
            RequestFields::MERCHANT_CODE        => $this->getMerchantId(),
            RequestFields::AMOUNT               => $this->formatAmount($input['payment']['amount']),
            RequestFields::PAYMENT_ID           => $input['payment']['id'],
            RequestFields::MODE_OF_TRANSACTION  => Constants::MODE_OF_TRANSACTION_PAYMENT,
            RequestFields::FUND_TRANSFER        => Constants::FUND_TRANSFER,
        ];

        return $data;
    }

    // -------------------------- Auth helper methods end --------------------------

    // -------------------------- Callback helper methods ------------------------------

    protected function checkCallbackStatus(array $content)
    {
        if ($content[ResponseFields::STATUS] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse($content)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::PAYMENT_ID],
                                    Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    // -------------------------- Callback helper methods end --------------------------

    // -------------------------- Verify helper methods ------------------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequest($verify->input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'decrypted'  => $verify->verifyResponseContent,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyResponse($verify);
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::VERIFY_RESULT];
        }

        if ((isset($content[ResponseFields::VERIFY_BANK_REF_NUMBER]) === true) and
            (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true))
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::VERIFY_BANK_REF_NUMBER];
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus()
    {
        return ResponseCodeMap::SUCCESS_CODE;
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     */
    protected function verifyCallback(array $input, array $callbackContent)
    {
        $verify = new Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        // We set apiSuccess based on the response from the callback,
        // since we can't check the same from the payment status.
        $apiSuccess = ($callbackContent[ResponseFields::STATUS] === ResponseCodeMap::SUCCESS_CODE);

        if ($verify->gatewaySuccess !== $apiSuccess)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED);
        }
    }

    protected function parseVerifyResponse($content)
    {
        parse_str($content, $data);

        return $this->getEncryptor()->decryptData($data[ResponseFields::VERIFY_DATA]);
    }

    protected function getVerifyRequest(array $input)
    {
        $data = [
            RequestFields::VERIFY_MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::VERIFY_PAYMENT_ID            => $input['payment']['id'],
            RequestFields::VERIFY_AMOUNT                => $this->formatAmount($input['payment']['amount']),
            RequestFields::VERIFY_MODE_OF_TRANSACTION   => RequestFields::VERIFY_MODE_OF_TRANSACTION_VALUE
        ];

        $encryptedString = $this->getEncryptor()->encryptData($data);

        $content = [
            RequestFields::VERIFY_MERCHANT_CODE => $this->getMerchantId(),
            RequestFields::VERIFY_DATA          => $encryptedString
        ];

        $request = $this->getStandardRequestArray($content, 'post', Action::VERIFY);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'content'    => $data,
                'payment_id' => $input['payment']['id'],
            ]
        );

        return $request;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::VERIFY_RESULT]) === true) and
            ($content[ResponseFields::VERIFY_RESULT] === ResponseCodeMap::RESULT_SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    // -------------------------- Verify helper methods end --------------------------

    // -------------------------- General helper methods --------------------------

    public function getEncryptor()
    {
        $secret = $this->getSecret();

        return new Encryptor(AES::MODE_ECB, $secret);
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    // -------------------------- General helper methods end ----------------------
}
