<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Models\Payment\Verify\Action as VerifyAction;
use RZP\Gateway\Netbanking\Axis\Emandate\EmandateTrait;

class Gateway extends Base\Gateway
{
    use EmandateTrait;
    use AuthorizeFailed;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    protected $bankingType = self::RETAIL;

    protected $sortRequestContent = false;

    protected $map = [
        RequestFields::AMOUNT                   => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_REFERENCE       => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE                => Base\Entity::REFERENCE1,

        // E Mandate specific fields
        Emandate\RequestFields::CUSTOMER_REF_NO => Base\Entity::SI_TOKEN
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

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
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

        $content = $this->getDataFromResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
             $content[RequestFields::MERCHANT_REFERENCE]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($content['AMT'], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkResponseStatus($attrs, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
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

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }

    public function verifyPayment(Verify $verify)
    {
        if ($verify->input['payment'][Payment\Entity::RECURRING] === true)
        {
            $this->setRecurringVerifyStatus($verify);

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

        if ($verify->input['payment'][Payment\Entity::RECURRING] === true)
        {
            if (Emandate\StatusCode::isSuccess($response[Emandate\ResponseFields::STATUS_CODE]))
            {
                $verify->gatewaySuccess = true;
            }
        }
        else
        {
            if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
                ($response[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }
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

        return $data;
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
        $input = $verify->input;
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
            RequestFields::ENCRYPTED_STRING         => $encryptedString,
            RequestFields::RETURN_URL               => $input['callbackUrl']
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

        $this->traceGatewayPaymentRequest($data, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        return $crypto->encryptString($stringToEncrypt);
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
    protected function prepareStringToEncrypt(array $data)
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '~' . $value;
        }

        $queryString = implode('$', $queryArray);

        return $queryString;
    }

    protected function getDataFromResponse(array $encryptedResponse)
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

        parse_str($decryptedString, $response);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $response);

        $this->checkDecryptionFailure($encryptedString, $response);

        return $response;
    }

    protected function checkDecryptionFailure(string $encryptedString, array $content)
    {
        if (empty($content) === true)
        {
            $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['encrypted_string' => $encryptedString,
                 'payment_id'       => $content[ResponseFields::MERCHANT_REFERENCE]]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }
    }

    /**
     * The default success status is Y, but this method accepts the any possible success value to ensure usability
     *
     * @param array $attributes
     * @param array $content
     * @param string $status
     * @throws Exception\GatewayErrorException
     */
    protected function checkResponseStatus(array $attributes, array $content, string $status = Status::YES)
    {
        if ((isset($attributes[Base\Entity::STATUS]) === false) or
            ($attributes[Base\Entity::STATUS] !== $status))
        {
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
            Base\Entity::STATUS          => $content[ResponseFields::STATUS],
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

    protected function parseResponseXml(string $response)
    {
        if (empty($response) === true)
        {
            return $response;
        }

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

        foreach ($responseArray as $key => $table)
        {
            if ($table[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS)
            {
                $tableToBeReturned = $table;

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

            $this->trace->error(TraceCode::MULTIPLE_TABLES_IN_VERIFY_RESPONSE, ['response_data' => $data]);

            throw new Exception\PaymentVerificationException(
                $data,
                null,
                VerifyAction::FINISH,
                ErrorCode::SERVER_ERROR_MULTIPLE_SUCCESS_TRANSACTIONS_IN_VERIFY
            );
        }

        return $tableToBeReturned;
    }

    protected function getAuthSuccessStatus()
    {
        return Status::getAuthSuccessStatus();
    }

    protected function setBankingTypeAndDomainType($input)
    {
        if (
            (isset($input['payment']) === true) and
            ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        )
        {
            $this->setBankingType(self::EMANDATE);
        }

         $this->setDomainType();
    }

    protected function setDomainType()
    {
        $this->domainType = $this->getBankingType();
    }

    /*
     *  Overriding parent class's method
     */
    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->domainType ?? $this->mode;

        if ($domainType !== self::EMANDATE)
        {
            $domainType .= '_' . $this->action;
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
            return $this->getTestMerchantId();
        }
        else
        {
            return $this->getLiveMerchantId();
        }
    }

    protected function getLiveSecret()
    {
        assert ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount in Rupees
     */
    protected function formatAmount(int $amount): string
    {
        return $amount / 100;
    }
}
