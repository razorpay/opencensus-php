<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Models\Payment\Verify\Action as VerifyAction;
use RZP\Gateway\Netbanking\Axis\Emandate\EmandateTrait;

class Gateway extends Base\Gateway
{
    use EmandateTrait;

    use AuthorizeFailed;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    protected $bankingType = BankingType::RETAIL;

    protected $sortRequestContent = false;

    protected $useOldKey = false;

    protected $map = [
        RequestFields::AMOUNT             => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_REFERENCE => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE          => Base\Entity::REFERENCE1,
    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->setBankingTypeAndDomainType($input);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            return $this->authorizeRecurring($input);
        }

        $content = $this->getPaymentRequestData($input);

        $entityAttributes = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

        $request = $this->getStandardRequestArray($content, 'post');

        $this->addRequestUrlQuery($request);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function addRequestUrlQuery(&$request)
    {
        $query = [
            'AuthenticationFG.MENU_ID'   => 'CIMSHP',
            'AuthenticationFG.CALL_MODE' => 2,
            'CATEGORY_ID'                => ($this->mode === Mode::LIVE) ? 'IRRAZB' : 'IRRAZ',
        ];

        if ($this->bankingType === BankingType::CORPORATE)
        {
            $query = [
                'AuthenticationFG.MENU_ID'   => 'CIMSHP',
                'AuthenticationFG.CALL_MODE' => 2,
                'CATEGORY_ID'                => 'IRCSM',
            ];
        }

        $request['url'] .= '?' . http_build_query($query);
    }

