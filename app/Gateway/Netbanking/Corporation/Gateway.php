<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Payment\Action;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
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

        $request = array(
            'url' => $this->getUrl('pay'),
            'method' => 'post',
            'content' => $content);

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

    // -------------------------- Auth helper methods ------------------------------

    protected function getAuthRequestData($input)
    {
        $data = array(
            // Setting this as the merchant code shared with us
            'CustID'            => $this->getMerchantId(),
            'MerCD'             => $this->getMerchantId(),
            'AMT'               => $input['payment']['amount'] / 100,
            'OTC'               => $input['payment']['id'],
            'MD'                => 'P',
            'TT'                => 'T',
        );

        if ($input['merchant']->isTPVRequired())
        {
            $data['AcctNo'] = $input['order']['account_number'];
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
        $data = [
            NetbankingEntity::RECEIVED => true
        ] + $content;

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::PAYMENT_ID],
                                    Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $data);

        return $gatewayPayment;
    }

    // -------------------------- Callback helper methods end --------------------------

    // -------------------------- Verify helper methods ------------------------------

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
    }

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

    protected function parseVerifyResponse($content)
    {
        parse_str($content, $data);

        return $this->getEncryptor()->decryptData($data[ResponseFields::VERIFY_DATA]);
    }

    protected function getVerifyRequestData(array $input)
    {
        $data = [
            RequestFields::VERIFY_MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::VERIFY_PAYMENT_ID            => $input['payment']['id'],
            RequestFields::VERIFY_AMOUNT                => $input['payment']['amount'] / 100,
            RequestFields::VERIFY_BANK_REF_NUMBER       => $input['gateway'][ResponseFields::BANK_REF_NUMBER],
            RequestFields::VERIFY_MODE_OF_TRANSACTION   => $input['gateway'][ResponseFields::MODE_OF_TRANSACTION],
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

        if ($content[ResponseFields::VERIFY_RESULT] === ResponseCodeMap::RESULT_SUCCESS)
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
        $mid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    // -------------------------- General helper methods end ----------------------
}
