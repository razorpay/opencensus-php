<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    protected $map = [
        RequestFields::AMOUNT  => 'amount'
    ];

    const VERIFY_STATUS_TO_CALLBACK = [
        Status::SUCCESS    => Confirmation::YES,
        Status::FAILED     => Confirmation::NO,
        Status::REVERSED   => Confirmation::NO,
        Status::IN_PROCESS => Confirmation::NO
    ];

    protected $authSuccessStatus = Confirmation::YES;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $content = $this->getDataFromResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
                               $content[RequestFields::PAYMENT_ID]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkCallbackStatus($attrs, $content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request
            ]);

        $response = $this->sendGatewayRequest($request);

        $responseBody = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $responseBody
            ]);

        $verify->verifyResponseContent = $this->getResponseArray($responseBody);
    }

    public function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $content);

        $this->setVerifyStatus($verify);

        $this->saveVerifyContentIfNeeded($verify);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->setApiSuccess($verify);

        $this->setGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    protected function setApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment'][Payment\Entity::STATUS] === 'failed') or
            ($input['payment'][Payment\Entity::STATUS] === 'created'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function setGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::STATUS]) === true) and
            ($content[ResponseFields::STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentRequestData(array $input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::ENCRYPTED_STRING] = $encryptedString;

        return $data;
    }

    protected function getVerifyRequestData(Verify $verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $data = $this->createDefaultRequestData($input);

        $paymentDate = Carbon::createFromTimestamp($payment['created_at'])
                                                   ->format('Y-m-d');

        $data[RequestFields::PAYMENT_DATE] = $paymentDate;

        $data[RequestFields::MODE]  = Action::INQUIRY;

        $additionalData = $this->getPaymentReferenceData($input);

        $this->setTpvFieldIfNeeded($additionalData, $input);

        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function getEncryptedString(array $input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $this->traceGatewayPaymentRequest($data, $input);

        $queryString = urldecode(http_build_query($data));

        $masterKey = $this->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        return base64_encode($aes->encryptString($queryString));
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22';

        $data = [
            RequestFields::RETURN_URL   => $callbackUrl,
            RequestFields::CONFIRMATION => Confirmation::YES,
        ];

        $additionalData = $this->getPaymentReferenceData($input);

        $data = array_merge($data, $additionalData);

        $this->setTpvFieldIfNeeded($data, $input);

        return $data;
    }

    protected function getPaymentReferenceData(array $input)
    {
        $prn = $input['payment'][Payment\Entity::ID];

        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        return [
            RequestFields::PAYMENT_ID    => $prn,
            RequestFields::ITEM_CODE     => strtoupper($prn),
            RequestFields::AMOUNT        => $amount,
            RequestFields::CURRENCY_CODE => Currency::INR,
        ];
    }

    protected function createDefaultRequestData(array $input)
    {
        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $data = [
            RequestFields::MODE       => Action::PAY,
            RequestFields::PAYEE_ID   => $this->getPid(),
            RequestFields::SPID       => $this->getSpid(),
        ];

        return $data;
    }

    protected function setTpvFieldIfNeeded(array & $additionalData, array $input)
    {
        if ($input['merchant']->isTPVRequired())
        {
            $additionalData[RequestFields::ACCOUNT_NO] = $input['order']['account_number'];
        }
    }

    protected function getDataFromResponse(array $data)
    {
        $masterKey = $this->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        $string = str_replace(' ', '+', $data['ES']);

        $decryptedString = $aes->decryptString(base64_decode($string));

        parse_str($decryptedString, $content);

        $this->trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'gateway' => $this->gateway,
                'decrypted_data' => $content
            ]);

        if (empty($content) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }

        return $content;
    }

    protected function getCallbackAttributes(array $content)
    {
        return [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID]
        ];
    }

    protected function checkCallbackStatus(array $attrs, array $content)
    {
        if ((isset($attrs[ResponseFields::LC_STATUS]) === false) or
            ($attrs[ResponseFields::LC_STATUS] !== Confirmation::YES))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveVerifyContentIfNeeded(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        $status = self::VERIFY_STATUS_TO_CALLBACK[$content[ResponseFields::STATUS]];

        $attributes = [Base\Entity::STATUS => $status];

        $this->checkIfStatusIsToBeSaved($gatewayPayment, $attributes);

        if ((empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true) and
            (isset($content[ResponseFields::BANK_PAYMENT_ID]) === true))
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_PAYMENT_ID];
        }

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getResponseArray($content)
    {
        $xml = (array) simplexml_load_string($content);

        return $xml['@attributes'];
    }

    public function getSpid()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    public function getPid()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId2();
        }

        return $this->getLiveMerchantId2();
    }

    /**
     * Overriding the parent class's method
     */
    protected function getLiveSecret()
    {
        switch ($this->getLiveMerchantId2())
        {
            case $this->config['live_merchant_id2']:
                return $this->config['live_hash_secret'];

            case $this->config['live_merchant_id2_tpv']:
                return $this->config['live_hash_secret_tpv'];
        }
    }
}
