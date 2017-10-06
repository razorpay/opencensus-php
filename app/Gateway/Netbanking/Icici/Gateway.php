<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use phpseclib\Crypt\AES;
use RZP\Models\Payment\Verify as PaymentVerify;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    protected $bankingType = self::RETAIL;

    protected $map = [
        RequestFields::AMOUNT  => 'amount'
    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setBankingTypeAndDomainType($terminal);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content, 'post', $this->getUrlType());

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

        $this->assertAmount($input, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function setBankingTypeAndDomainType($terminal)
    {
        // Default banking type is retail
        if ((isset($terminal) === true) and
            ($terminal->isCorporate() === true))
        {
            $this->setBankingType(self::CORPORATE);
        }

        $this->setDomainType();
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content, 'post', $this->getUrlType());

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'payment_id' => $verify->input['payment']['id'],
                'request'    => $request
            ]);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseBody = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'payment_id' => $verify->input['payment']['id'],
                'response'   => $response->body
            ]);

        $this->preProcessVerifyResponse($verify->verifyResponseBody);

        $verify->verifyResponseContent = $this->getResponseArray($verify);
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

        if (isset($content[ResponseFields::STATUS]) === true)
        {
            $status = $content[ResponseFields::STATUS];

            // Yes, the corporate verify success is Y
            if ($this->isCorporateBanking() === true)
            {
                $verify->gatewaySuccess = ($status === Status::Y);
            }
            // Whereas, the retail verify success is success
            else
            {
                $verify->gatewaySuccess = ($status === Status::SUCCESS);
            }
        }
    }

    protected function getPaymentRequestData(array $input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::ENCRYPTED_STRING] = $encryptedString;
        $data[RequestFields::SPID]             = $this->getSpid();

        return $data;
    }

    protected function getVerifyRequestData(Verify $verify)
    {
        $input = $verify->input;

        $payment = $verify->payment;

        $data = $this->createDefaultRequestData($input);

        $paymentDate = Carbon::createFromTimestamp($payment['created_at'],
                                                   Timezone::IST)
                                                   ->format('Y-m-d');

        $data[RequestFields::PAYMENT_DATE] = $paymentDate;

        $data[RequestFields::MODE]  = Action::INQUIRY;

        $additionalData = $this->getPaymentReferenceData($input);

        if ($this->isCorporateBanking() === true)
        {
            $data[RequestFields::SHOW_ON_SAME_PAGE]  = Status::Y;
            // This is a dummy url that is being set to use the api
            $data[RequestFields::RETURN_URL]  = $this->app['config']->get('app.url');
        }

        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function getEncryptedString(array $input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $this->traceGatewayPaymentRequest($data, $input);

        $queryString = urldecode(http_build_query($data));

        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

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
            RequestFields::MODE     => Action::PAY,
            RequestFields::PAYEE_ID => $this->getPid(),
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

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

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

    protected function assertAmount($input, $content)
    {
        $actualAmount = number_format($content['AMT'], 2, '.', '');
        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        if ($actualAmount !== $expectedAmount)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED);
        }
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

        if (empty($content) === true)
        {
            return;
        }

        $gatewayPayment = $verify->payment;

        $attributes = $this->getAttributesFromPaymentAndContent($gatewayPayment, $content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getAttributesFromPaymentAndContent($gatewayPayment, $content)
    {
        $attributes = [];

        list($status, $bankPaymentIdKey) = $this->getKeysBasedOnBankingType();

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $this->getConfirmationFromContent($content, $status);
        }

        if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $this->getBankPaymentIdFromContent($content, $bankPaymentIdKey);
        }

        return $attributes;
    }

    protected function getKeysBasedOnBankingType()
    {
        if ($this->isCorporateBanking() === true)
        {
            return [
                Status::Y,
                ResponseFields::PAYMENTID,
            ];
        }
        else
        {
            return [
                Status::SUCCESS,
                ResponseFields::BANK_PAYMENT_ID,
            ];
        }
    }

    protected function getConfirmationFromContent($content, $status)
    {
        $confirmation = Confirmation::NO;

        if (isset($content[ResponseFields::STATUS]) === true)
        {
            if ($content[ResponseFields::STATUS] === $status)
            {
                $confirmation = Confirmation::YES;
            }
        }

        return $confirmation;
    }

    protected function getBankPaymentIdFromContent($content, $bankPaymentIdKey)
    {
        return $content[$bankPaymentIdKey] ?? null;
    }

    protected function getAuthSuccessStatus()
    {
        return Confirmation::getAuthSuccessStatus();
    }

    /**
     * In case of corporate payments the verify response returned is an
     * ill formed xml. To parse the same, we will reploace the offending keys
     * with an appropriate parsable version of the same.
     * */
    protected function preProcessVerifyResponse(&$content)
    {
        // successful case
        $content = str_replace(ResponseFields::BILL_REF_NUM, ResponseFields::US_BILL_REF_NUM, $content);

        $content = str_replace(ResponseFields::CONSUMER_CODE, ResponseFields::US_CONSUMER_CODE, $content);
    }

    protected function getResponseArray(Verify $verify)
    {
        try
        {
            $xml = (array) simplexml_load_string($verify->verifyResponseBody);

            return $xml['@attributes'];
        }
        catch (\Exception $e)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                PaymentVerify\Action::RETRY);
        }
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
            if ($this->isCorporateBanking() === true)
            {
                return $this->getTestMerchantId2Corporate();
            }
            else
            {
                return $this->getTestMerchantId2();
            }
        }

        return $this->getLiveMerchantId2();
    }

    protected function getTestMerchantId2Corporate()
    {
        return $this->config['test_merchant_id2_corp'];
    }

    protected function getTestSecret()
    {
        if ($this->isCorporateBanking() === true)
        {
            return $this->getTestSecretCorporate();
        }

        return parent::getTestSecret();
    }

    protected function getTestSecretCorporate()
    {
        return $this->config['test_hash_secret_corp'];
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

            case $this->config['live_merchant_id2_corp'];
                return $this->config['live_hash_secret_corp'];
        }
    }

    protected function setDomainType()
    {
        $this->domainType = $this->getBankingType() . '_' . $this->getMode();
    }

    protected function getUrlType()
    {
        return $this->getBankingType() . '_QUERY';
    }
}
