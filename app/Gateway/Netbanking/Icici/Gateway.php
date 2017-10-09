<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Verify as PaymentVerify;

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
    const RECURRING         = 'R';

    const RECURRING_BANKING = 'recurring';

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setBankingTypeAndDomainType($terminal);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            //
            // Debit steps are handled in the method below
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
        if (empty($input['token']->getGatewayToken()) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TOKEN_EMPTY,
                Token\Entity::GATEWAY_TOKEN,
                [
                    'payment' => $input['payment'],
                    'token'   => $input['token']->toArray(),
                ]);
        }

        $entity = [
            RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($entity);

        //
        // We set the PRN value to be the unique payment ID. But we send the token number
        // of the recurring registration payment for all SI execution payments
        //
        $requestData = $this->getSecondRecurringRequestData($input);

        $request = $this->getStandardRequestArray($requestData, 'post', $this->getUrlType());

        $this->trace->info(
            TraceCode::GATEWAY_RECURRING_DEBIT_REQUEST,
            [
                'payment_id' => $input['payment']['id'],
                'token_id'   => $input['token']->getId(),
                'request'    => $request,
                'gateway'    => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_RECURRING_DEBIT_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'token_id'   => $input['token']->getId(),
                'response'   => $response->body,
                'gateway'    => $this->gateway
            ]);

        try
        {
            $responseArray = $this->getResponseArray($response->body);
        }
        catch (\Exception $e)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                null,
                [
                    'payment_id' => $input['payment']['id'],
                    'token_id'   => $input['token']->getId(),
                    'response'   => $response->body,
                    'gateway'    => $this->gateway
                ]);
        }

        $attrs = $this->getResponseAttributes($responseArray);

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
        if ((empty($response[ResponseFields::PAID]) === true) or
            ($response[ResponseFields::PAID] !== Confirmation::YES))
        {
            $errorCode = SiStatusCode::getInternalErrorCode($response[ResponseFields::STATUS]);

            $gatewayErrorCode = $response[ResponseFields::PAID] ?? Status::N;

            $gatewayErrorDesc = $response[ResponseFields::STATUS];

            throw new Exception\GatewayErrorException(
                $errorCode, $gatewayErrorCode, $gatewayErrorDesc,
                [
                    'response' => $response,
                    'gateway'  => $this->gateway,
                ]);
        }
    }

    protected function getSecondRecurringRequestData(array $input)
    {
        //
        // All second recurring payments need the gatewayToken to be set
        //
        $gatewayToken = $input['token']->getGatewayToken();

        $baseRequestData = $this->getBaseRequestData(Mode::STANDING_INSTRUCTIONS);
        $referenceData = $this->getPaymentReferenceData($input);

        $paymentDate = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                             ->format('Y-m-d');

        $recurringRequestData = [
            RequestFields::SI_REFERENCE_NUMBER   => $gatewayToken,
            RequestFields::SI_DEBIT_PAYMENT_DATE => $paymentDate,
        ];

        return array_merge($baseRequestData, $referenceData, $recurringRequestData);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $callbackData = $this->getDataFromResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
                               $callbackData[RequestFields::PAYMENT_ID]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attrs = $this->getResponseAttributes($callbackData);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkCallbackStatus($attrs, $callbackData);

        $this->assertAmount($input, $callbackData);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        if ($this->hasRecurringData($gatewayPayment) === true)
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $acquirerData = array_merge($acquirerData, $recurringData);
        }

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
        else if ((isset($terminal) === true) and
                 ($terminal->isRecurring() === true))
        {
            $this->setBankingType(self::RECURRING_BANKING);
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
                // Need to ensure that the status is not a failure status
                $verify->gatewaySuccess = (Status::isSiStatusFailure($status) === false);
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
        $baseRequestData = $this->getBaseRequestData(Mode::PAY);

        $requestData = $this->getBaseAuthorizeRequestData($input);

        //
        // For recurring payments, we use E-Mandate Registration flow
        //
        if ($this->isEMandateRegistrationRequired($input) === true)
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
        $baseRequestData = $this->getBaseRequestData(Mode::INQUIRY);

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

        // We check that the recurring type of the payment is registration and not debit
        if ($verify->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $requestData[RequestFields::SI] = Status::Y;
            $requestData[RequestFields::SI_AUTO_PAY_AMOUNT] = $verify->input['token']->getMaxAmount() / 100;
        }

        return array_merge($baseRequestData, $requestData);
    }

    protected function getBaseVerifyRequestData(Base\Entity $gatewayPayment, array $input)
    {
        $paymentDate = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)
                             ->format('Y-m-d');

        $data = $this->getPaymentReferenceData($input);

        $data[RequestFields::PAYMENT_DATE] = $paymentDate;

        //
        // For payments that were done via the recurring flow, we
        // send the SI request reference ID in the verify request.
        //
        if ($gatewayPayment->getSIToken() !== null)
        {
            $data[RequestFields::SI_REFERENCE_NUMBER] = $gatewayPayment->getSIToken();
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
    protected function getEMandateRequestData(array $input): array
    {
        $date = Carbon::now(Timezone::IST)->format('Y-m-d');

        $endDate = Carbon::now(Timezone::IST)
                         ->addYears(Base\Entity::MAX_RECURRING_END_YEARS)
                         ->format('Y-m-d');

        $data = [
            RequestFields::SI                  => Confirmation::YES,
            RequestFields::SI_PAYMENT_DATE     => $date,
            // Recurring
            RequestFields::SI_PAYMENT_TYPE     => self::RECURRING,
            RequestFields::SI_PAYMENT_FREQ     => Frequency::AS_AND_WHEN,
            // Num installments = empty when charge at will
            RequestFields::SI_NUM_INSTALLMENTS => '',
            RequestFields::SI_AUTO_PAY_AMOUNT  => $input['token']->getMaxAmount() / 100,
            RequestFields::SI_END_DATE         => $endDate,
        ];

        return $data;
    }

    /**
     * Registration step is marked by
     * 1. payment being recurring,
     * 2. terminal being 3DS recurring and
     * 3. token's recurring parameter being false
     *
     * @param array $input
     * @return bool
     */
    protected function isEMandateRegistrationRequired(array $input): bool
    {
        $paymentRecurring = $input['payment']['recurring'];
        $terminalRecurring = $input['terminal']->is3DSRecurring();
        $tokenRecurring = (isset($input['token']) === true) ? $input['token']->isRecurring() : null;

        $this->trace->info(
            TraceCode::GATEWAY_FIRST_RECURRING,
            [
                'payment_recurring'     => $paymentRecurring,
                'terminal_recurring'    => $terminalRecurring,
                'token_recurring'       => $tokenRecurring,
            ]);

        //
        // Payment has to be a recurring payment, terminal has to be enabled for recurring
        // and token's recurring field has to be set to false, because it gets updated to true
        // after the initial recurring payment is successful.
        //
        // In case the token is already recurring, we don't have to do any registration.
        //
        return (($paymentRecurring === true) and
                ($terminalRecurring === true) and
                ($tokenRecurring === false));
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

    /**
     * For recurring payments, we use tokenId for ITC, otherwise we use paymentId in upper case.
     * For all payments, we use paymentId as the PRN parameter - as a unique identifier
     *
     * @param array $input
     * @return array
     */
    protected function getPaymentReferenceData(array $input)
    {
        $paymentId = $input['payment'][Payment\Entity::ID];

        $itc = strtoupper($paymentId);

        //
        // ITC is always in upper case
        //
        if ($input['payment']['recurring'] === true)
        {
            $itc = strtoupper($input['token']->getId());
        }

        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        return [
            RequestFields::PAYMENT_ID    => $paymentId,
            RequestFields::ITEM_CODE     => $itc,
            RequestFields::AMOUNT        => $amount,
            RequestFields::CURRENCY_CODE => Currency::INR,
        ];
    }

    protected function getBaseRequestData(string $mode)
    {
        $requestData = [
            RequestFields::MODE     => $mode,
            RequestFields::PAYEE_ID => $this->getPid(),
        ];

        return $requestData;
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

    protected function getResponseAttributes(array $content)
    {
        //
        // BID won't be sent back when the payment has not been scheduled in SI flow
        //
        $data = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID] ?? null,
        ];

        //
        // These fields are received in the callback of first recurring request
        //

        //
        // For first recurring payment, we get SCHEDULEID in the callback response,
        // but for second recurring payments, we get RID in the callback response
        //
        $recurringData = [
            Base\Entity::SI_TOKEN  => $content[ResponseFields::SI_REFERENCE_ID] ??
                                      $content[ResponseFields::SI_SCHEDULE_ID] ??
                                      null,
            Base\Entity::SI_STATUS => $content[ResponseFields::SI_STATUS] ?? null,
            Base\Entity::SI_MSG    => $content[ResponseFields::SI_MESSAGE] ?? null,
        ];

        return array_merge($data, $recurringData);
    }

    protected function assertAmount($input, $content)
    {
        $actualAmount = (int) ($content['AMT'] * 100);

        parent::assertAmount($input['payment']['amount'], $actualAmount);
    }

    protected function checkCallbackStatus(array $attrs, array $content)
    {
        if ((isset($attrs[ResponseFields::STATUS_LC]) === false) or
            ($attrs[ResponseFields::STATUS_LC] !== Confirmation::YES))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                [
                    'content' => $content,
                    'gateway' => $this->gateway,
                ]);
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

    protected function getVerifyAttributesFromPaymentAndContent(Base\Entity $gatewayPayment, array $content)
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

    protected function getVerifyConfirmationFromContent(array $content, string $status)
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
        $xml = (array) simplexml_load_string($response);

        return $xml['@attributes'];
    }

    public function getSpid()
    {
        if ($this->isTestMode() === true)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    public function getPid()
    {
        if ($this->isTestMode() === true)
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
        else if ($this->isRecurringBanking() === true)
        {
            return $this->config['test_hash_secret_rec'];
        }

        return parent::getTestSecret();
    }

    protected function isRecurringBanking()
    {
        return ($this->bankingType === self::RECURRING_BANKING);
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

            case $this->config['live_merchant_id2_corp']:
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

    protected function hasRecurringData($gatewayPayment)
    {
        return (($gatewayPayment->getSIStatus() !== null) and
                ($gatewayPayment->getSIToken() !== null));
    }

    protected function getRecurringData(Base\Entity $gatewayPayment = null)
    {
        $siStatus = $gatewayPayment->getSIStatus();

        $recurringStatus = Status::SI_STATUS_TO_RECURRING_STATUS_MAP[$siStatus] ?? Token\RecurringStatus::REJECTED;

        // TODO: Get the failure reason mapping and
        // display the correct failure reason here
        $recurringFailureReason = Status::getSiMessage($siStatus);

        $recurringData = [
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::GATEWAY_TOKEN            => $gatewayPayment->getSIToken(),
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason,
        ];

        return $recurringData;
    }
}
