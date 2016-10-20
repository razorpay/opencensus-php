<?php

namespace RZP\Gateway\FirstData;

use RZP\Constants;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Requests;
use Requests_Hooks;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    const CERTIFICATE_DIRECTORY_NAME        = 'cert_dir_name';
    const CERTIFICATE_FORMAT_P12            = 'p12';

    const PROCESSING                        = 'PROCESSING';
    const SERVICES                          = 'SERVICES';

    const CHECKSUM_ATTRIBUTE = ConnectResponseFields::RESPONSE_HASH;

    protected $gateway = Constants\Entity::FIRST_DATA;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $requestContent = $this->getPreAuthRequestContentArray($input);

        $authorizeFields = $this->getAuthorizeFields($requestContent);

        $authorizeEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

        $request = $this->getStandardRequestArray($requestContent);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->traceGatewayCallback($input['gateway']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['gateway'][ConnectResponseFields::ORDER_ID],
            Base\Action::AUTHORIZE);

        $this->verifySecureHash($input['gateway']);

        $attributes = $this->getCallbackFields($input['gateway']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkApprovalCode($gatewayPayment);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                                $input['payment'][Payment\Entity::ID],
                                                Base\Action::CAPTURE);

        if ($gatewayPayment !== null)
        {
            $this->trace->info(
                TraceCode::PAYMENT_ALREADY_CAPTURED,
                $input['payment']);

            return;
        }

        $requestContent = $this->getRequestArray($input, TxnType::CAPTURE);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_CAPTURE_RESPONSE,
            [
                'capture_response' => $response
            ]);

        $captureFields = $this->getCaptureOrRefundFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);

        $this->checkApprovalCode($captureEntity);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $requestContent = $this->getRequestArray($input, TxnType::REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'refund_response' => $response
            ]);

        $refundFields = $this->getCaptureOrRefundFields($response, $input['refund']);

        $refundEntity = $this->createGatewayPaymentEntity($refundFields, $input);

        $this->checkApprovalCode($refundEntity);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getSoapResponse($requestContent)
    {
        try
        {
            $xmlResponse = $this->postSoapRequest($requestContent, ApiRequestFields::ORDER_REQUEST);

            $traceCode = $this->getTraceCode();

            $this->trace->info($traceCode, [$xmlResponse->asXml()]);

            $response = $this->parseOrderResponse($xmlResponse);
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            // If a timeout occurs, don't throw an exception just yet.
            // We build a mock response, that allows the gateway entity
            // to be created, then throw the same exception
            // in checkApprovalCode
            $response = $this->buildTimeoutResponse($e);
        }

        return $response;
    }

    protected function checkApprovalCode($gatewayEntity)
    {
        // Request has failed if the first character
        // of the approval code string isn't 'Y'
        if ($gatewayEntity->getApprovalCode()[0] !== 'Y')
        {
            $approvalCode = $this->getActualCodeFromApprovalCode($gatewayEntity->getApprovalCode());

            $gatewayErrorDesc = ErrorCodes::getErrorDesc($approvalCode);

            $errorCode = ErrorCodes::getMappedCode($approvalCode);

            if ($gatewayEntity->getReceived() === false)
            {
                throw new Exception\GatewayTimeoutException($gatewayEntity->getApprovalCode());
            }

            throw new Exception\GatewayErrorException($errorCode, $approvalCode, $gatewayErrorDesc);
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
            Entity::GATEWAY_PAYMENT_ID => $authRequest[ConnectRequestFields::ORDER_ID],
        ];

        return $attributes;
    }

    protected function getCallbackFields($callbackBody)
    {
        $attributes = [
            Entity::RECEIVED                => true,
            Entity::APPROVAL_CODE           => $callbackBody[ConnectResponseFields::APPROVAL_CODE],
            Entity::TDATE                   => $callbackBody[ConnectResponseFields::TDATE],
            Entity::TRANSACTION_RESULT      => $callbackBody[ConnectResponseFields::STATUS],
            Entity::GATEWAY_TRANSACTION_ID  => $callbackBody[ConnectResponseFields::IPG_TRANSACTION_ID],
            Entity::ENDPOINT_TRANSACTION_ID => $callbackBody[ConnectResponseFields::ENDPOINT_TRANSACTION_ID],
            Entity::GATEWAY_TERMINAL_ID     => $callbackBody[ConnectResponseFields::TERMINAL_ID],
            Entity::AUTH_CODE               => $callbackBody[ConnectResponseFields::PROCESSOR_RESPONSE_CODE],
        ];

        if ($attributes[Entity::TRANSACTION_RESULT] === Status::APPROVED)
        {
            $attributes[Entity::STATUS] = Status::AUTHORIZED;
        }

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    protected function getCaptureOrRefundFields($response, $input)
    {
        $attributes = [
            Entity::RECEIVED               => true,
            Entity::APPROVAL_CODE          => $response[ApiResponseFields::APPROVAL_CODE],
            Entity::AMOUNT                 => $input['amount'],
            Entity::TDATE                  => $response[ApiResponseFields::TDATE],
            Entity::STATUS                 => Status::CAPTURED,
            Entity::TRANSACTION_RESULT     => $response[ApiResponseFields::TRANSACTION_RESULT],
            Entity::GATEWAY_PAYMENT_ID     => $response[ApiResponseFields::ORDER_ID],
            Entity::GATEWAY_TRANSACTION_ID => $response[ApiResponseFields::IPG_TRANSACTION_ID],
            Entity::GATEWAY_TERMINAL_ID    => $response[ApiResponseFields::TERMINAL_ID],
            Entity::AUTH_CODE              => $response[ApiResponseFields::PROCESSOR_APPROVAL_CODE],
        ];

        $this->setRefundIdIfNeeded($attributes, $input);

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
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

    protected function setRefundIdIfNeeded(& $attributes, $input)
    {
        if ($this->action == Base\Action::REFUND)
        {
            $attributes[Entity::REFUND_ID] = $input['id'];
        }
    }

    protected function setErrorMessageIfNeeded(& $attributes)
    {
        $approvalCode = $attributes[Entity::APPROVAL_CODE];

        if ($approvalCode[0] !== 'Y')
        {
            $approvalCode = $this->getActualCodeFromApprovalCode($approvalCode);

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

        $ipgApiActionResponse = $this->parseVerifyResponse($response);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway_verify_response' => $ipgApiActionResponse->asXML()
            ]);

        $verify->setVerifyResponseContent($ipgApiActionResponse);
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;

        $verifyResponse = $verify->verifyResponseContent;

        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        foreach ($verifyResponse->children('a1', true) as $transactionValue)
        {
            $type   = $transactionValue->children('v1', true)->CreditCardTxType->Type->__toString();

            // Verify response contains separate states for all transactions, possibly multiple for refund/capture.
            // We're only interested in the preauth transaction state, so loop to that one, and check status.
            if ($type === TxnType::AUTH)
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

        $authGatewayPaymentId = $verifyAuthResponse->children('v1', true)->TransactionDetails->OrderId->__toString();

        $authGatewayStatus  = $verifyAuthResponse->children('a1', true)->TransactionState->__toString();

        $verify->gatewaySuccess = ($authGatewayStatus === Status::AUTHORIZED);

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input['payment']);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->payment = $this->saveVerifyContent($gatewayPayment, $authGatewayPaymentId, $authGatewayStatus, $authTdate);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;
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
            [$response->body]);

        $xml = simplexml_load_string($response->body);

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
            ]);

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
    protected function parseVerifyResponse($xml)
    {
        $ipgApiActionResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $successful = $ipgApiActionResponse->IPGApiActionResponse->successfully->__toString();

        if ($successful === 'false')
        {
            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                        null,
                        'Verification failed');
        }

        return $ipgApiActionResponse;
    }

    protected function getTraceCode()
    {
        if ($this->action === Base\Action::CAPTURE)
        {
            return TraceCode::GATEWAY_CAPTURE_RESPONSE;
        }
        else
        {
            return TraceCode::GATEWAY_REFUND_RESPONSE;
        }
    }

    protected function getRelativeUrl($type)
    {
        $servicesApiActionList = [
            Action::CAPTURE,
            Action::REFUND,
            Action::VERIFY
        ];

        if (in_array($this->action, $servicesApiActionList))
        {
            $type = self::SERVICES;
        }
        else
        {
            $type = self::PROCESSING;
        }

        $ns = $this->getGatewayNamespace();

        return constant($ns . '\Url::' . $type);
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
        $approvalCode   = $content[ConnectResponseFields::APPROVAL_CODE];

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
            ConnectRequestFields::CARD_FUNCTION             => $input['card'][Card\Entity::TYPE],
            ConnectRequestFields::COMMENTS                  => '',
            ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => 'Razorpay Payments',
            ConnectRequestFields::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
            ConnectRequestFields::CARD_NUMBER               => $input['card'][Card\Entity::NUMBER],
            ConnectRequestFields::NAME                      => $input['card'][Card\Entity::NAME],
            ConnectRequestFields::EXP_MONTH                 => $input['card'][Card\Entity::EXPIRY_MONTH],
            ConnectRequestFields::EXP_YEAR                  => $input['card'][Card\Entity::EXPIRY_YEAR],
            ConnectRequestFields::CVV                       => $input['card'][Card\Entity::CVV],
            ConnectRequestFields::RESPONSE_SUCCESS_URL      => $input['callbackUrl'],
            ConnectRequestFields::RESPONSE_FAIL_URL         => $input['callbackUrl'],
            ConnectRequestFields::TXN_TYPE                  => TxnType::AUTH,
            ConnectRequestFields::PAYMENT_METHOD            => PaymentMethod::METHOD_MAP[$method],
        ];

        return $content;
    }

    protected function getRequestOptions()
    {
        $auth = $this->getCredentials();

        $options['auth'] = [$auth['username'], $auth['password']];

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
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                            $input['payment'][Payment\Entity::ID],
                                            Base\Action::AUTHORIZE);

        $request[ApiRequestFields::A1_ACTION]
                    [ApiRequestFields::A1_INQUIRY_ORDER]
                        [ApiRequestFields::A1_ORDER_ID] = $gatewayPayment[Entity::GATEWAY_PAYMENT_ID];

        return $request;
    }

    protected function getRequestArray($input, $txnType)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                            $input['payment'][Payment\Entity::ID],
                                            Base\Action::AUTHORIZE);

        $currency     = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];
        $amountEntity = TxnType::$amountEntity[$txnType];

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE]     = $txnType;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CHARGE_TOTAL]         = $input[$amountEntity]['amount'] / 100;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY]             = $currencyCode;
        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $gatewayPayment[Entity::GATEWAY_PAYMENT_ID];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function arrayToXml($array, $wrap=null)
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

    protected function traceGatewayPaymentRequest($request, $input)
    {
        $this->scrubCardInfo($request['content']);

        parent::traceGatewayPaymentRequest($request, $input);
    }

    protected function traceGatewayCallback($gatewayCallback)
    {
        $this->scrubCardInfo($gatewayCallback);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $gatewayCallback);
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

    // FirstData terminal attributes to Terminal Entity mapping
    //
    // Store ID              => GATEWAY_MERCHANT_ID
    // Shared Secret         => GATEWAY_SECURE_SECRET
    // User ID               => GATEWAY_MERCHANT_ID
    // Password              => GATEWAY_ACCESS_CODE
    // Client Cert           => GATEWAY_CLIENT_CERTIFICATE (base64 encoded)
    // Client Cert Password  => GATEWAY_TERMINAL_PASSWORD

    protected function getStoreId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_store_id'];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
    }

    protected function getSharedSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_hash_secret'];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET];
    }

    protected function getCredentials()
    {
        if ($this->mode === Mode::TEST)
        {
            $auth = [
                'username' => $this->config['test_user_id'],
                'password' => $this->config['test_password']
            ];

            return $auth;
        }

        $auth = [
            'username' => $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2],
            'password' => $this->terminal[Terminal\Entity::GATEWAY_ACCESS_CODE]
        ];

        return $auth;
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

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' . $this->getStoreId() . '.' . self::CERTIFICATE_FORMAT_P12;

        if (file_exists($clientCertPath) === false)
        {
            $clientCertFile = fopen($clientCertPath, 'w');

            if ($this->mode === Mode::TEST)
            {
                $encodedCert = $this->config['test_client_certificate'];
            }
            else
            {
                $encodedCert = $this->terminal[Terminal\Entity::GATEWAY_CLIENT_CERTIFICATE];
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
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_client_certificate_password'];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];
    }
}
