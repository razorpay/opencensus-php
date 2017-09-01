<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use phpseclib\Crypt\AES;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Models\Payment\Verify as PaymentVerify;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    protected $bankingType = self::RETAIL;

    protected $map = [
        RequestFields::AMOUNT  => 'amount'
    ];

    // Payment type recurring
    const RECURRING = 'R';

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setBankingTypeAndDomainType($terminal);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPayment($input) === true)
        {
            //
            // We return nothing here, to avoid 2 step flow
            //
            return $this->authorizeSecondRecurring($input);
        }

        $requestData = $this->getAuthorizeRequestData($input);

        $entity = [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($requestData, 'post', $this->getUrlType());

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function authorizeSecondRecurring(array $input)
    {
        $entity = [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];

        $gatewayPayment = $this->createGatewayPaymentEntity($entity);

        $requestData = $this->getSecondRecurringRequestData($input, $gatewayPayment);

        $request = $this->getStandardRequestArray($requestData, 'post', $this->getUrlType());

        $this->trace->info(
            TraceCode::GATEWAY_RECURRING_DEBIT_REQUEST,
            [
                'payment_id' => $input['payment']['id'],
                'request'    => $request
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_RECURRING_DEBIT_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response'   => $response->body
            ]);

        $responseArray = $this->getResponseArray($response->body);

        $attrs = $this->getCallbackAttributes($responseArray);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkSecondRecurringStatus($responseArray);
    }

    /**
     * PAID tells us whether the payment was a success. Can be a Y or N.
     * STATUS gives us more information on the success / failure case.
     *
     * @param array $response
     * @throws Exception\GatewayErrorException
     */
    protected function checkSecondRecurringStatus(array $response)
    {
        if ((empty($response[ResponseFields::STATUS]) === true) or
            ($response[ResponseFields::STATUS] !== Status::SI_SUCCESS))
        {
            $errorCode = SiStatusCode::getInternalErrorCode($response[ResponseFields::STATUS]);

            $gatewayErrorCode = $response[ResponseFields::PAID];

            $gatewayErrorDesc = $response[ResponseFields::STATUS];

            throw new Exception\GatewayErrorException(
                $errorCode, $gatewayErrorCode, $gatewayErrorDesc);
        }
    }

    protected function getSecondRecurringRequestData(array $input, $gatewayPayment)
    {
        $gatewayToken = $input['token']->getGatewayToken();

        $baseRequestData = $this->getBaseRequestData(Action::STANDING_INSTRUCTIONS);
        $verifyRequestData = $this->getBaseVerifyRequestData($gatewayPayment, $input);

        $paymentDate = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                             ->format('Y-m-d');

        $recurringRequestData = [
            RequestFields::SI_REFERENCE_NUMBER   => $gatewayToken,
            RequestFields::SI_DEBIT_PAYMENT_DATE => $paymentDate,
        ];

        return array_merge($baseRequestData, $verifyRequestData, $recurringRequestData);
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

        $acquirerData = $this->getAcquirerData($gatewayPayment);

        $recurringData = [];

        if ($this->isFirstRecurring($input))
        {
            $recurringData = $this->getRecurringData($gatewayPayment);
        }

        $callbackData = array_merge($acquirerData, $recurringData);

        return $this->getCallbackResponseData($input, $callbackData);
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
        $requestData = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($requestData, 'post', $this->getUrlType());

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

        try
        {
            $verify->verifyResponseContent = $this->getResponseArray($verify->verifyResponseBody);
        }
        catch (\Exception $e)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                PaymentVerify\Action::RETRY);
        }
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
            else if ($verify->input['payment']['recurring'] === true)
            {
                // TODO: Not all SI based payments are mapped to this success status - ensure this is right
                $verify->gatewaySuccess = ($status !== Status::SI_FAILED);
            }
            // Whereas, the retail verify success is success
            else
            {
                $verify->gatewaySuccess = ($status === Status::SUCCESS);
            }
        }
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $baseRequestData = $this->getBaseRequestData(Action::PAY);

        $requestData = $this->getBaseAuthorizeRequestData($input);

        //
        // For recurring payments, we use E-Mandate
        //
        if ($this->isFirstRecurring($input) === true)
        {
            $eMandateData = $this->getEMandateRequestData($input);

            $requestData = array_merge($requestData, $eMandateData);
        }

        $this->traceGatewayPaymentRequest($requestData, $input);

        $encryptedString = $this->getEncryptedString($requestData);

        $requestData = [
            RequestFields::ENCRYPTED_STRING => $encryptedString,
            RequestFields::SPID             => $this->getSpid(),
        ];

        return array_merge($baseRequestData, $requestData);
    }

    protected function getVerifyRequestData(Verify $verify)
    {
        $baseRequestData = $this->getBaseRequestData(Action::INQUIRY);

        $requestData = $this->getBaseVerifyRequestData($verify->payment, $verify->input);

        if ($this->isCorporateBanking() === true)
        {
            $corporateData = [
                RequestFields::SHOW_ON_SAME_PAGE    => Status::Y,
                // This is a dummy url that is being set to use the api
                RequestFields::RETURN_URL           => $this->app['config']->get('app.url')
            ];

            $requestData = array_merge($requestData, $corporateData);
        }

        // If the SI reference ID is not empty, we know that this is a recurring payment
//        TODO: Check why adding this to the request causes a verification failure
        if (empty($verify->payment->getSIRefId()) === false)
        {
            $requestData[RequestFields::SI] = Status::Y;
            $requestData[RequestFields::SI_AUTO_PAY_AMOUNT] = (int) $verify->input['token']->getMaxAmount() / 100;
        }

        return array_merge($baseRequestData, $requestData);
    }

    protected function getBaseVerifyRequestData($gatewayPayment, $input)
    {
        $paymentDate = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)
                             ->format('Y-m-d');

        $data = $this->getPaymentReferenceData($input);

        if ($this->action === Action::VERIFY)
        {
            $data[RequestFields::PAYMENT_DATE] = $paymentDate;

            //
            // For payments that were done via the recurring flow, we
            // send the SI request reference ID in the verify request
            //
            if ($gatewayPayment->getSIRefId() !== null)
            {
                $data[RequestFields::SI_REFERENCE_NUMBER] = $gatewayPayment->getSIRefId();
            }
        }

        return $data;
    }

    protected function getEncryptedString(array $data)
    {
        $queryString = urldecode(http_build_query($data));

        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return base64_encode($aes->encryptString($queryString));
    }

    /**
     * This method gets the request data pertaining
     * to the E - Mandate registration step.
     *
     * @param array $input
     * @return array
     */
    protected function getEMandateRequestData(array $input)
    {
        $date = Carbon::now(Timezone::IST)->format('Y-m-d');

        $endDate = Carbon::now(Timezone::IST)
                         ->addYears(Base\Recurring::MAX_END_YEARS)
                         ->format('Y-m-d');

        $data = [
            RequestFields::SI                  => Confirmation::YES,
            // TODO: How do we get the start date in case of charge-at-will?
            RequestFields::SI_PAYMENT_DATE     => $date,
            // Recurring
            RequestFields::SI_PAYMENT_TYPE     => self::RECURRING,
            RequestFields::SI_PAYMENT_FREQ     => Frequency::AS_AND_WHEN,
            // Num installments = empty when charge at will
            RequestFields::SI_NUM_INSTALLMENTS => '',
            RequestFields::SI_AUTO_PAY_AMOUNT  => (int) $input['token']->getMaxAmount() / 100,
            // TODO: Should we accept this from the merchant?
            RequestFields::SI_END_DATE         => $endDate,
        ];

        return $data;
    }

    protected function getBaseAuthorizeRequestData(array $input)
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

    protected function getBaseRequestData(string $mode)
    {
        $defaultData = [
            RequestFields::MODE     => $mode,
            RequestFields::PAYEE_ID => $this->getPid(),
        ];

        return $defaultData;
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
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID],
            //
            // These fields are received in the callback of first recurring request
            //
            // TODO: Find out which one is sent and fix this accordingly.
            Base\Entity::SI_REF_ID       => $content[ResponseFields::SI_REFERENCE_ID] ??
                                            $content[ResponseFields::SI_SCHEDULE_ID] ??
                                            null,
            Base\Entity::SI_STATUS       => $content[ResponseFields::SI_STATUS] ?? null,
            Base\Entity::SI_MSG          => $content[ResponseFields::SI_MESSAGE] ?? null,
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

        if (empty($content) === true)
        {
            return;
        }

        $gatewayPayment = $verify->payment;

        $attributes = $this->getVerifyAttributesFromPaymentAndContent($gatewayPayment, $content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getVerifyAttributesFromPaymentAndContent($gatewayPayment, $content)
    {
        $attributes = [];

        list($status, $bankPaymentIdKey) = $this->getKeysBasedOnBankingType();

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $this->getVerifyConfirmationFromContent($content, $status);
        }

        if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[$bankPaymentIdKey] ?? null;
        }

        return $attributes;
    }

    protected function getKeysBasedOnBankingType()
    {
        if ($this->isCorporateBanking() === true)
        {
            return  [
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

    protected function getVerifyConfirmationFromContent($content, $status)
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

    protected function getAuthSuccessStatus()
    {
        return Confirmation::getAuthSuccessStatus();
    }

    /**
     * In case of corporate payments the verify response returned is an
     * ill formed xml. To parse the same, we will replace the offending keys
     * with an appropriate parse-able version of the same.
     *
     * @param $content
     */
    protected function preProcessVerifyResponse(& $content)
    {
        // successful case
        $content = str_replace(ResponseFields::BILL_REF_NUM, ResponseFields::US_BILL_REF_NUM, $content);

        $content = str_replace(ResponseFields::CONSUMER_CODE, ResponseFields::US_CONSUMER_CODE, $content);
    }

    protected function getResponseArray(string $response)
    {
        try
        {
            $xml = (array) simplexml_load_string($response);

            return $xml['@attributes'];
        }
        catch (\Exception $e)
        {
            throw new Exception\LogicException(
                $e->getMessage(),
                ErrorCode::SERVER_ERROR_EMPTY_RESPONSE,
                [
                    'response' => $response
                ]);
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

            if ($this->input['terminal']->isRecurring() === true)
            {
                return $this->getTestMerchantId2Recurring();
            }

            return $this->getTestMerchantId2();
        }

        return $this->getLiveMerchantId2();
    }

    protected function getTestMerchantId2Recurring()
    {
        return $this->config['test_merchant_id2_rec'];
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

    protected function isSecondRecurringPayment(array $input)
    {
        //
        // If the customer's SI request is approved. This is a valid
        // way of ensuring that this is a 2nd recurring payment
        //
        if ((isset($input['token']) === true) and
            ($input['token']->isRecurring() === true) and
            ($input['terminal']->isRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function setDomainType()
    {
        $this->domainType = $this->getBankingType() . '_' . $this->getMode();
    }

    protected function getUrlType()
    {
        return $this->getBankingType() . '_QUERY';
    }

    protected function getRecurringData($gatewayPayment)
    {
        $siStatus = $gatewayPayment->getSIStatus();

        // This null check is used in the test cases
        $recurringStatus = Status::SI_STATUS_TO_RECURRING_STATUS_MAP[$siStatus] ?? null;

        // TODO: We should have a mapping here with our internal error codes.
        // We cannot show the message as it is.
        $recurringFailureReason = $gatewayPayment->getSIMessage();

        $recurringData = [
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::GATEWAY_TOKEN            => $gatewayPayment->getSIRefId(),
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason ?? null,
        ];

        return $recurringData;
    }
}
