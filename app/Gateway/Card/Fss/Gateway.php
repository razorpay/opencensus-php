<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = Payment\Gateway::CARD_FSS;

    protected $acquirerMethodMap = [
        Acquirer::SBI => 'post',
        Acquirer::BOB => 'get',
        Acquirer::FSS => 'get',
    ];

    /**
     * Fss Gateway has purchase model so framing the request here
     * after persisting the gateway entity.
     *
     * @param array $input
     *
     * @return array
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $purchaseRequestFields = $this->getPurchaseRequestContentArray($input);

        $purchaseRequestContent = $this->getPurchaseRequestContent($purchaseRequestFields, $input);

        $gatewayAcquirer = $this->terminal->getGatewayAcquirer();

        $request = $this->getPurchaseRequestFieldsArray($purchaseRequestContent,
            $this->acquirerMethodMap[$gatewayAcquirer], Action::PURCHASE);

        $purchaseFields = $this->getPurchaseFields($purchaseRequestFields);

        $this->createGatewayPaymentEntity($purchaseFields, $input);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    /**
     * callback function for all the purchase requests.
     * @param array $input
     *
     * @return array
     */
    public function callback(array $input): array
    {
        parent::callback($input);

        // Trace payment callback
        $this->traceGatewayData($input['gateway'], TraceCode::GATEWAY_PAYMENT_CALLBACK);

        $gatewayResponse = $input['gateway'];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE);

        $this->handleGatewayError($gatewayResponse, $gatewayPayment);

        if ($this->isEmptyTranData($gatewayResponse) === false)
        {
            $gatewayContent = $this->getDecryptedRequestContent($gatewayResponse[Fields::TRANDATA], $input);

            $attributes = $this->getCallbackFields($gatewayContent);

            $gatewayPayment->fill($attributes);

            if (isset($gatewayContent[Fields::AMOUNT]) === true)
            {
                $expectedAmount = $this->getFormattedAmount($input['payment']['amount'] / 100);

                $actualAmount = $this->getFormattedAmount($gatewayContent[Fields::AMOUNT]);

                $this->assertAmount($expectedAmount, $actualAmount);
            }

            // assert paymentId only if trackid is set in callback request
            if (isset($gatewayContent[Fields::TRACK_ID]) === true)
            {
                $this->assertPaymentId($input['payment']['id'], $gatewayContent[Fields::TRACK_ID]);
            }
        }

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkErrorMessage($gatewayPayment, $gatewayResponse);

        $this->checkCapturedStatus($gatewayPayment, ErrorCode::BAD_REQUEST_PAYMENT_FAILED);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    /**
     * Refund Method for gateway Entity
     *
     * @param array $input
     */
    public function refund(array $input)
    {
        parent::refund($input);

        $refundRequestContentArray = $this->getGatewayRequestContentArray($input);

        $refundRequestContent = $this->getGatewayRequestContent($refundRequestContentArray);

        $request = $this->getStandardRequestArray($refundRequestContent, 'post', Action::REFUND);

        $response = $this->postRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'refund_id' => $input['refund']['id'],
                'response'  => $response->body,
            ]
        );

        $responseFields = $this->getResponseFields($response);

        $content = $response->body;

        $attributes = $this->getRefundFields($responseFields, $input);

        $this->parseResponseStatus($attributes);

        $gatewayEntity = $this->createGatewayPaymentEntity($attributes, $input);

        // Doing Additional check with the result because error messages are sent in result.
        if ((in_array($responseFields[Fields::RESULT], Status::$successStates) === false) and
            ($this->isErrorMessage($responseFields[Fields::RESULT]) === true))
        {
            $this->checkErrorMessage($gatewayEntity, $responseFields, $content);
        }

        $this->checkCapturedStatus($gatewayEntity, ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED, $content);

        // $content is a string hence not using json_encode() for GATEWAY_RESPONSE
        return [
            Payment\Gateway::GATEWAY_RESPONSE  => $content,
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($responseFields)
        ];
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        $verify = new Base\Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        return $this->verifyRefundResponse($verify);
    }

    /**
     * Since URls for all the acquirers are different handling seperately.
     * @param string $type
     *
     * @return string
     */
    public function getUrl($type = null)
    {
        $gatewayAquirer = $this->getGatewayAcquirer($this->input);

        $urlMap = Url::$urlMap;

        $domainConstantName = $this->mode.'_domain';

        $urlDomain = $urlMap[$gatewayAquirer][$domainConstantName];

        $actionType = $type ?? $this->action;

        $relativeUrl = $urlMap[$gatewayAquirer][$actionType];

        return $urlDomain . $relativeUrl;
    }

    /**
     * @param array       $content
     * @param string      $method
     * @param string|null $type
     *
     * @return array
     */
    public function getPurchaseRequestFieldsArray($content = [], $method = 'post', $type = null)
    {
        $request = $this->getStandardRequestArray([], $method, $type);

        $request['url'] .= http_build_query($content);

        return $request;
    }

    /**
     * Frames fields to create gateway entity.
     * @param array $requestFields
     *
     * @return array
     */
    protected function getPurchaseFields(array $requestFields): array
    {
        $attributes = [
            Entity::AMOUNT      => $requestFields[Fields::AMOUNT] * 100,
            Entity::CURRENCY    => $requestFields[Fields::CURRENCY_CODE],
        ];

        return $attributes;
    }

    /**
     * Gets all the required fields for making purchase request.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getPurchaseRequestContentArray(array $input)
    {
        $requestContent = [
            Fields::CARD          => $input[E::CARD][Card\Entity::NUMBER],
            Fields::CVV           => $input[E::CARD][Card\Entity::CVV],
            Fields::CURRENCY_CODE => Currency::getIsoCode(Currency::INR),
            Fields::EXPIRY_YEAR   => $input[E::CARD][Card\Entity::EXPIRY_YEAR],
            Fields::EXPIRY_MONTH  => $this->getFormattedExpMonth($input[E::CARD][Card\Entity::EXPIRY_MONTH]),
            Fields::TYPE          => $this->getCardType($input[E::CARD][Card\Entity::TYPE]),
            Fields::MEMBER        => $this->getCardHolderName($input),
            Fields::AMOUNT        => $input[E::PAYMENT][Payment\Entity::AMOUNT] / 100,
            Fields::ACTION        => Action::getActionValue(Action::PURCHASE),
            Fields::TRACK_ID      => $input[E::PAYMENT][Payment\Entity::ID],
            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],
            Fields::ID            => $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
            Fields::LANGUAGE_ID   => Constants::LANGUAGE_USA,
        ];

        $this->modifyGatewayRequestContentArray($requestContent, $input);

        // Trace the payment request after removing the sensitive fields.
        $this->traceGatewayData($requestContent, TraceCode::GATEWAY_PAYMENT_REQUEST);

        return $requestContent;
    }

    protected function getCardHolderName($input)
    {
        $name = $input[E::CARD][Card\Entity::NAME];

        $name = preg_replace('/[^a-zA-Z ]/', '', $name);

        return trim(preg_replace('/\s+/', ' ',$name));
    }

    /**
     * Adds extra elements based on gateway acquirer.
     *
     * @param array $requestContent
     * @param array $input
     */
    protected function modifyGatewayRequestContentArray(array & $requestContent, array $input)
    {
        $gatewayAquirer = $this->getGatewayAcquirer($input);

        switch ($gatewayAquirer)
        {
            case Acquirer::FSS:
                $requestContent[Fields::UDF3]      = $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID];
                $requestContent[Fields::UDF5]      = strtolower(Constants::TRACK_ID);
                $requestContent[Fields::BANK_CODE] = BankCodes::getBankCodeByIfsc($input[E::CARD][Card\Entity::ISSUER]);

                if ($this->action === Action::REFUND)
                {
                    $requestContent[Fields::PASSWORD] = $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];
                }

                if ($this->mode === Mode::TEST)
                {
                    $requestContent[Fields::ID]         = $this->config['fss']['merchant_id'];
                    $requestContent[Fields::UDF3]       = $this->config['fss']['merchant_id'];

                    if ($this->action === Action::REFUND)
                    {
                        $requestContent[Fields::PASSWORD] = $this->config['fss']['terminal_password'];
                    }
                }

                break;
            case Acquirer::BOB:
                $requestContent[Fields::PASSWORD] = $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];
                $requestContent[Fields::UDF5]     = Constants::TRACK_ID;

                if ($this->mode === Mode::TEST)
                {
                    $requestContent[Fields::ID]       = $this->config['barb']['merchant_id'];
                    $requestContent[Fields::PASSWORD] = $this->config['barb']['terminal_password'];
                }

                if ($input[E::MERCHANT]->isFeatureEnabled(Feature\Constants::VIJAYA_MERCHANT) === true)
                {
                    $requestContent[Fields::UDF7]       = $input[E::CARD][Card\Entity::NAME];
                    $requestContent[Fields::UDF8]       = $input[E::PAYMENT][Payment\Entity::EMAIL];
                    $requestContent[Fields::UDF9]       = $input[E::PAYMENT][Payment\Entity::CONTACT];
                    $requestContent[Fields::UDF10]      = 'Bangalore, Karnataka';
                    $requestContent[Fields::UDF11]      = $input[E::PAYMENT][Payment\Entity::AMOUNT] / 100;
                    $requestContent[Fields::UDF13]      = $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2];
                    $requestContent[Fields::UDF14]      = $input[E::TERMINAL][Terminal\Entity::GATEWAY_ACCESS_CODE];
                }

                $requestContent[Fields::UDF6]       = str_replace("&", "", $input[E::MERCHANT]->getDbaName());
                $requestContent[Fields::UDF12]      = $input[E::PAYMENT][Payment\Entity::ID];

                break;
            case Acquirer::SBI:
                $requestContent[Fields::UDF1]       = strtolower(Constants::UDF1);
                $requestContent[Fields::UDF2]       = strtolower(Constants::UDF2);
                $requestContent[Fields::UDF3]       = $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID];
                $requestContent[Fields::UDF4]       = strtolower(Constants::UDF4);
                $requestContent[Fields::UDF5]       = Constants::TRACK_ID;
                $requestContent[Fields::PASSWORD]   = $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

                if ($this->mode === Mode::TEST)
                {
                    $requestContent[Fields::ID]         = $this->config['sbin']['merchant_id'];
                    $requestContent[Fields::UDF3]       = $this->config['sbin']['merchant_id'];
                    $requestContent[Fields::PASSWORD]   = $this->config['sbin']['terminal_password'];
                }

                break;
        }
    }

    /**
     * @param array $requestContent
     * @param array $input
     *
     * @return array
     */
    protected function getPurchaseRequestContent(array $requestContent, array $input): array
    {
        // Entire request content is wrapped in xml.
        $requestBuffer = $this->getGatewayRequestContent($requestContent);

        // Encrypted request content
        $tranData = $this->getEncryptedRequestContent($requestBuffer, $input);

        $content = [
            Fields::TRAN_DATA     => $tranData,
            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],
            Fields::TRANPORTAL_ID => $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
        ];

        return $content;
    }

    /**
     * Creates a gateway payment entry.
     * @param array $attributes
     * @param array $input
     *
     * @return Entity
     */
    protected function createGatewayPaymentEntity(array $attributes, array $input): Entity
    {
        $gatewayPaymentEntity = $this->getNewGatewayPaymentEntity();

        $gatewayPaymentEntity->setAcquirer($input['terminal']['gateway_acquirer']);

        $gatewayPaymentEntity->setPaymentId($input['payment']['id']);

        if (empty($input['refund']['id']) === false)
        {
            $gatewayPaymentEntity->setRefundId($input['refund']['id']);
        }

        $gatewayPaymentEntity->setAction($this->action);

        $gatewayPaymentEntity->fill($attributes);

        $this->repo->saveOrFail($gatewayPaymentEntity);

        return $gatewayPaymentEntity;
    }

    /**
     * Encrypts the string as per the encryption rules of the gateway provider.
     * @param string $str
     *
     * @return string
     */
    protected function getEncryptedRequestContent(string $str, array $input): string
    {
        $secretKey = $this->getSecret();

        $gatewayAquirer = $this->getGatewayAcquirer($input);

        switch ($gatewayAquirer)
        {
            case Acquirer::FSS:
                $crypto = new AesCrypto(AesCrypto::MODE_CBC, $secretKey, $secretKey);

                return $crypto->encryptString($str);
                break;
            case Acquirer::BOB:
                $crypto = new TripleDESCrypto(TripleDESCrypto::MODE_ECB, $secretKey, true);

                return $crypto->encryptString($str);
                break;
            case Acquirer::SBI:
                $crypto = new SbiAesCrypto(SbiAesCrypto::MODE_ECB, $secretKey, $secretKey);

                return $crypto->encryptString($str);
                break;
            default:
                break;
        }
    }

    /**
     * @param string $str
     *
     * @return array
     */
    protected function getDecryptedRequestContent(string $str, array $input)
    {
        $secretKey = $this->getSecret();

        $gatewayAquirer = $this->getGatewayAcquirer($input);

        $decryptedString = '';

        switch ($gatewayAquirer)
        {
            case Acquirer::FSS:
                $crypto = new AesCrypto(AesCrypto::MODE_CBC, $secretKey, $secretKey);

                $decryptedString = $crypto->decryptString($str);
                break;
            case Acquirer::BOB:
                $crypto = new TripleDESCrypto(TripleDESCrypto::MODE_ECB, $secretKey, false);

                $decryptedString = $crypto->decryptString($str);
                break;
            case Acquirer::SBI:
                $crypto = new SbiAesCrypto(SbiAesCrypto::MODE_ECB, $secretKey);

                $decryptedString = $crypto->decryptString($str);
                break;
            default:
                break;
        }

        // Trace payment decrypted data as string because if their
        // is any error in data format we can get to know about this.
        $this->traceGatewayData([
                                    'gateway_data' => $decryptedString,
                                    'payment_id'   => $input['payment']['id'],
                                    'gateway'      => $this->gateway,
                                ],
                                TraceCode::GATEWAY_PAYMENT_CALLBACK);

        $decryptedResult = Utility::createResponseArray($decryptedString);

        return $decryptedResult;
    }

    /**
     * Filters out the relevant mappings and gets the data.
     * @param array $gatewayContent
     *
     * @return array
     */
    protected function getFormattedGatewayFields(array $gatewayContent): array
    {
        $attributes = [
            Entity::RECEIVED => true,
        ];

        $mandatoryFields = [
            Entity::GATEWAY_TRANSACTION_ID,
            Entity::STATUS,
        ];

        $errorResponseFields = [
            Fields::ERROR_TEXT,
            Fields::ERROR,
        ];

        // Razorpay vs FSS Field mapping
        $callbackFieldMapping = [
            Entity::GATEWAY_PAYMENT_ID     => Fields::PAY_ID,
            Entity::GATEWAY_TRANSACTION_ID => Fields::TRAN_ID,
            Entity::REF                    => Fields::REF,
            Entity::AUTH                   => Fields::AUTH,
            Entity::POST_DATE              => Fields::POST_DATE,
            Entity::STATUS                 => Fields::RESULT,
            Entity::AUTH_RES_CODE          => Fields::AUTH_RES_CODE,
            Entity::ERROR_MESSAGE          => $errorResponseFields,
        ];

        $missingCallbackFields = [];

        foreach ($callbackFieldMapping as $key => $value)
        {
            // Checking with empty "null" because we use simple_xml to deserialize the data
            // so null is converted to string.
            if (is_array($value) === true)
            {
                // To handle case where error_text is sent by bob and error is sent by fss.
                foreach ($value as $gatewayReponseField)
                {
                    $this->validateGatewayResponseField($key,
                                                        $gatewayReponseField,
                                                        $attributes,
                                                        $missingCallbackFields,
                                                        $gatewayContent);
                }
            }
            else
            {
                $this->validateGatewayResponseField($key, $value, $attributes, $missingCallbackFields, $gatewayContent);
            }
        }

        // Calculate missing fields.
        $missingFields = array_intersect($mandatoryFields, $missingCallbackFields);

        // When error message is present mandatory fields are not required.
        if (count($missingFields) > 0 and empty($attributes[Entity::ERROR_MESSAGE]) === true)
        {
            $this->trace->error(
                TraceCode::GATEWAY_PAYMENT_MISSING_FIELD,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'fields'     => $missingFields,
                    'message'    => 'Mandatory Fields are missing',
                    'gateway'    => $this->gateway,
                ]
            );
        }

        return $attributes;
    }

    private function validateGatewayResponseField($gatewayField,
                                                  $gatewayResponseField,
                                                  & $attributes,
                                                  & $missingCallbackFields,
                                                  $gatewayContent)
    {
        if ((empty($gatewayContent[$gatewayResponseField]) === false) and
            ($gatewayContent[$gatewayResponseField] !== 'null'))
        {
            $attributes[$gatewayField] = $gatewayContent[$gatewayResponseField];
        }
        else
        {
            $missingCallbackFields[] = $gatewayField;
        }
    }

    protected function getCallbackFields(array $gatewayContent): array
    {
        return $this->getFormattedGatewayFields($gatewayContent);
    }

    /**
     * @param $response
     *
     * @return array
     */
    protected function getResponseFields($response)
    {
        $responseBody = $response->body;

        $responseFields = Utility::createResponseArray($responseBody);

        return $responseFields;
    }
    /**
     * Refund Fields to set the gateway entity.
     * @param $refundResponse
     * @param $input
     *
     * @return array
     */
    protected function getRefundFields($refundResponse, $input)
    {
        $refundFields = $this->getFormattedGatewayFields($refundResponse);

        $refundFields[Entity::AMOUNT] = $input[E::REFUND][Entity::AMOUNT];

        $refundFields[Entity::CURRENCY] = Currency::getIsoCode(Currency::INR);

        return $refundFields;
    }

    /**
     * parsing the verify refund Request Response a
     * @param Base\Verify $verify
     *
     * @return array
     */
    protected function verifyRefundResponse(Base\Verify $verify)
    {
        $verifyResponse = $verify->verifyResponseContent;

        $scroogeResponse = new Base\ScroogeResponse();

        $scroogeResponse->setGatewayVerifyResponse($verifyResponse)
                        ->setGatewayKeys($this->getGatewayData($verifyResponse));

        if ((empty($verifyResponse[Fields::RESULT]) === false) and
            ($verifyResponse[Fields::RESULT] === Status::SUCCESS))
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                               ->toArray();
    }

    /**
     * @param Base\Verify $verify
     *
     * @throws Exception\GatewayErrorException
     */
    protected function verifyPayment(Base\Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $verifyResponse = $verify->verifyResponseContent;

        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if (empty($verifyResponse[Fields::RESULT]) === true)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $status = $verifyResponse[Fields::RESULT];

        $verify->gatewaySuccess = (($status === Status::SUCCESS) or ($status === Status::CAPTURED));

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input['payment']);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $this->verifyAmountMismatch($verify, $input, $verifyResponse, E::PAYMENT);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;
    }

    /**
     * @param Base\Verify $verify
     * @param array       $input
     * @param array       $response
     * @param string      $entity       Payment|Refund
     */
    protected function verifyAmountMismatch(Base\Verify $verify, array $input, array $response, string $entity)
    {
        $expectedAmount = $this->getFormattedAmount($input[$entity]['amount'] / 100);
        $actualAmount = $this->getFormattedAmount($response[Fields::AMOUNT]);

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    /**
     * @param Base\Entity $gatewayPayment
     * @param array       $payment
     *
     * @return bool
     */
    protected function getVerifyApiStatus(Base\Entity $gatewayPayment, array $payment)
    {
        if (($payment['status'] === 'failed') or
            ($payment['status'] === 'created'))
        {
            $apiStatus = false;

            if ($gatewayPayment['status'] === Status::CAPTURED)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }
        else
        {
            $apiStatus = true;

            if ($gatewayPayment['status'] !== Status::CAPTURED)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }

        return $apiStatus;
    }

    /**
     * Verify payment Request
     * @param Base\Verify $verify
     */
    protected function sendPaymentVerifyRequest(Base\Verify $verify)
    {
        $input = $verify->input;

        $requestContentArray = $this->getGatewayRequestContentArray($input);

        $requestContent = $this->getGatewayRequestContent($requestContentArray);

        $request = $this->getStandardRequestArray($requestContent, 'post', Action::VERIFY);

        $this->traceGatewayData($requestContentArray, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->postRequest($request);

        $response = $this->parseVerifyResponse($response);

        $verify->setVerifyResponseContent($response);
    }

    /**
     * @param $response
     *
     * @return array|string
     */
    protected function parseVerifyResponse($response)
    {
        $responseBody = $response->body;

        $response = Utility::createResponseArray($responseBody);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'body' => $response
            ]
        );

        return $response;
    }

    protected function removeSensitiveRequestFields(array $requestContent)
    {
        $sensitiveKeys = [
            Fields::ID,
            Fields::PASSWORD,
            Fields::CARD,
            Fields::CVV,
            Fields::EXPIRY_MONTH,
            Fields::EXPIRY_YEAR,
        ];

        return array_diff_key($requestContent, array_flip($sensitiveKeys));
    }

    protected function getGatewayRequestContentArray($input)
    {
        $traceCode = null;

        $requestContent = [
            Fields::CURRENCY_CODE  => Currency::getIsoCode(Currency::INR),
            Fields::TYPE           => $this->getCardType($input[E::CARD][Card\Entity::TYPE]),
            Fields::LANGUAGE_ID    => Constants::LANGUAGE_USA,
            Fields::ID             => $input[E::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
        ];

        switch ($this->action)
        {
            case Action::VERIFY:
                // In verify also fss needs a trackId.
                $requestContent[Fields::TRANSACTION_ID] = $input['payment']['id'];
                $requestContent[Fields::ACTION]         = Action::getActionValue(Action::VERIFY);
                $requestContent[Fields::TRACK_ID]       = Entity::generateUniqueId();
                $requestContent[Fields::AMOUNT]         = $input[E::PAYMENT][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST;
                break;
            case Action::VERIFY_REFUND:
                $requestContent[Fields::TRANSACTION_ID] =  $input['refund']['id'];
                $requestContent[Fields::ACTION]         = Action::getActionValue(Action::VERIFY);
                $requestContent[Fields::TRACK_ID]       = Entity::generateUniqueId();
                $requestContent[Fields::AMOUNT]         = $input[E::REFUND][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_REFUND_VERIFY_REQUEST;
                break;
            case Action::REFUND:
                $requestContent[Fields::TRANSACTION_ID] = $input['payment']['id'];
                $requestContent[Fields::ACTION]         = Action::getActionValue(Action::REFUND);
                $requestContent[Fields::TRACK_ID]       = $input[E::REFUND][Entity::ID];
                $requestContent[Fields::AMOUNT]         = $input[E::REFUND][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_REFUND_REQUEST;
                break;
        }

        $this->modifyGatewayRequestContentArray($requestContent, $input);

        $this->traceGatewayData($requestContent, $traceCode);

        return $requestContent;
    }

    /**
     * FSS sends error messages in status, so parsing the same for storing.
     * @param array $attributes
     */
    protected function parseResponseStatus(array & $attributes)
    {
        $this->sanitizeStatus($attributes[Entity::STATUS]);

        $status = $attributes[Entity::STATUS];

        if ((empty($status) === false) and
            (trim($status) !== Status::CAPTURED))
        {
            // Error message is sent as status.
            if ($this->isErrorMessage($status) === true)
            {
                $attributes[Entity::ERROR_MESSAGE] = $status;

                $attributes[Entity::STATUS] = Status::NOT_CAPTURED;
            }
        }
    }

    /**
     * Gateway sends ErrorMessages in result so to detect if the result is error or not.
     * @param string $errorMessageText
     *
     * @return boolean
     */
    private function isErrorMessage(string $errorMessageText)
    {
        foreach (Constants::$errorMessageStart as $errorText)
        {
            $result = substr($errorMessageText, 0, strlen($errorText)) === $errorText;

            if ($result === true)
            {
                return $result;
            }
        }

        return false;
    }

    /**
     * Sanitizing status because in fss gateway, if we have any errors in
     * the response we will get the result with error + description as twice.
     * <result>GW001-somerandomerror</result><result>GW001-somerandomerror</result>
     *
     * @param $status
     */
    private function sanitizeStatus(& $status)
    {
        if (empty($status) === false and is_array($status))
        {
            $status = current($status);
        }
    }
    /**
     * @param array $requestContent
     *
     * @return string
     */
    protected function getGatewayRequestContent(array $requestContent): string
    {
        // Entire request content is wrapped in xml.
        $requestBuffer = Utility::createRequestXml($requestContent);

        return $requestBuffer;
    }

    /**
     * @param $request
     *
     * @return \WpOrg\Requests\Response
     */
    protected function postRequest($request)
    {
        $request['options'] = $this->getRequestOptions();

        $request['headers'] = $this->getRequestHeaders();

        $response = $this->sendGatewayRequest($request);

        return $response;
    }

    /**
     * Headers for the s2s call.
     * @return array
     */
    protected function getRequestHeaders()
    {
        $headers = [
            'Content-Type:application/xml',
            'Cache-Control: no-cache',
        ];

        return $headers;
    }

    /**
     * Verify of ssl certs should be false.
     * @return mixed
     */
    protected function getRequestOptions()
    {
        $options['verify'] = false;
        $options['timeout'] = 60;

        return $options;
    }

    /**
     * @param Entity $gatewayPayment
     * @param array  $gatewayContent
     *
     * @throws Exception\GatewayErrorException
     */
    protected function checkErrorMessage($gatewayPayment, $gatewayContent, $content = '')
    {
        // FSS sends just cancelled in the error instead of error code + desc.
        if ((empty($gatewayPayment->getErrorMessage()) === false) and
            ($gatewayPayment->getErrorMessage() !== Constants::CANCELLED))
        {
            $gatewayCode = $this->getErrorCode($gatewayPayment->getErrorMessage());

            $errorCode = ErrorCodes\ErrorCodes::getInternalErrorCode(['code' => $gatewayCode]);

            $errorDesc = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription(['code' => $gatewayCode]);

            // $content is a string hence not using json_encode() for GATEWAY_RESPONSE
            throw new Exception\GatewayErrorException(
                $errorCode,
                $gatewayCode,
                $errorDesc,
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => $content,
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($gatewayContent)
                ]);
        }
    }

    /**
     * Util function to split errorcodes
     * @param $errorText
     *
     * @return string
     */
    private function getErrorCode($errorText)
    {
        return trim(current(explode('-', $errorText)));
    }

    /**
     * Formats expmonth for the gateways desire.
     * @param $expMonth
     *
     * @return string
     */
    private function getFormattedExpMonth($expMonth)
    {
        return str_pad($expMonth, 2, '0', STR_PAD_LEFT);
    }

    /**
     * We return credit card as default type. if debit is not present.
     * because we set card type as credit when it's unknown in card entity.
     * @param $cardType
     *
     * @return string
     */
    private function getCardType($cardType)
    {
        $gatewayAcquirer = $this->getGatewayAcquirer($this->input);

        $acquirerCardTypeMap = CardType::getCardTypesByAcquirer($gatewayAcquirer);

        if (isset($acquirerCardTypeMap[$cardType]) === false)
        {
            throw new Exception\ServerErrorException('card type not supported',
                ErrorCode::SERVER_ERROR_CARD_TYPE_NOT_SUPPORTED, [
                    'cardType' => $cardType,
                    'gateway'  => $this->gateway,
                ]);
        }

        return $acquirerCardTypeMap[$cardType];
    }

    /**
     * @param $amount
     *
     * @return string
     */
    private function getFormattedAmount($amount)
    {
        return number_format($amount, 2,'.', '');
    }

    /**
     * Fss sends captured/success if it's a successful transaction else it will send error messages.
     *
     * @param Entity $gateway
     * @param String $errorCode
     *
     * @throws Exception\GatewayErrorException
     */
    protected function checkCapturedStatus(Entity $gateway, $errorCode, $content = '')
    {
        $status = $gateway->getStatus();

        if (in_array($status, Status::$successStates) === false)
        {
            $refundFields = Utility::createResponseArray($content);

            // $content is a string hence not using json_encode() for GATEWAY_RESPONSE
            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                'Transaction not successful',
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => $content,
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($refundFields)
                ]
            );
        }
    }

    /**
     * over riding getTestSecret becuase for different acquirers we ahve different
     *
     * @return mixed
     */
    protected function getTestSecret()
    {
        assertTrue ($this->mode === Mode::TEST);

        $gatewayAquirer = $this->getGatewayAcquirer($this->input);

        return $this->config[strtolower($gatewayAquirer)]['test_hash_secret'];
    }

    /**
     * @param $input
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    private function getGatewayAcquirer($input)
    {
        $gatewayAquirer = $input[E::TERMINAL]->getGatewayAcquirer();

        if ((empty($gatewayAquirer) === true) or
            (in_array($gatewayAquirer, Acquirer::$validGatewayAcquirers) === false))
        {
            throw new Exception\LogicException(
                'Unsupported acquirer for the gateway',
                null,
                [
                    'acquirer' => $gatewayAquirer,
                ]);
        }

        return $gatewayAquirer;
    }

    /**
     * @param array  $content
     * @param string $traceCode
     */
    protected function traceGatewayData(array $content, string $traceCode)
    {
        $content = $this->removeSensitiveRequestFields($content);

        $this->trace->info(
            $traceCode,
            [
                'gateway_content' => $content,
            ]);
    }

    /**
     * Trandata is the encrypted text returned in the callback from the gateway.
     * @param array $gatewayResponse
     *
     * @return bool
     */
    protected function isEmptyTranData(array $gatewayResponse)
    {
        return (empty($gatewayResponse[Fields::TRANDATA]) === true);
    }

    /**
     * Checks and handles the first layer of the gateway response for any errors.
     *
     * @param $gatewayResponse
     * @param $gatewayPayment
     */
    protected function handleGatewayError($gatewayResponse, $gatewayPayment)
    {
        if (empty($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]) === false)
        {
            $gatewayPayment->setGatewayPaymentId($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]);
        }

        if (empty($gatewayResponse[Fields::GATEWAY_ERROR_TEXT]) === false)
        {
            $gatewayPayment->setErrorMessage($gatewayResponse[Fields::GATEWAY_ERROR_TEXT]);
        }
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                Fields::RESULT          => $refundFields[Fields::RESULT] ?? null,
                Fields::TRAN_ID         => $refundFields[Fields::TRAN_ID] ?? null,
                Fields::TRACK_ID        => $refundFields[Fields::TRACK_ID] ?? null,
                Fields::PAY_ID          => $refundFields[Fields::PAY_ID] ?? null,
                Fields::AUTH_RES_CODE   => $refundFields[Fields::AUTH_RES_CODE] ?? null,
                Fields::REF             => $refundFields[Fields::REF] ?? null,
            ];
        }
        return [];
    }

    /**
     * This function authorize the payment forcefully when verify api is not supported
     * or not giving correct response.
     *
     * @param $input
     * @return bool
     */
    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                        $input['payment']['id'],
                                        Action::AUTHORIZE);

        // If it's already authorized on gateway side, there's nothing to do here. We just return back.
        if (($gatewayPayment->getRef() !== null) and
            ($gatewayPayment->getReceived() === true))
        {
            return true;
        }

        $attributes = [
            Entity::STATUS  => Status::SUCCESS,
            Entity::REF     => $input['gateway']['reference_number']
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }
}
