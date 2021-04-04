<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
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
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Models\Payment\Verify as PaymentVerify;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    protected $bankingType = BankingType::RETAIL;

    protected $map = [
        RequestFields::AMOUNT    => 'amount',
        RequestFields::ITEM_CODE => 'client_code',
    ];

    // Payment type recurring
    const RECURRING         = 'R';

    // Verification window for defining border transaction in seconds refer isEodTransaction method
    const VERIFY_WINDOW     =  600;

    const GATEWAY_DATE_FORMAT = 'Y-m-d';

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setBankingTypeAndDomainType($input);
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

        $entity = [
            RequestFields::AMOUNT    => $input['payment'][Payment\Entity::AMOUNT] / 100,
            RequestFields::ITEM_CODE => $this->getItc($input),
        ];

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

        $attributes = $this->getResponseAttributes($responseArray);

        $gatewayPayment->fill($attributes);

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

        $attributes = $this->getResponseAttributes($callbackData);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkCallbackStatus($attributes, $callbackData);

        $callbackAmount = $callbackData['AMT'];

        if ($this->isFirstRecurringPayment() === true)
        {
            $paymentAmount = $input['payment']['amount'];

            if ($paymentAmount > 0)
            {
                $expectedAmount = $this->formatAmount($paymentAmount / 100);
                $actualAmount   = $this->formatAmount($callbackAmount);

                $this->assertAmount($expectedAmount, $actualAmount);
            }
            //
            // Since there's no hot payment, we would not
            // have an amount in the first auth request
            // We get amount as `'null'` in these cases.
            // Cases:
            // 1. Status => Y, AMT => null
            // 2. Status => N, AMT => 99999
            //
            if (($callbackAmount !== 'null') and
                ($callbackData[ResponseFields::PAID] === Status::Y) and
                ($paymentAmount === 0))
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED,
                    null,
                    null,
                    [
                        'gateway_payment_id' => $gatewayPayment->getId(),
                        'callback_data'      => $callbackData,
                        'payment_id'         => $input['payment']['id'],
                    ]);
            }
            else if ($callbackData[ResponseFields::PAID] === Status::N)
            {
                $expectedAmount = $this->formatAmount($input['token']['max_amount'] / 100);
                $actualAmount   = $this->formatAmount($callbackAmount);

                $this->assertAmount($expectedAmount, $actualAmount);
            }
        }
        else
        {
            $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);
            $actualAmount   = $this->formatAmount($callbackAmount);

            $this->assertAmount($expectedAmount, $actualAmount);
        }

        $this->verifyCallback($input, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        if ($this->hasRecurringData($gatewayPayment) === true)
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $acquirerData = array_merge($acquirerData, $recurringData);
        }

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        //
        // We don't do this for recurring payments as they don't have amount
        // in the response
        //
        if ((isset($input['payment']['recurring']) === true) and
            ($input['payment']['recurring'] === true))
        {
            return;
        }

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        if ($this->isCorporateBanking() === true)
        {
            $actualAmount = $this->formatAmount((float) $verify->verifyResponseContent[ResponseFields::UC_AMOUNT]);
        }
        else
        {
            $actualAmount = $this->formatAmount((float) $verify->verifyResponseContent[ResponseFields::AMOUNT]);
        }

        $this->assertAmount($expectedAmount, $actualAmount);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        //
        // temp fix: failed recurring payments are getting marked as success on verify on ICICI's end
        //
        if (($input['payment']['recurring'] === true) and
            (isset($input['payment']['recurring_type']) === true) and
            ($input['payment']['recurring_type'] === 'auto'))
        {
           return;
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function setBankingTypeAndDomainType($input)
    {
        // Default banking type is retail
        if (isset($input['payment']) === true)
        {
            if ($input['payment']['bank'] === Payment\Processor\Netbanking::ICIC_C)
            {
                $this->setBankingType(BankingType::CORPORATE);
            }
            else if ($input['payment']['recurring'] === true)
            {
                $this->setBankingType(BankingType::RECURRING);
            }
        }

        $this->setDomainType();
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $paymentCreatedAt = $verify->input['payment']['created_at'];

        //We need transaction date in verify request to the api
        if ($verify->payment->getDate() === null)
        {
            $formattedDate =  Carbon::createFromTimestamp($paymentCreatedAt,Timezone::IST)
                                    ->format(self::GATEWAY_DATE_FORMAT);

            $verify->payment->setDate($formattedDate);
        }

        $this->makeVerifyRequestToGateway($verify);

        $this->setGatewaySuccess($verify);

        // Sometimes  verify txn that happens near to eod fails since it hits the  gateway server next day.
        if (($verify->gatewaySuccess === false) and
            ($this->isEodTransaction($paymentCreatedAt) === true))
        {
            $gatewayTxnDate = Carbon::createFromTimestamp($paymentCreatedAt)->addDay(1)->format('Y-m-d');

            $verify->payment->setDate($gatewayTxnDate);

            $this->makeVerifyRequestToGateway($verify);
        }

        $this->setGatewaySuccess($verify);
    }

    protected function makeVerifyRequestToGateway($verify)
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
                $verify->gatewaySuccess = (Status::isSiStatusSuccess($status) === true);
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
            // This directDebitFlow flow is used when registration amount is greater than 0.
            // so when the payment is success the registration and debit for input amount will be done in single payment.
            $directDebitFlow = $input['payment']['amount'] > 0 ? true : false;

            $eMandateData = $this->getEMandateRequestData($input, $directDebitFlow);

            $requestData = array_merge($requestData, $eMandateData);

            if ($directDebitFlow === false)
            {
                $this->assertAmount(0, $input['payment']['amount']);

                //
                // For registration-only payments, we
                // should not send the amount field
                // There will be no upfront amount.
                //
                unset($requestData['AMT']);
            }
        }

        $traceRequestData = $requestData;

        unset($traceRequestData[RequestFields::ACCOUNT_NO]);

        $this->traceGatewayPaymentRequest($traceRequestData, $input);

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
        $data = $this->getPaymentReferenceData($input);

        $data[RequestFields::PAYMENT_DATE] = $gatewayPayment->getDate();
        $data[RequestFields::ITEM_CODE]    = $gatewayPayment->getClientCode() ?: $data[RequestFields::ITEM_CODE];

        //
        // For payments that were done via the recurring flow, we
        // send the SI request reference ID in the verify request.
        //
        if ($gatewayPayment->getSIToken() !== null)
        {
            $data[RequestFields::SI_REFERENCE_NUMBER] = $gatewayPayment->getSIToken();
        }

        $bankRef = $gatewayPayment->getBankPaymentId();

        if ((empty($bankRef) === false) and (strpos($bankRef, 'CFL-') !== false))
        {
            $data[RequestFields::BID] = $bankRef;
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
     * @param bool $debitFlow
     * @return array
     */
    protected function getEMandateRequestData(array $input, bool $directDebitFlow): array
    {
        // For registration-only auth request, we need to set the payment date to any date in the future
        $date = Carbon::now(Timezone::IST)->addDay()->format('Y-m-d');

        if ($directDebitFlow === true)
        {
            $date = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)->format('Y-m-d');
        }

        $endDate = Carbon::now(Timezone::IST)->setTimestamp($input['token']->getExpiredAt())->format('Y-m-d');

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

        $itc = $this->getItc($input);

        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        return [
            RequestFields::PAYMENT_ID    => $paymentId,
            RequestFields::ITEM_CODE     => $itc,
            RequestFields::AMOUNT        => $amount,
            RequestFields::CURRENCY_CODE => Currency::INR,
        ];
    }

    /**
     * Returns ITC value
     * We upper case it since verify only works with upper
     * case ITC even if we pass lower case in the payment request.
     * Ideally, we should only modify it for verify.
     */
    protected function getItc($input)
    {
        $itc = $input['payment']['id'];

        if ($input['payment']['merchant_id'] === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $itc = $input['payment']['description'] . '/' . $input['payment']['id'];
        }

        //
        // ITC is always in upper case
        //
        if ($input['payment']['recurring'] === true)
        {
            $itc = $input['token']->getId();
        }

        return strtoupper($itc);
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

    protected function checkCallbackStatus(array $attributes, array $content)
    {
        if ($this->isFirstRecurringPayment() === true)
        {
            //
            // Since there's no hot payment done in the registration request,
            // we don't need to throw an exception here for payment failed,
            // even in the case of mandate registration failure.
            // The mandate registration failure will be handled in the
            // getRecurringData() method, by setting the recurring status.
            //
            // Here, the gateway's hot payment should also be null.
            // This is validated later in the flow.
            //

            return;
        }

        if (isset($attributes[ResponseFields::STATUS_LC]) === false)
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
        else
        {
            if ($attributes[ResponseFields::STATUS_LC] === Confirmation::PENDING)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
            }

            if ($attributes[ResponseFields::STATUS_LC] !== Confirmation::YES)
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
    }

    protected function isFirstRecurringPayment()
    {
        return ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL);
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

        if ($verify->gatewaySuccess === false)
        {
           unset($gatewayPayment[Base\Entity::DATE]);
        }

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

        // If RID exists and status is registration success, set token related attributes here
        if ((empty($content[ResponseFields::SI_REFERENCE_ID]) === false) and
            (in_array($content[ResponseFields::STATUS], Status::SI_SUCCESS_STATUSES, true)))
        {
            $recurringData = [
                Base\Entity::SI_TOKEN  => $content[ResponseFields::SI_REFERENCE_ID] ??
                    $content[ResponseFields::SI_SCHEDULE_ID] ??
                    null,
                Base\Entity::SI_STATUS => Status::Y,
            ];

            $attributes = array_merge($attributes, $recurringData);
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
        return ($this->bankingType === BankingType::RECURRING);
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
        //
        // For SI terminals, the live_merchant_id2 is different
        // for every terminal. This is different from retail terminals
        // where live_merchant_id2 is the same.
        // A secret is configured at live_merchant_id2 level. But,
        // we asked them to configure the retail terminal's
        // live_merchant_id2's secret to all our SI terminals too.
        //
        if ($this->isRecurringBanking() === true)
        {
            return $this->config['live_hash_secret'];
        }

        // For all corporate payments, use a single secret
        if ($this->isCorporateBanking() === true)
        {
            return $this->config['live_hash_secret_corp'];
        }

        switch ($this->getLiveMerchantId2())
        {
            case $this->config['live_merchant_id2_tpv']:
                return $this->config['live_hash_secret_tpv'];
            case $this->config['live_merchant_id2_cred']:
                return $this->config['live_hash_secret_cred'];
            case $this->config['live_merchant_id2']:
            case $this->config['live_merchant_id2_aditiya_birla_direct']:
            default:
                return $this->config['live_hash_secret'];
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

    protected function getRecurringData(Base\Entity $gatewayPayment)
    {
        $siStatus = $gatewayPayment->getSIStatus();

        if (isset(Status::SI_STATUS_TO_RECURRING_STATUS_MAP[$siStatus]) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['gateway_payment' => $gatewayPayment->toArray()]);
        }

        $recurringStatus = Status::SI_STATUS_TO_RECURRING_STATUS_MAP[$siStatus];

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

    protected function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * This method checks if transactions happened towards EOD.
     * ICIC Transaction Status API filters based on the date as well.
     * Transactions that happens at 11.50 we resent verify request with next days payment date.
     * @param PaymentCreatedAt
     * @return bool
     */
    protected function isEodTransaction($paymentCreatedAt) : bool
    {
        $paymentTime = Carbon::createFromTimestamp($paymentCreatedAt, Timezone::IST);

        return ($paymentTime->secondsUntilEndOfDay() <= self::VERIFY_WINDOW);
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                            $input['payment']['id'],
                                            Payment\Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Confirmation::YES))
        {
            return true;
        }

        $attrs = [
            Base\Entity::STATUS  => Confirmation::YES,
        ];

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }
}
