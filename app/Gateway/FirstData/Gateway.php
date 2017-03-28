<?php

namespace RZP\Gateway\FirstData;

use Carbon\Carbon;
use Requests_Hooks;
use RZP\Constants;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Card;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';
    const CERTIFICATE_FORMAT_P12     = 'p12';

    const PROCESSING                 = 'PROCESSING';
    const SERVICES                   = 'SERVICES';

    const CHECKSUM_ATTRIBUTE = ConnectResponseFields::RESPONSE_HASH;

    protected static $testChance = null;

    protected $gateway = Constants\Entity::FIRST_DATA;

    const TRACE_CODE_MAPPING = [
        Base\Action::PURCHASE  => TraceCode::GATEWAY_PURCHASE_RESPONSE,
        Base\Action::CAPTURE   => TraceCode::GATEWAY_CAPTURE_RESPONSE,
        Base\Action::REFUND    => TraceCode::GATEWAY_REFUND_RESPONSE,
        Base\Action::REVERSE   => TraceCode::GATEWAY_REFUND_RESPONSE,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $requestContent = $this->getPreAuthRequestContentArray($input);

        if ($this->isSecondRecurringPayment($input) === true)
        {
            return $this->secondRecurring($input);
        }

        $authorizeFields = $this->getAuthorizeFields($requestContent);

        $authorizeEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

        $request = $this->getStandardRequestArray($requestContent);

        $this->traceGatewayPaymentRequest($request, $input);

        // Enabling optimized flow only for 30% of merchants
        // We'll enable it for all the merchants once we
        // test this flow properly
        // This flow won't work for RuPay as RuPay doesn't have a TermUrl
        // Any change in the data results in integrity failure.
        if (($input['card']['network_code'] !== Card\Network::RUPAY) and
            ($this->getChance() < 30))
        {
            // Ideally, we could have returned the request array from
            // here only.
            //
            // However, we prevent three network call on client side by
            // doing it on the server side here.

            $request = $this->makeRequestAndGetFormData($request);

            // Adding this check to remove one extra redirect to Razorpay
            // We internally handle the redirect as we know that the
            // redirection will come to us. This can happen in case of
            // not enrolled cards
            if (strpos($request['url'], 'https://api.razorpay.com/v1/') === 0)
            {
                $input['gateway'] = $request['content'];

                return $this->callback($input, false);
            }
            else if (isset($request['content']['PaReq']) === true)
            {
                // Caching original termUrl for 15 mins
                $this->app['cache']->put($this->getCacheKey($input), $request['content']['TermUrl'], 15);

                // Setting Razorpay callback as TermUrl to receive ACS response on
                // Razorpay and send it to IPG via s2s call
                $request['content']['TermUrl'] = $input['callbackUrl'];
            }
        }

        return $request;
    }

    protected function secondRecurring($input)
    {
        parent::action($input, Base\Action::PURCHASE);

        $requestContent = $this->getPurchaseRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PURCHASE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_PURCHASE_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response'   => $response
            ]
        );

        $purchaseFields = $this->getPurchaseFields($response, $input['payment']);

        $purchaseEntity = $this->createGatewayPaymentEntity($purchaseFields, $input);

        $this->checkApprovalCode($purchaseEntity);
    }

    protected function makeRequestAndGetFormData($request)
    {
        $request['headers']['User-Agent'] = $this->app['request']->header('User-Agent');
        $request['headers']['X-Forwarded-For'] = $this->app['request']->getRealClientIp();
        $request['headers']['X-Real-IP'] = $this->app['request']->getRealClientIp();

        $response = $this->sendGatewayRequest($request);

        $this->traceS2sCallResponse($response);

        $crawler = new Crawler($response->body, $request['url']);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            throw new Exception\GatewayTimeoutException('Gateway Timed Out', null, true);
        }

        $form = $formCrawler->form();

        $method = $form->getMethod();
        $content = $form->getValues();

        array_walk($content, function(&$value, $key)
        {
            $value = htmlentities($value);
        });

        $request = [
            'url'     => trim($form->getUri()),
            'method'  => strtolower($method),
            'content' => $content,
        ];

        return $request;
    }

    public function callback(array $input, $acs = true)
    {
        parent::callback($input);

        // Ideally, one check should be enough but adding additional check to
        // ensure robustness
        if ($this->app['cache']->get($this->getCacheKey($input)) !== null)
        {
            if ((isset($input['gateway']['PaRes']) === true) and
                ($acs === true))
            {
                $this->validateParesStatus($input);

                $input['gateway'] = $this->getCallbackGatewayContent($input);
            }
            else
            {
                $this->trace->info(
                    FIRST_DATA_PARES_MISSING,
                    [
                        'payment_id' => $input['payment_id'],
                        'gateway' => $input['gateway']
                    ]);
            }
        }

        $this->traceGatewayCallback($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $input['gateway'][ConnectResponseFields::ORDER_ID]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['gateway'][ConnectResponseFields::ORDER_ID],
            Base\Action::AUTHORIZE);

        $this->verifySecureHash($input['gateway']);

        $this->mockApprovalCodeIfNeeded($input['gateway']);

        $attributes = $this->getCallbackFields($input['gateway']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkApprovalCode($gatewayPayment);
    }

    protected function getCallbackGatewayContent(array $input)
    {
        $originalTermUrl = $this->app['cache']->get($this->getCacheKey($input));

        $request = [
            'url' => $originalTermUrl,
            'method' => 'post',
            'content' => $input['gateway']
        ];

        $response = $this->makeRequestAndGetFormData($request);

        return $response['content'];
    }

    public function capture(array $input)
    {
        parent::capture($input);

        if ($this->isCaptureNecessary($input) === false)
        {
            return;
        }

        $requestContent = $this->getCaptureRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_CAPTURE_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response' => $response
            ]
        );

        $captureFields = $this->getCaptureFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);

        $this->checkApprovalCode($captureEntity);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $requestContent = $this->getRefundRequestArray($input, TxnType::REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response' => $response
            ]
        );

        $refundFields = $this->getRefundFields($response, $input['refund']);

        $refundEntity = $this->createGatewayPaymentEntity($refundFields, $input);

        $this->checkApprovalCode($refundEntity);
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        $requestContent = $this->getReverseRequestArray($input, TxnType::REVERSE);

        $this->trace->info(TraceCode::GATEWAY_REVERSE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REVERSE_RESPONSE,
            [
                'response' => $response
            ]
        );

        $reverseFields = $this->getReverseFields($response, $input['refund']);

        $reverseEntity = $this->createGatewayPaymentEntity($reverseFields, $input);

        $this->checkApprovalCode($reverseEntity);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // First Data is not returning approval code in some cases.
    // In these cases, we mock the code and handle it appropriately.
    protected function mockApprovalCodeIfNeeded(& $gatewayCallback)
    {
        if (empty($gatewayCallback[ConnectResponseFields::APPROVAL_CODE]) === false)
        {
            $this->setApproval($gatewayCallback[ConnectResponseFields::APPROVAL_CODE]);

            return;
        }

        //Approval code wasn't returned, but we can generate one from fail fields
        if (isset($gatewayCallback[ConnectResponseFields::FAIL_RC]) === true)
        {
            $failCode = $gatewayCallback[ConnectResponseFields::FAIL_RC];

            $failReason = $gatewayCallback[ConnectResponseFields::FAIL_REASON];

            $mockedApprovalCode = implode(':', ['N', $failCode, $failReason]);
        }
        // Approval code wasn't returned, and neither were fail_rc and fail_reason
        // Assume failure, and mock the failed approval code.
        else
        {
            $mockedApprovalCode = implode(':', ['N', Codes::MOCK_FAIL_APPROVAL_CODE]);
        }

        $this->setApproval($mockedApprovalCode);

        $gatewayCallback[ConnectResponseFields::APPROVAL_CODE] = $mockedApprovalCode;
    }

    protected function setApproval($approvalCode)
    {
        // Request has failed if the first character
        // of the approval code string isn't 'Y'
        if ($approvalCode[0] === 'Y')
        {
            $this->approval = true;
        }
        else
        {
            $this->approval = false;
        }
    }

    protected function getSoapResponse($requestContent)
    {
        $xmlResponse = $this->postSoapRequest($requestContent, ApiRequestFields::ORDER_REQUEST);

        $traceCode = $this->getTraceCode();

        $this->trace->info($traceCode, [$xmlResponse->asXml()]);

        $response = $this->parseOrderResponse($xmlResponse);

        return $response;
    }

    protected function checkApprovalCode($gatewayEntity)
    {
        if ($this->approval === false)
        {
            $approvalCode = $this->getActualCodeFromApprovalCode($gatewayEntity->getApprovalCode());

            $gatewayErrorDesc = ErrorCodes::getErrorDesc($approvalCode);

            $errorCode = ErrorCodes::getMappedCode($approvalCode);

            // Cryptic error messages that First Data keeps sending us
            $this->checkSpecialCases($approvalCode, $gatewayEntity, $gatewayErrorDesc);

            throw new Exception\GatewayErrorException($errorCode, $approvalCode, $gatewayErrorDesc);
        }
    }

    protected function checkSpecialCases($approvalCode, $gatewayEntity, $gatewayErrorDesc)
    {
        if (ErrorCodes::isSpecialCase($approvalCode) === true)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_FIRST_DATA_UNEXPECTED,
                [
                    'approval_code' => $gatewayEntity->getApprovalCode(),
                    'error_msg'     => $gatewayErrorDesc,
                ]
            );
        }
    }

    protected function getActualCodeFromApprovalCode($approvalCode)
    {
        // Approval Code is sent as a concatenation of the code ('N:224')
        // and the reason ('Timed out') separated by a ':'.
        // Eg. "N:87:Bad Track Data"
        // Break it using the ':' separator.
        $approvalCodeArray = explode(':', $approvalCode);

        // Retrieve only approval code
        $code = implode(array_slice($approvalCodeArray, 0, 2), ':');

        return $code;
    }

    protected function getAuthorizeFields($authRequest)
    {
        $attributes = [
            Entity::AMOUNT             => $authRequest[ConnectRequestFields::CHARGE_TOTAL] * 100,
            Entity::CURRENCY           => $authRequest[ConnectRequestFields::CURRENCY],
            Entity::GATEWAY_PAYMENT_ID => $authRequest[ConnectRequestFields::ORDER_ID],
        ];

        return $attributes;
    }

    protected function getCallbackFields($callbackBody)
    {
        $attributes = [
            Entity::RECEIVED                => true,
            Entity::APPROVAL_CODE           => $callbackBody[ConnectResponseFields::APPROVAL_CODE],
        ];

        $this->setFieldIfPresent($attributes, Entity::TRANSACTION_RESULT,
                    ConnectResponseFields::STATUS, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TRANSACTION_ID,
                    ConnectResponseFields::IPG_TRANSACTION_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::ENDPOINT_TRANSACTION_ID,
                        ConnectResponseFields::ENDPOINT_TRANSACTION_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TERMINAL_ID,
                        ConnectResponseFields::TERMINAL_ID, $callbackBody);

        if ($attributes[Entity::TRANSACTION_RESULT] === Status::APPROVED)
        {
            $attributes[Entity::STATUS]    = Status::AUTHORIZED;

            $attributes[Entity::AUTH_CODE] = $callbackBody[ConnectResponseFields::PROCESSOR_RESPONSE_CODE];

            $attributes[Entity::TDATE]     = $callbackBody[ConnectResponseFields::TDATE];
        }

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    protected function getPurchaseFields($response, $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        return $attributes;
    }

    protected function getCaptureFields($response, $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setFieldIfPresent($attributes, Entity::AUTH_CODE,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE, $response);

        return $attributes;
    }

    protected function getRefundFields($response, $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setFieldIfPresent($attributes, Entity::AUTH_CODE,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE, $response);

        $this->setRefundId($attributes, $input);

        return $attributes;
    }

    protected function getReverseFields($response, $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setRefundId($attributes, $input);

        return $attributes;
    }

    protected function getCommonResponseFields($response, $input)
    {
        $currencyCode = Currency::ISO_NUMERIC_CODES[$input['currency']];

        $attributes = [
            Entity::RECEIVED      => true,
            Entity::APPROVAL_CODE => $response[ApiResponseFields::APPROVAL_CODE],
            Entity::AMOUNT        => $input['amount'],
            Entity::CURRENCY      => $currencyCode,
            Entity::STATUS        => Status::CAPTURED,
        ];

        $this->setApproval($attributes[Entity::APPROVAL_CODE]);

        $this->setFieldIfPresent($attributes, Entity::TDATE,
                    ApiResponseFields::TDATE, $response);

        $this->setFieldIfPresent($attributes, Entity::TRANSACTION_RESULT,
                    ApiResponseFields::TRANSACTION_RESULT, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_PAYMENT_ID,
                    ApiResponseFields::ORDER_ID, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TRANSACTION_ID,
                    ApiResponseFields::IPG_TRANSACTION_ID, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TERMINAL_ID,
                    ApiResponseFields::TERMINAL_ID, $response);

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    protected function setFieldIfPresent(array & $attributes, string $field,
                                            string $responseField, array $response)
    {
        if (isset($response[$responseField]) === true)
        {
            $attributes[$field] = $response[$responseField];
        }
        else
        {
            $attributes[$field] = null;

            if ($this->approval === false)
            {
                // Random fields are often missing in FirstData responses
                // in cases of auth being declined. Raise warning, but chill.
                $traceLevel = 'warning';
                $message    = $responseField . ' is missing from response.';
            }
            else
            {
                // If a random field is missing in a successful response,
                // then contact FirstData immediately and clear things up.
                $traceLevel = 'error';
                $message    = $responseField . ' is missing from a successful preauth response.';
            }

            $this->trace->$traceLevel(
                TraceCode::GATEWAY_PAYMENT_MISSING_FIELD,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'message'    => $message,
                    'gateway'    => $this->gateway,
                ]
            );
        }
    }

    protected function buildTimeoutResponse($exception)
    {
        $code = ErrorCodes::getTimeoutCode();

        $attributes = [
            ApiResponseFields::APPROVAL_CODE           => $code . ':' . $exception->getMessage(),
            ApiResponseFields::ORDER_ID                => null,
            ApiResponseFields::TDATE                   => null,
            ApiResponseFields::TRANSACTION_RESULT      => null,
            ApiResponseFields::IPG_TRANSACTION_ID      => null,
            ApiResponseFields::TERMINAL_ID             => null,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE => null,
        ];

        return $attributes;
    }

    protected function setRefundId(& $attributes, $input)
    {
        $attributes[Entity::REFUND_ID] = $input['id'];
    }

    protected function setErrorMessageIfNeeded(& $attributes)
    {
        if ($this->approval === false)
        {
            $approvalCode = $this->getActualCodeFromApprovalCode($attributes[Entity::APPROVAL_CODE]);

            $attributes[Entity::ERROR_MESSAGE] = ErrorCodes::getErrorDesc($approvalCode);
            $attributes[Entity::STATUS]        = Status::FAILED;

            if ($approvalCode === ErrorCodes::getTimeoutCode())
            {
                $attributes[Entity::RECEIVED] = false;
            }
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $requestContent = $this->getVerifyRequestContentArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $requestContent);

        $response = $this->postSoapRequest($requestContent, ApiRequestFields::ACTION_REQUEST);

        $ipgApiActionResponse = $this->parseVerifyResponse($input['payment'], $response);

        $verify->setVerifyResponseContent($ipgApiActionResponse);
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;

        $verifyResponse = $verify->verifyResponseContent;

        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if($verifyResponse === null)
        {
            // Verify request failed, as FirstData API returned successfully flag set to false
            // This is probably because the payment request timed out, or some other unknown
            // reason. Either way, this is equivalent to gateway success being false.
            $verify->gatewaySuccess = false;

            $authTdate              = null;

            $authGatewayPaymntId    = null;

            $authGatewayStatus      = Status::FAILED;
        }
        else
        {
            foreach ($verifyResponse->children('a1', true) as $transactionValue)
            {
                // FirstData has several components or services
                // The auth request is sent to the Connect service,
                // so here we're only interested in that one.
                $component = $transactionValue->children('a1', true)->SubmissionComponent->__toString();

                if ($component !== Component::CONNECT)
                {
                    continue;
                }

                $type   = $transactionValue->children('v1', true)->CreditCardTxType->Type->__toString();

                // Verify response contains separate states for all transactions, possibly multiple for refund/capture.
                // We're only interested in the preauth transaction state, so loop to that one, and check status.
                if (in_array($type, [TxnType::AUTH, TxnType::SALE], true) === true)
                {
                    $verifyAuthResponse = $transactionValue;
                }
            }

            // A example of the verify response structure can be found
            // in the verifyResponseWrapper method of SoapWrapper class.
            //
            // As tdate, order_ID and state are structed under different
            // namespaces, their parsing logic is also distinct.
            $authTdate  = $verifyAuthResponse->children('v1', true)->TransactionDetails->TDate->__toString();

            $authGatewayPaymntId = $verifyAuthResponse->children('v1', true)->TransactionDetails->OrderId->__toString();

            $authGatewayStatus  = $verifyAuthResponse->children('a1', true)->TransactionState->__toString();

            $verify->gatewaySuccess = in_array($authGatewayStatus, [Status::AUTHORIZED, Status::CAPTURED], true);
        }

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input['payment']);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->payment = $this->saveVerifyContent($gatewayPayment, $authGatewayPaymntId,
                                                    $authGatewayStatus, $authTdate);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        // Verify Response is actually a SOAP Object, and AuthorizeFailed
        // expects it to be an array. This avoids an error being thrown
        // during failed->auth process.
        $verify->setVerifyResponseContent([]);
    }

    protected function getVerifyApiStatus($gatewayPayment, $payment)
    {
        if (($payment['status'] === 'failed') or
            ($payment['status'] === 'created'))
        {
            $apiStatus = false;

            if ($gatewayPayment['status'] === Status::AUTHORIZED)
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

            if ($gatewayPayment['status'] !== Status::AUTHORIZED)
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

    protected function saveVerifyContent($gatewayPayment, $gatewayPaymentId, $status, $tdate)
    {
        $gatewayPayment->setStatus($status);

        $gatewayPayment->setTdate($tdate);

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function postSoapRequest($content, $requestType)
    {
        $xmlRequest = $this->arrayToXml($content);

        $content = SoapWrapper::defaultWrapper($xmlRequest, $requestType);

        $options = $this->getRequestOptions();

        $request = $this->getStandardRequestArray($content, 'post', $options);

        $this->traceSoapRequest($request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'body'    => $response->body,
                'headers' => $response->headers,
                'code'    => $response->status_code,
            ]
        );

        $xml = simplexml_load_string(trim($response->body));

        return $xml;
    }

    /**
     * Parses response to Capture and Refund requests and converts xml response to an associative array
     * @param  $xml Response received
     * @return $body Associative array containing response
     */
    protected function parseOrderResponse($xml)
    {
        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'raw_xml_response' => $xml->asXml()
            ]
        );

        $soapEnvBody = $xml->children('SOAP-ENV', true)->Body;

        if ($soapEnvBody->Fault->count() > 0)
        {
            $ipgApiOrderResponse = $soapEnvBody->Fault->children()
                                                ->detail->children('ipgapi', true)
                                                ->IPGApiOrderResponse;
        }
        else
        {
            $ipgApiOrderResponse = $soapEnvBody->children('ipgapi', true);
        }

        $xmlBody = $ipgApiOrderResponse->children('ipgapi', true);

        $body = json_decode(json_encode($xmlBody), true);

        return $body;
    }

    /**
     * Parses response to Verify request and converts xml response to an associative array
     * @param  $xml Response received
     * @return $ipgApiActionResponse
     */
    protected function parseVerifyResponse($payment, $xml)
    {
        $ipgApiActionResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $successful = $ipgApiActionResponse->IPGApiActionResponse->successfully->__toString();

        if ($successful === 'false')
        {
            $this->trace->warning(
                TraceCode::PAYMENT_VERIFY_FAILED,
                [
                    'payment_id' => $payment['id'],
                    'message'    => 'Payment verification failed.',
                    'gateway'    => $this->gateway,
                ]
            );

            return null;
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $ipgApiActionResponse->asXML()
            ]
        );

        return $ipgApiActionResponse;
    }

    protected function getTraceCode()
    {
        return self::TRACE_CODE_MAPPING[$this->action];
    }

    protected function getRelativeUrl($component)
    {
        $servicesApiActionList = [
            Action::CAPTURE,
            Action::REFUND,
            Action::VERIFY,
            Action::REVERSE,
        ];

        if (in_array($this->action, $servicesApiActionList))
        {
            $component = Component::API;
        }
        else
        {
            $component = Component::CONNECT;
        }

        $ns = $this->getGatewayNamespace();

        return constant($ns . '\Url::' . $component);
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $options = [])
    {
        $request = parent::getStandardRequestArray($content, $method);

        $request['options'] = $options;

        return $request;
    }

    protected function createGatewayPaymentEntity($content, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->fill($content);

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        $gatewayPayment->setAction($this->action);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    // This is a SHA hash of the following fields :
    // storename + txndatetime + chargetotal + currency + sharedsecret.
    protected function getRequestHash($txnDateTime, $chargeTotal, $currencyCode)
    {
        $storeId = $this->getStoreId();

        $sharedSecret = $this->getSecret();

        $stringToHash = $storeId . $txnDateTime . $chargeTotal . $currencyCode . $sharedSecret;

        $hash = hash(HashAlgo::SHA1, bin2hex($stringToHash));

        return $hash;
    }

    protected function getStringToHash($content, $glue = '')
    {
        $approvalCode   = $content[ConnectResponseFields::APPROVAL_CODE] ?? null;

        $txnDateTime    = $content[ConnectResponseFields::TXN_DATE_TIME];

        $chargeTotal    = $content[ConnectResponseFields::CHARGE_TOTAL];

        $currencyCode   = $content[ConnectResponseFields::CURRENCY];

        $storeId = $this->getStoreId();

        $sharedSecret = $this->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;

        return $stringToHash;
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA1, bin2hex($str));
    }

    protected function getPreAuthRequestContentArray($input)
    {
        $createdAt = $input['payment'][Payment\Entity::CREATED_AT];

        $dateTime = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata');

        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $method = $input['card'][Card\Entity::NETWORK_CODE];

        $requestHash = $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode);

        $txnType = TxnType::AUTH;

        if (Payment\Gateway::supportsAuthAndCapture($this->gateway, $method) === false)
        {
            $txnType = TxnType::SALE;
        }

        $content = [
            ConnectRequestFields::TIME_ZONE                 => 'Asia/Kolkata',
            ConnectRequestFields::TXN_DATE_TIME             => $txnDateTime,
            ConnectRequestFields::HASH_ALGORITHM            => strtoupper(HashAlgo::SHA1),
            ConnectRequestFields::HASH                      => $requestHash,
            ConnectRequestFields::STORE_NAME                => $this->getStoreId(),
            ConnectRequestFields::MODE                      => PaymentMode::PAYONLY,
            ConnectRequestFields::CHARGE_TOTAL              => $chargeTotal,
            ConnectRequestFields::CURRENCY                  => $currencyCode,
            ConnectRequestFields::ORDER_ID                  => $input['payment'][Payment\Entity::ID],
            ConnectRequestFields::INVOICE_NUMBER            => $input['payment'][Payment\Entity::ID],
            // Card Entity type field is not reliable, and not mandatory
            // ConnectRequestFields::CARD_FUNCTION             => $input['card'][Card\Entity::TYPE],
            ConnectRequestFields::COMMENTS                  => '',
            ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => $this->getDynamicMerchantName($input['merchant']),
            ConnectRequestFields::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
            ConnectRequestFields::CARD_NUMBER               => $input['card'][Card\Entity::NUMBER],
            ConnectRequestFields::NAME                      => $input['card'][Card\Entity::NAME],
            ConnectRequestFields::EXP_MONTH                 => $input['card'][Card\Entity::EXPIRY_MONTH],
            ConnectRequestFields::EXP_YEAR                  => $input['card'][Card\Entity::EXPIRY_YEAR],
            ConnectRequestFields::CVV                       => $input['card'][Card\Entity::CVV],
            ConnectRequestFields::RESPONSE_SUCCESS_URL      => $input['callbackUrl'],
            ConnectRequestFields::RESPONSE_FAIL_URL         => $input['callbackUrl'],
            ConnectRequestFields::TXN_TYPE                  => $txnType,
            ConnectRequestFields::PAYMENT_METHOD            => PaymentMethod::METHOD_MAP[$method],
        ];

        if ($this->isFirstRecurringPayment($input) === true)
        {
            $content[ConnectRequestFields::TOKEN] = $input['token']->getId();
        }

        return $content;
    }

    protected function isFirstRecurringPayment($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['terminal']->is3DSRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function isSecondRecurringPayment($input)
    {
        if (($input['payment']['recurring'] === true) and
            (isset($input['token']) === true) and
            ($input['token'] !== null) and
            ($input['token']->isRecurring() === true) and
            ($input['terminal']->isNon3DSRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function isCaptureNecessary($input)
    {
        $captureEntity = $this->repo->findByPaymentIdAndAction(
                                            $input['payment'][Payment\Entity::ID],
                                            Base\Action::CAPTURE);

        if ($captureEntity !== null)
        {
            $this->trace->info(
                TraceCode::PAYMENT_ALREADY_CAPTURED,
                $input['payment']);

            return false;
        }

        $purchaseEntity = $this->repo->findByPaymentIdAndAction(
                                            $input['payment'][Payment\Entity::ID],
                                            Base\Action::PURCHASE);

        if ($purchaseEntity !== null)
        {
            // First gatewayPayment entity was a purchase transaction,
            // so capture is not needed.
            // This happens in case of second recurring payment requests.
            return false;
        }
    }

    protected function getRequestOptions()
    {
        $options['auth'] = $this->getCredentials();

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlSslOpts']);

        $options['hooks'] = $hooks;

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLCERTTYPE, strtoupper(self::CERTIFICATE_FORMAT_P12));

        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $this->getClientCertificatePassword());

        curl_setopt($curl, CURLOPT_CAINFO, $this->getServerCertificate());

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: text/xml"]);
    }

    protected function getVerifyRequestContentArray($input)
    {
        $request[ApiRequestFields::A1_ACTION]
                    [ApiRequestFields::A1_INQUIRY_ORDER] = [
            ApiRequestFields::A1_ORDER_ID => $input['payment']['id'],
            ApiRequestFields::A1_STORE_ID => $this->getStoreId(),
        ];

        return $request;
    }

    protected function getPurchaseRequestArray($input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::SALE;

        $this->setPaymentRequestArray($body, $input, TxnType::SALE);

        $body[ApiRequestFields::V1_RECURRING_TYPE] = Codes::STANDING_INSTRUCTION;

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $input['payment']['id'];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getCaptureRequestArray($input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::CAPTURE;

        $this->setPaymentRequestArray($body, $input, TxnType::CAPTURE);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $input['payment']['id'];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getRefundRequestArray($input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::REFUND;

        $this->setPaymentRequestArray($body, $input, TxnType::REFUND);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $input['payment']['id'];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getReverseRequestArray($input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::REVERSE;

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                            $input['payment'][Payment\Entity::ID],
                                            Base\Action::AUTHORIZE);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID => $input['payment']['id'],
            ApiRequestFields::V1_TDATE    => $gatewayPayment[Entity::TDATE],
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function setPaymentRequestArray(& $body, $input, $txnType)
    {
        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $amountEntity = TxnType::$amountEntity[$txnType];

        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CHARGE_TOTAL] = $input[$amountEntity]['amount'] / 100;

        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY] = $currencyCode;

        if (($this->isSecondRecurringPayment($input) === true) and
            ($txnType === TxnType::SALE))
        {
            $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_HOSTED_DATA_ID] = $input['token']->getId();
        }
    }

    protected function arrayToXml($array, $wrap = null)
    {
        // set initial value for XML string
        $xml = '';
        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                $xml .= $this->arrayToXml($value, $key);
            }
            else
            {
                $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
            }
        }

        // wrap XML with $wrap TAG
        if ($wrap !== null)
        {
            $xml = "<$wrap>".$xml."</$wrap>";
        }

        return $xml;
    }

    protected function traceSoapRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            ['gateway_soap_request' => $request]);
    }

    protected function traceGatewayPaymentRequest(
        $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->scrubCardInfo($request['content']);

        parent::traceGatewayPaymentRequest($request, $input, $traceCode);
    }

    protected function traceGatewayCallback($gatewayCallback)
    {
        $this->scrubCardInfo($gatewayCallback);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $gatewayCallback
        );
    }

    protected function scrubCardInfo(& $content)
    {
        $scrubFields = [
            ConnectRequestFields::CARD_NUMBER,
            ConnectRequestFields::CVV,
            ConnectRequestFields::EXP_MONTH,
            ConnectRequestFields::EXP_YEAR
        ];

        foreach ($scrubFields as $scrubField)
        {
            unset($content[$scrubField]);
        }
    }

    // FirstData creds
    //
    // Store ID              => Terminal attr (GATEWAY_MERCHANT_ID)
    // Shared Secret         => env(FIRST_DATA_LIVE_HASH_SECRET)
    // User ID               => env(FIRST_DATA_LIVE_USER_ID)
    // Password              => env(FIRST_DATA_LIVE_PASSWORD)
    // Client Cert           => env(FIRST_DATA_LIVE_CLIENT_CERTIFICATE)
    // Client Cert Password  => env(FIRST_DATA_LIVE_CLIENT_CERTIFICATE_PASSWORD)

    public function getStoreId()
    {
        $storeId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $storeId = $this->config['test_store_id'];
        }

        return $storeId;
    }

    protected function getLiveSecret()
    {
        $liveSecret = $this->input['terminal']['gateway_secure_secret'];

        if ($this->isChildStoreId() === true)
        {
            $liveSecret = $this->config['live_hash_secret'];
        }

        return $liveSecret;
    }

    protected function getCredentials()
    {
        $username = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
        $password = $this->terminal[Terminal\Entity::GATEWAY_ACCESS_CODE];

        if ($this->isChildStoreId() === true)
        {
            $username = $this->config['live_user_id'];
            $password = $this->config['live_password'];
        }

        if ($this->mode === Mode::TEST)
        {
            $username = $this->config['test_user_id'];
            $password = $this->config['test_password'];
        }

        return [$username, $password];
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function getServerCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        return $gatewayCertPath . '/' . $this->config['server_certificate'];
    }

    public function getClientCertificateName()
    {
        $certName = $this->getStoreId() . '.' . self::CERTIFICATE_FORMAT_P12;

        if ($this->isChildStoreId() === true)
        {
            $certName = $this->config['client_certificate'];
        }

        return $certName;
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $clientCertFile = fopen($clientCertPath, 'w');

            $encodedCert = $this->terminal[Terminal\Entity::GATEWAY_CLIENT_CERTIFICATE];

            if ($this->isChildStoreId() === true)
            {
                $encodedCert = $this->config['live_client_certificate'];
            }

            if ($this->mode === Mode::TEST)
            {
                $encodedCert = $this->config['test_client_certificate'];
            }

            $key = base64_decode($encodedCert);

            fwrite($clientCertFile, $key);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getClientCertificatePassword()
    {
        $password = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->isChildStoreId() === true)
        {
            $password = $this->config['live_client_certificate_password'];
        }

        if ($this->mode === Mode::TEST)
        {
            $password = $this->config['test_client_certificate_password'];
        }

        return $password;
    }

    protected function isChildStoreId()
    {
        // Older creds needed a separate value to access
        // FirstData API and web portal.
        // New FirstData creds are child ids, and do not
        // have a merchantId2 value of their own.

        return ($this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2] === null);
    }

    protected function getChance()
    {
        if (self::$testChance === null)
        {
            return 99;

            //TODO : Fix this, Disable optimization
            /*
            return rand(0, 99);
             */
        }

        return self::$testChance;
    }

    public static function setTestChance($chance = null)
    {
        self::$testChance = $chance;
    }

    protected function traceS2sCallResponse($response)
    {
        $patternReplacementPairs = [
            '/(\<cardnum&gt;(\d{6})(.*)<\/cardnum)/' => '<cardnum&gt;${2}...****<\/cardnum',
            '/(\<cvv2&gt;(.*)<\/cvv2)/' => '(\<cvv2&gt;***<\/cvv2)'
        ];

        $responseBody = preg_replace(
            array_keys($patternReplacementPairs),
            array_values($patternReplacementPairs),
            $response->body
        );

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'payment_id' => $this->input['payment']['id'],
                'response' => $responseBody
            ]);
    }

    protected function validateParesStatus(array $input)
    {
        $PaRes = $input['gateway']['PaRes'];

        try
        {
            $PaRes = base64_decode($PaRes);
            $PaRes = gzinflate(substr($PaRes, 2));

            $PaResObject = simplexml_load_string($PaRes);
            $PaRes = json_decode(json_encode($PaResObject), true);

            if (isset($PaRes['Message']['PARes']['TX']['status']) === true)
            {
                $this->trace->info(TraceCode::GATEWAY_CALLBACK_PARES,
                    [
                        'gateway' => $this->gateway,
                        'PaResStatus' => $PaRes['Message']['PARes']['TX']['status']
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }
}
