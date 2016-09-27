<?php

namespace RZP\Gateway\FirstData;

use Requests;
use Requests_Hooks;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\Trace;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\Action;
use RZP\Constants\HashAlgo;
use RZP\Constants;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{
    const TEST_STORE_ID                     = 'test_store_id';
    const TEST_HASH_SECRET                  = 'test_hash_secret';

    const SERVER_CERTIFICATE_PATH           = 'server_certificate_path';
    const CLIENT_CERTIFICATE_PATH           = 'client_certificate_path';
    const CLIENT_CERTIFICATE_KEY_PATH       = 'client_certificate_key_path';

    const PROCESSING                        = 'PROCESSING';
    const SERVICES                          = 'SERVICES';

    protected $gateway = Constants\Entity::FIRST_DATA;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPreAuthRequestContentArray($input);

        $authorizeFields = $this->getAuthorizeFields($content);

        $authorizeEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $gatewayPayment = $this->repo
                            ->findByPaymentIdAndActionOrFail($input['gateway'][ConnectResponseFields::ORDER_ID], Base\Action::AUTHORIZE);

        $this->verifyResponseHash($input['gateway']);

        $this->verifyPaymentCallbackResponse($input, $gatewayPayment);

        $attributes = $this->getCallbackFields($input['gateway']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->retrieveCapturedByPaymentId($input['payment'][Payment\Entity::ID]);

        if ($gatewayPayment !== null)
        {
            $this->trace->info(TraceCode::PAYMENT_ALREADY_CAPTURED, $input['payment']);
            return;
        }

        $content = $this->getRequestArray($input, TxnType::CAPTURE);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $content);

        $xmlResponse = $this->postSoapRequest($content, ApiRequestFields::ORDER_REQUEST);

        $response = $this->parseOrderResponse($xmlResponse);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, [$response]);

        $captureFields = $this->getCaptureOrRefundFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);

        $this->checkApprovalCode($captureEntity);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRequestArray($input, TxnType::REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $content);

        $xmlResponse = $this->postSoapRequest($content, ApiRequestFields::ORDER_REQUEST);

        $response = $this->parseOrderResponse($xmlResponse);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [$response]);

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

    protected function checkApprovalCode($gatewayEntity)
    {
        if ($gatewayEntity->getApprovalCode()[0] !== 'Y')
        {
            // Approval Code is sent as a concatenation of the code ('N:224')
            // and the reason ('Timed out') separated by a ':'.
            // Break it using the ':' separator.
            $approvalCodeArray = explode(':',$gatewayEntity->getApprovalCode());
            // Retrieve actual approval code
            $approvalCode = implode(array_slice($approvalCodeArray, 0, 2),':');

            $desc = ErrorCodes::getErrorDesc($approvalCode);

            $errorCode = ErrorCodes::getMappedCode($approvalCode);

            throw new Exception\GatewayErrorException($errorCode, $approvalCode, $desc);
        }
    }

    protected function getAuthorizeFields($authRequest)
    {
        $attributes = array(
            Entity::AMOUNT              => $authRequest[ConnectRequestFields::CHARGE_TOTAL]*100,
            Entity::GATEWAY_PAYMENT_ID  => $authRequest[ConnectRequestFields::ORDER_ID],
        );

        return $attributes;
    }

    protected function getCallbackFields($callbackBody)
    {
        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::APPROVAL_CODE               => $callbackBody[ConnectResponseFields::APPROVAL_CODE],
            Entity::TDATE                       => $callbackBody[ConnectResponseFields::TDATE],
            Entity::TRANSACTION_RESULT          => $callbackBody[ConnectResponseFields::STATUS],
        );

        if ($attributes[Entity::TRANSACTION_RESULT] === Status::APPROVED)
        {
            $attributes[Entity::STATUS] = Status::AUTHORIZED;
        }

        return $attributes;
    }

    protected function getCaptureOrRefundFields($response, $input)
    {
        $approvalCode = $response[ApiResponseFields::APPROVAL_CODE];

        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::APPROVAL_CODE               => $approvalCode,
            Entity::AMOUNT                      => $input['amount'],
            Entity::TDATE                       => $response[ApiResponseFields::TDATE],
            Entity::STATUS                      => Status::CAPTURED,
            Entity::TRANSACTION_RESULT          => $response[ApiResponseFields::TRANSACTION_RESULT],
            Entity::GATEWAY_PAYMENT_ID          => $response[ApiResponseFields::ORDER_ID],
        );

        if ($approvalCode[0] !== 'Y')
        {
            // Approval Code is sent as a concatenation of the code ('N:224')
            // and the reason ('Timed out') separated by a ':'.
            // Break it using the ':' separator.
            $approvalCodeArray = explode(':',$approvalCode);
            // Retrieve actual approval code
            $approvalCode = implode(array_slice($approvalCodeArray, 0, 2),':');

            $desc = ErrorCodes::getErrorDesc($approvalCode);

            $attributes[Entity::ERROR_MESSAGE] = $desc;
        }

        return $attributes;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $content = $this->getVerifyRequestContentArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $content);

        $response = $this->postSoapRequest($content, ApiRequestFields::ACTION_REQUEST);

        $ipgApiActionResponse = $this->parseVerifyResponse($response);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, [$ipgApiActionResponse->asXML()]);

        $content = $this->getPaymentVerifyResponse($ipgApiActionResponse);

        $verify->verifyResponseContent = $ipgApiActionResponse;

        return $content;
    }

    protected function getPaymentVerifyResponse($verifyResponse)
    {
        return array(
            Entity::TDATE       => $verifyResponse->children('a1', true)->children('ipgapi', true)->IPGApiOrderResponse->TDate->__toString(),
            Entity::STATUS      => $verifyResponse->children('a1', true)->TransactionValues->TransactionState->__tostring(),
        );
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;
        $gatewayPayment = $verify->payment;
        $ipgApiActionResponse = $verify->verifyResponseContent;

        $orderId = $gatewayPayment->getGatewayPaymentId();

        $verify->status = VerifyResult::STATUS_MATCH;

        foreach ( $ipgApiActionResponse->children('a1', true) as $transactionValue )
        {
            $tdate  = $transactionValue->children('v1', true)->TransactionDetails->TDate->__toString();
            $type   = $transactionValue->children('v1', true)->CreditCardTxType->Type->__toString();
            $state  = $transactionValue->children('a1', true)->TransactionState->__toString();

            if ($type === TxnType::AUTH)
            {
                $gatewayPayment = $this->repo->findByPaymentIdAndAction($orderId, Base\Action::AUTHORIZE);

                if (($gatewayPayment === null) or
                    ($gatewayPayment->getStatus() !== $state))
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->payment = $this->saveVerifyContent($orderId, $state, $tdate);
                }
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        return $verify->status;
    }

    protected function saveVerifyContent($orderId, $status, $tdate)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction($orderId, Base\Action::AUTHORIZE);

        $gatewayPayment->setStatus($status);
        $gatewayPayment->setTdate($tdate);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function postSoapRequest($content, $requestType)
    {
        $xmlRequest = $this->arrayToXml($content);

        $content = SoapWrapper::defaultWrapper($xmlRequest, $requestType);

        $options = $this->getRequestOptions();

        $request = $this->getStandardRequestArray($content, $options);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [$response->body]);

        $xml   = simplexml_load_string($response->body);

        return $xml;
    }

    /**
     * Parses response to Capture and Refund requests and converts xml response to an associative array
     * @param  $xml Response received
     * @return $body Associative array containing response
     */
    protected function parseOrderResponse($xml)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, ['raw_xml_response' => $xml->asXml()]);

        $soapEnvBody = $xml->children('SOAP-ENV', true)->Body;

        if ($soapEnvBody->Fault->count() > 0)
        {
            $ipgApiOrderResponse = $soapEnvBody->Fault->children()
                                                ->detail->children('ipgapi', true)
                                                ->IPGApiOrderResponse;

            $gatewayCode = $ipgApiOrderResponse->ApprovalCode->__toString();

            $desc = $ipgApiOrderResponse->ErrorMessage->__toString();

            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED, $gatewayCode, $desc);
        }

        $ipgApiOrderResponse = $soapEnvBody->children('ipgapi', true);

        $xmlBody = $ipgApiOrderResponse->children('ipgapi', true);

        $body = json_decode(json_encode($xmlBody), true);

        return $body;
    }

    /**
     * Parses response to Verify request and converts xml response to an associative array
     * @param  $xml Response received
     * @return $body Associative array containing response
     */
    protected function parseVerifyResponse($xml)
    {
        $ipgApiActionResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $successful = $ipgApiActionResponse->IPGApiActionResponse->successfully->__toString();

        if ($successful === 'false')
        {
            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }

        return $ipgApiActionResponse;
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

        return constant($ns.'\Url::'.$type);
    }

    protected function getStandardRequestArray($content = [], $options = [], $method = 'post')
    {
        $request = parent::getStandardRequestArray($content, $method);

        $request['options'] = $options;

        return $request;
    }

    protected function getPreAuthRequestContentArray($input)
    {
        $content = $this->getRequestContentArray($input);

        $content[ConnectRequestFields::TXN_TYPE] = TxnType::AUTH;

        $method = $input['card'][Card\Entity::NETWORK_CODE];

        $content[ConnectRequestFields::PAYMENT_METHOD] = Codes::PAYMENT_METHODS[$method];

        $this->setCardDetails($content, $input['card']);
        $this->setCallbackUrls($content, $input);

        return $content;
    }

    protected function setCardDetails(& $content, $cardInput)
    {
        $content[ConnectRequestFields::CARD_NUMBER] = $cardInput[Card\Entity::NUMBER];
        $content[ConnectRequestFields::NAME]        = $cardInput[Card\Entity::NAME];
        $content[ConnectRequestFields::EXP_MONTH]   = $cardInput[Card\Entity::EXPIRY_MONTH];
        $content[ConnectRequestFields::EXP_YEAR]    = $cardInput[Card\Entity::EXPIRY_YEAR];
        $content[ConnectRequestFields::CVV]         = $cardInput[Card\Entity::CVV];
    }

    protected function setCallbackUrls(& $content, $input)
    {
        $content[ConnectRequestFields::RESPONSE_SUCCESS_URL] = $input['callbackUrl'];
        $content[ConnectRequestFields::RESPONSE_FAIL_URL]    = $input['callbackUrl'];
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

    /**
     * Get SHA1 hash using the given fields
     * sharedsecret + approvalcode + chargetotal + currency + txndatetime + storename.
     * @param  $approvalCode
     * @param  $chargeTotal
     * @param  $currencyCode
     * @param  $txnDateTime
     * @return $hash
     */
    protected function getExpectedResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime)
    {
        $storeId = $this->getStoreId();
        $sharedSecret = $this->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;

        $hash = hash(HashAlgo::SHA1, bin2hex($stringToHash));

        return $hash;
    }

    protected function getRequestContentArray($input)
    {
        $createdAt = $input['payment'][Payment\Entity::CREATED_AT];
        $dateTime = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata');
        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Codes::ISO_NUMERIC_CURRENCY[$currency];

        $content = array(
            ConnectRequestFields::TIME_ZONE                 => 'Asia/Kolkata',
            ConnectRequestFields::TXN_DATE_TIME             => $txnDateTime,
            ConnectRequestFields::HASH_ALGORITHM            => strtoupper(HashAlgo::SHA1),
            ConnectRequestFields::HASH                      => $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode),
            ConnectRequestFields::STORE_NAME                => $this->getStoreId(),
            ConnectRequestFields::MODE                      => Codes::PAYMENT_MODE_PAYONLY,
            ConnectRequestFields::CHARGE_TOTAL              => $chargeTotal,
            ConnectRequestFields::CURRENCY                  => $currencyCode,
            ConnectRequestFields::ORDER_ID                  => $input['payment'][Payment\Entity::ID],
            ConnectRequestFields::INVOICE_NUMBER            => $input['payment'][Payment\Entity::ID],
            ConnectRequestFields::CARD_FUNCTION             => $input['card'][Card\Entity::TYPE],
            ConnectRequestFields::COMMENTS                  => '',
            ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => 'Razorpay Payments',
            ConnectRequestFields::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
        );

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
        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
        curl_setopt($curl, CURLOPT_CAINFO, $this->getServerCertificate());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    }

    protected function getVerifyRequestContentArray($input)
    {
        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $request[ApiRequestFields::A1_ACTION][ApiRequestFields::A1_INQUIRY_ORDER][ApiRequestFields::A1_ORDER_ID] = $gatewayPayment[Entity::GATEWAY_PAYMENT_ID];

        return $request;
    }

    protected function getRequestArray($input, $txnType)
    {
        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $currency     = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Codes::ISO_NUMERIC_CURRENCY[$currency];
        $amountEntity = TxnType::$amountEntity[$txnType];

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE]      = $txnType;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CHARGE_TOTAL]          = $input[$amountEntity][Payment\Entity::AMOUNT]/100;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY]           = $currencyCode;
        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $gatewayPayment[Entity::GATEWAY_PAYMENT_ID];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    private function arrayToXml($array, $wrap=null)
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

    protected function verifyPaymentCallbackResponse($input, $gatewayPayment)
    {
        // Approval Code is sent as a concatenation of the code ('N:224')
        // and the reason ('Timed out') separated by a ':'.
        // Break it using the ':' separator.
        $approvalCodeArray = explode(':',$input['gateway'][ConnectResponseFields::APPROVAL_CODE]);
        // Retrieve actual approval code
        $approvalCode = implode(array_slice($approvalCodeArray, 0, 2),':');

        if ($approvalCode[0] !== 'Y')
        {
            $attributes = $this->getCallbackFields($input['gateway']);

            $desc = ErrorCodes::getErrorDesc($approvalCode);

            $gatewayPayment->fill($attributes);
            $gatewayPayment->setErrorMessage($desc);
            $gatewayPayment->setApprovalCode($approvalCode);

            $this->repo->saveOrFail($gatewayPayment);

            $errorCode = ErrorCodes::getMappedCode($approvalCode);

            throw new Exception\GatewayErrorException($errorCode, $approvalCode, $desc);
        }
    }

    private function verifyResponseHash($input)
    {
        $approvalCode   = $input[ConnectResponseFields::APPROVAL_CODE];
        $txnDateTime    = $input[ConnectResponseFields::TXN_DATE_TIME];
        $chargeTotal    = $input[ConnectResponseFields::CHARGE_TOTAL];
        $currencyCode   = $input[ConnectResponseFields::CURRENCY];

        $expectedHash  = $this->getExpectedResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime);

        if (hash_equals($expectedHash, $input[ConnectResponseFields::RESPONSE_HASH]) === false)
        {
            $this->trace->error(
                TraceCode::GATEWAY_CHECKSUM_VERIFY_FAILED, ['auth_response' => $input, 'expected_hash' => $expectedHash]);

            throw new Exception\BadRequestValidationFailureException('Failed response_hash verification');
        }
    }

    protected function traceGatewayPaymentRequest($request, $input)
    {
        unset($request['content'][ConnectRequestFields::CARD_NUMBER]);
        unset($request['content'][ConnectRequestFields::CVV]);

        parent::traceGatewayPaymentRequest($request, $input);
    }

    protected function getStoreId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::TEST_STORE_ID];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
    }

    protected function getCredentials()
    {
        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config['test_user_id'],
                'password' => $this->config['test_password']
            );

            return $auth;
        }

        $auth = array(
            'username' => $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_ID],
            'password' => $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD]
        );

        return $auth;
    }

    protected function getServerCertificate()
    {
        return storage_path() . '/' . $this->config[self::SERVER_CERTIFICATE_PATH];
    }

    protected function getClientCertificate()
    {
        return storage_path() . '/' . $this->config[self::CLIENT_CERTIFICATE_PATH];
    }

    protected function getClientCertificateKey()
    {
        return storage_path() . '/' . $this->config[self::CLIENT_CERTIFICATE_KEY_PATH];
    }

    protected function getSharedSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::TEST_HASH_SECRET];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET];
    }
}
