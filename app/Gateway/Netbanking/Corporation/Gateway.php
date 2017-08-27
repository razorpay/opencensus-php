<?php

namespace RZP\Gateway\Netbanking\Corporation;

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
use phpseclib\Crypt\AES;

class Gateway extends Base\Gateway
{
    protected $gateway = 'netbanking_corporation';

    protected $bank = 'corporation';

    protected $tpv;

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

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

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

        $this->checkCallbackStatus($content);

        // If callback status was a success, we verify the payment immediately
        $this->verifyCallback($input);

        // Saving callback response only if the above checks pass
        $gatewayPayment = $this->saveCallbackResponse($content);

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
        $data = array(
            // Setting this as the merchant code shared with us
            RequestFields::CUSTOMER_ID          => $this->getMerchantId(),
            RequestFields::MERCHANT_CODE        => $this->getMerchantId(),
            RequestFields::AMOUNT               => $input['payment']['amount'] / 100,
            RequestFields::PAYMENT_ID           => $input['payment']['id'],
            RequestFields::MODE_OF_TRANSACTION  => Constants::MODE_OF_TRANSACTION_PAYMENT,
            RequestFields::FUND_TRANSFER        => Constants::FUND_TRANSFER,
        );

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

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
        $content = $this->getVerifyRequestData($verify->input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $verify->verifyResponseContent,
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
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::STATUS];
        }

        if (isset($content[ResponseFields::BANK_REF_NUMBER]) === true and
            empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true
        )
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_REF_NUMBER];
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
    protected function verifyCallback(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        // Setting this back to a callback request once the verification in callback is done
        // parent::callback($input);
    }

    protected function parseVerifyResponse($content)
    {
        parse_str($content, $data);

        return $this->getEncryptor()->decryptData($data[ResponseFields::VERIFY_DATA]);
    }

    protected function getVerifyRequestData(array $input)
    {
        // If we're calling the double verification request from callback,
        // we'll have the 'gateway' attribute filled, because we already got
        // the response from the gateway in the callback request.
        // But, if we're calling this method from the verify request,
        // we'll have to fetch the bank ref number from the netbanking repo.
        if(isset($input['gateway']) === false)
        {
            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

            $bankRefNumber = $gatewayPayment[Base\Entity::BANK_PAYMENT_ID];
        }
        else
        {
            $bankRefNumber = $input['gateway'][ResponseFields::BANK_REF_NUMBER];
        }

        $data = [
            RequestFields::VERIFY_MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::VERIFY_PAYMENT_ID            => $input['payment']['id'],
            RequestFields::VERIFY_AMOUNT                => $input['payment']['amount'] / 100,
            RequestFields::VERIFY_BANK_REF_NUMBER       => $bankRefNumber,
            RequestFields::VERIFY_MODE_OF_TRANSACTION   => RequestFields::VERIFY_MODE_OF_TRANSACTION_VALUE,
            RequestFields::VERIFY_ACCOUNT_NUMBER        => "",
        ];

        $encryptedString = $this->getEncryptor()->encryptData($data);

        $data = [
            RequestFields::VERIFY_MERCHANT_CODE => $this->getMerchantId(),
            RequestFields::VERIFY_DATA          => $encryptedString
        ];

        return $data;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::VERIFY_RESULT]) and
            $content[ResponseFields::VERIFY_RESULT] === ResponseCodeMap::RESULT_SUCCESS
        )
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

    // -------------------------- General helper methods end ----------------------
}