    protected function getActionType()
    {
        return $this->getBankingType() . '_' . $this->getAction();
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
                           [
                               'gateway_response' => $input['gateway'],
                               'payment_id'       => $input['payment']['id'],
                           ]);

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            return $this->handleEmandateCallback($input);
        }

        if ((isset($input['s2s']) === true) and ($input['s2s'] === true))
        {
            // Should occur only in corporate payments.
            assertTrue ($this->isCorporateBanking() === true);

            $content = $input['gateway'];
        }
        else
        {
            $content = $this->getDataFromEncryptedResponse($input);
        }

        $traceContent = $content;

        unset($traceContent[RequestFields::BANK_ACCOUNT_NUMBER]);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
                           ['content'    => $traceContent,
                            'payment_id' => $input['payment']['id']]);

        $this->assertPaymentId($input['payment']['id'],
                               $content[RequestFields::MERCHANT_REFERENCE]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount   = number_format($content['AMT'], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkResponseStatus($attrs, $content);

        $this->verifyCallback($input, $gatewayEntity);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        if ($verify->input['payment'][Payment\Entity::RECURRING] === true)
        {
            $this->checkVerifyGatewaySuccess($verify);
        }
        else
        {
            $this->checkGatewaySuccess($verify);
        }

        if ($verify->input['payment'][Payment\Entity::RECURRING] === false)
        {
            $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

            $actualAmount = number_format(
                $verify->verifyResponseContent[ResponseFields::VERIFY_RESPONSE_AMT],
                2,
                '.',
                '');

            $this->assertAmount($expectedAmount, $actualAmount);
        }

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

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        if ($verify->input['payment'][Payment\Entity::RECURRING] === true)
        {
            $this->sendEmandatePaymentVerifyRequest($verify);

            return;
        }

        $content = $this->getPaymentVerifyData($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'content' => $content,
                'gateway' => $this->gateway,
            ]);

        $request = $this->getStandardRequestArray($content, 'post');

        // Use this, till we add the correct root certificate for Digicert on our server
        $request['options']['verify'] = $this->getCaInfo();

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response, $verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }

    protected function getVerifyContent($data, $input)
    {
        $data[RequestFields::VERIFY_CHECKSUM] = $this->getHashOfArray($data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'decrypted_data' => $data,
                'payment_id'     => $input['payment']['id']
            ]);

        $stringToEncrypt = $this->prepareStringToEncrypt($data, '=', '|');

        $encryptedData = $this->getEncryptor()->encryptString($stringToEncrypt);

        return [
            RequestFields::VERIFY_ENCDATA   => $encryptedData,
            RequestFields::VERIFY_PAYEE_ID  => $this->getMerchantId(),
        ];
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA256, $str, false);
    }

    public function verifyPayment(Verify $verify)
    {
        if ($verify->input['payment'][Payment\Entity::RECURRING] === true)
        {
            $this->setEmandateVerifyStatus($verify);

            $verify->payment = $this->saveEmandateVerifyResponseIfNeeded($verify);

            return;
        }

        $this->setVerifyStatus($verify);

        $verify->payment = $this->saveVerifyResponseIfNeeded($verify);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->setVerifyAmountMismatch($verify);
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input['payment'][Payment\Entity::AMOUNT]);

        $verify->amountMismatch =
            ($paymentAmount !== $verify->verifyResponseContent[ResponseFields::VERIFY_RESPONSE_AMT]);
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $response = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if (empty($response) === true)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                Payment\Verify\Action::RETRY);
        }

        if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentVerifyData(Verify $verify)
    {
        $input = $verify->input;

        $date = date('Y-m-d', $input['payment']['created_at']);

        $data = [
            RequestFields::VERIFY_PAYEE_ID => $this->getMerchantId(),
            RequestFields::VERIFY_ITC      => $this->getVerifyItc($verify),
            RequestFields::VERIFY_PRN      => $input['payment']['id'],
            RequestFields::VERIFY_DATE     => $date,
            RequestFields::VERIFY_AMT      => $this->formatAmount($input['payment']['amount']),
        ];

        return $this->getVerifyContent($data, $input);
    }

    protected function getStringToHash($data, $glue = '')
    {
        $resultArray = [];

        foreach ($data as $key => $value)
        {
            $resultArray[] = $key . '=' . $value;
        }

        return implode('|', $resultArray);
    }

    /**
     * For payments before April 20th at 2pm, we need to send the
     * caps_payment_id as the ITC. But after this date, we send the
     * gateway_merchant_id due to a change in the auth request
     *
     * @param Verify $verify
     * @return string $itc
     */
    protected function getVerifyItc(Verify $verify)
    {
        $input          = $verify->input;
        $gatewayPayment = $verify->payment;

        $timestamp = $input['payment']['created_at'];

        //
        // 04/20/2017 @ 2:00pm IST
        //
        if ($timestamp < 1492677000)
        {
            $itc = $gatewayPayment->getCapsPaymentId();
        }
        else if (empty($gatewayPayment->getReference1()) === false)
        {
            $itc = $gatewayPayment->getReference1();
        }
        else
        {
            $itc = $this->getMerchantId();
        }

        return $itc;
    }

    protected function getPaymentRequestData(array $input)
    {
        $encryptedString = $this->getAuthorizeEncryptedString($input);

        return [
            RequestFields::ENCRYPTED_STRING => $encryptedString,
            RequestFields::RETURN_URL       => $input['callbackUrl'],
        ];
    }

    protected function getAuthorizeEncryptedString(array $input)
    {
        $defaultData = $this->getEntityAttributes($input);

        $data = [
            RequestFields::PAYEE_ID          => $this->getMerchantId(),
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Currency::INR,
            RequestFields::CONFIRMATION      => Status::YES,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::BANK_ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $data = array_merge($defaultData, $data);

        $traceData = $data;

        unset($traceData[RequestFields::BANK_ACCOUNT_NUMBER]);

        $this->traceGatewayPaymentRequest($traceData, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        $crypto = $this->getEncryptor();

        return $crypto->encryptString($stringToEncrypt);
    }

    public function getEncryptor(): AESCrypto
    {
        $masterKey = $this->getSecret();

        return new AESCrypto($masterKey);
    }

    protected function getEntityAttributes(array $input)
    {
        return [
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::ITEM_CODE          => $this->getMerchantId(),
            RequestFields::AMOUNT             => $this->formatAmount($input['payment']['amount']),
        ];
    }

    /*
     * @param Eg. $data = ['PRN' => "6vTX585l2WP6Bq", 'MD' => "P"]
     * @return Eg. string "PRN~6vTX585l2WP6Bq$MD~P"
     */
    protected function prepareStringToEncrypt(array $data, $kvSeparator = '~', $pairsSeparator = '$')
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . $kvSeparator . $value;
        }

        $queryString = implode($pairsSeparator, $queryArray);

        return $queryString;
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\GatewayErrorException
     *
     * Here, we first try to decrypt using the new key and if it does not work,
     * we will try using the old key
     *
     * When using the new key they would be urlencoding the data and hence, when
     * using the new key you'd have to urldecode before decryption.
     *
     */
    protected function getDataFromEncryptedResponse(array $input)
    {
        // rawurldecode because sometimes the data contains '+' which gets converted
        // to whitespace when using urldecode and subsequently the decryption fails
        $encryptedString = rawurldecode($input['gateway'][ResponseFields::ENCRYPTED_STRING]);

        $crypto = $this->getEncryptor();

        $decryptedString = $crypto->decryptString($encryptedString);

        parse_str($decryptedString, $response);

        $this->checkDecryptionFailure($encryptedString, $response, $input);

        return $response;
    }

    protected function checkDecryptionFailure(string $encryptedString, array $content, array $input)
    {
        if (isset($content[RequestFields::MERCHANT_REFERENCE]) === false)
        {
            $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['encrypted_string' => $encryptedString,
                 'payment_id'       => $input['payment']['id']]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }
    }

    /**
     * The default success status is Y, but this method accepts the any possible success value to ensure usability
     *
     * @param array  $attributes
     * @param array  $content
     * @param string $status
     * @throws Exception\BadRequestException
     * @throws Exception\GatewayErrorException
     */
    protected function checkResponseStatus(array $attributes, array $content, string $status = Status::YES)
    {
        if ((isset($attributes[Base\Entity::STATUS]) === false) or
            ($attributes[Base\Entity::STATUS] !== $status))
        {
            // Check for and if pending throw that instead
            if ($content[ResponseFields::FLAG] === Status::PENDING)
            {
                $this->trace->info(
                    TraceCode::PAYMENT_CALLBACK_PENDING,
                    ['content' => $content]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
            }

            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getCallbackAttributes(array $content)
    {
        return [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID] ?? $content[ResponseFields::STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE_ID],
        ];
    }

    protected function saveVerifyResponseIfNeeded(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        if ((isset($content[ResponseFields::PAYMENT_STATUS])) and
            ($content[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
        {
            $attributes = $this->getVerifyAttributes($verify, $gatewayPayment);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    protected function getVerifyAttributes(Verify $verify, $gatewayPayment)
    {
        $content = $verify->verifyResponseContent;

        $bankPaymentId = $gatewayPayment->getBankPaymentId();

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            // We're saving the response only if status is a success
            $attributes[Base\Entity::STATUS] = Status::YES;
        }

        if (empty($bankPaymentId) === true)
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_REFERENCE_ID];
        }

        return $attributes ?? [];
    }

    protected function parseVerifyResponse($response, $verify)
    {
        $response = $response->body;

        if (empty($response) === true)
        {
            return $response;
        }

        $response = $this->getEncryptor()->decryptString($response);

        $response = simplexml_load_string($response);

        //
        // Converting all elements of $response xml into an array
        //
        $responseArray = json_decode(json_encode($response), true);

        $tableToBeReturned = $responseArray['Table1'];

        //
        // We are returning the first table entry that contains
        // a successful status.
        //
        $numSuccess = 0;

        $bankPaymentIdInDB = $verify->payment->getBankPaymentId();

        foreach ($responseArray as $key => $table)
        {
            if ((isset($table[ResponseFields::PAYMENT_STATUS])) and
                ($table[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
            {
                if(($table['BID'] === $bankPaymentIdInDB) or ($bankPaymentIdInDB === null))
                {
                    $tableToBeReturned = $table;
                }

                $numSuccess++;
            }
        }

        //
        // If the number of successful
        // transactions is greater than 1, then this is an error
        // and therefore we throw an exception
        //
        if ($numSuccess > 1)
        {
            $data = [
                'response_array' => $responseArray,
                'payment_id'     => $this->input['payment']['id'],
                'num_success'    => $numSuccess,
                'gateway'        => $this->gateway,
            ];

            $this->trace->info(TraceCode::MULTIPLE_TABLES_IN_VERIFY_RESPONSE, ['response_data' => $data]);
        }

        return $tableToBeReturned;
    }

    protected function getAuthSuccessStatus()
    {
        return Status::getAuthSuccessStatus();
    }

    protected function setBankingTypeAndDomainType($input)
    {
        if (isset($input['payment']) === true)
        {
            // If emandate registration payment
            if (($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL))
            {
                $this->setBankingType(BankingType::EMANDATE);
            }
            else if ($input['payment']['bank'] === Payment\Processor\Netbanking::UTIB_C)
            {
                $this->setBankingType(BankingType::CORPORATE);
            }
        }

         $this->setDomainType();
    }
    protected function setDomainType()
    {
        $this->domainType = $this->getBankingType();

        //  $this->domainType = $this->getActionType() . '_' . $this->getMode();
    }

    /*
     *  Overriding parent class's method
     */
    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->domainType ?? $this->mode;

        if ($domainType !== BankingType::EMANDATE)
        {
            // For retail the base url changes for live mode and test mode
            if ($this->domainType === BankingType::RETAIL)
            {
                $domainType .= '_' . $this->action . '_' . $this->mode;
            }
            else
            {
                $domainType .= '_' . $this->action;
            }
        }
        else
        {
            // For EMandate, add test and live domain URLs
            $domainType .= '_' . $this->getMode();
        }

        $domainConstantName = strtoupper($domainType).'_DOMAIN';

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        $domainType = strtoupper($this->domainType);

        return constant($ns.'\Url::'.$type.'_'.$domainType);
    }

    public function getMerchantId()
    {
        if ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            return $this->getEmandateMerchantId();
        }

        if ($this->mode === Mode::TEST)
        {
            if ($this->isRetailBanking() === true)
            {
                return $this->getTestMerchantId();
            }
            else if ($this->isCorporateBanking() === true)
            {
                return $this->getTestMerchantIdCorporate();
            }
        }
        else
        {
            return $this->getLiveMerchantId();
        }
    }

    protected function getTestMerchantIdCorporate()
    {
        return $this->config['test_merchant_id_corporate'];
    }

    protected function getLiveSecret()
    {
        assertTrue ($this->mode === Mode::LIVE);

        if ($this->isRetailBanking() === true)
        {
            if ($this->action === Action::VERIFY)
            {
                return $this->config['verify_live_hash_secret'];
            }

            return $this->config['live_hash_secret_new'];
        }
        else if ($this->isCorporateBanking() === true)
        {
            if ($this->action === Action::VERIFY)
            {
                $key = $this->config['live_hash_secret_corporate_verify'];

                // Since the encryption key has to be of size 16 for encryption block to
                // of size 16.
                return substr($key, 0, 16);
            }
            else
            {
                return $this->config['live_hash_secret_corporate'];
            }
        }
    }

    protected function getTestSecret()
    {
        assertTrue ($this->mode === Mode::TEST);

        if ($this->isRetailBanking() === true)
        {
            if ($this->action === Action::VERIFY)
            {
                return $this->config['verify_test_hash_secret'];
            }

            return $this->config['test_hash_secret_new'];
        }
        else if ($this->isCorporateBanking() === true)
        {
            if ($this->action === Action::VERIFY)
            {
                $key = $this->config['test_hash_secret_corporate_verify'];

                return substr($key, 0, 16);
            }
            else
            {
                return $this->config['test_hash_secret_corporate'];
            }
        }
    }

    public function getPaymentIdFromServerCallback($input)
    {
        return $input[ResponseFields::MERCHANT_REFERENCE];
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount in Rupees
     */
    protected function formatAmount($amount): string
    {
        return $amount / 100;
    }

    protected function getVerifySecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['verify_test_hash_secret'];
        }

        return $this->config['verify_live_hash_secret'];
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/cainfo.pem';

        return $clientCertPath;
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::YES))
        {
            return true;
        }

        $attrs = [
            Base\Entity::STATUS             => Status::YES,
            Base\Entity::BANK_PAYMENT_ID    => $input['gateway']['gateway_payment_id'],
        ];

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }
}
