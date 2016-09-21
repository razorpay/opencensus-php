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
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\Action;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{

    protected $gateway = \RZP\Constants\Entity::FIRST_DATA;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPreauthRequestContentArray($input);

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

        $this->verifyResponseHash($input['gateway'], $gatewayPayment);

        $this->verifyPaymentCallbackResponse($input);

        $attributes = $this->getCallbackFields($input['gateway']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->repo->retrieveCapturedByPaymentId($input['payment'][Payment\Entity::ID]);

        if (($gatewayPayment !== null) and
            ($gatewayPayment[Entity::AMOUNT] === $input['payment'][Payment\Entity::AMOUNT]))
        {
            $this->trace->info(TraceCode::PAYMENT_ALREADY_CAPTURED, $input['payment']);
            return;
        }

        $content = $this->getIpgApiOrderContentArray($input, TxnType::CAPTURE);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $content);

        $response = $this->postOrderRequestAndParseResponse($content);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, [$response]);

        $captureFields = $this->getCaptureFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getIpgApiOrderContentArray($input, TxnType::REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $content);

        $response = $this->postOrderRequestAndParseResponse($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [$response]);

        $refundFields = $this->getRefundFields($response, $input['refund']);

        $refundEntity = $this->createGatewayPaymentEntity($refundFields, $input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getAuthorizeFields($authRequest)
    {
        $attributes = array(
            Entity::AMOUNT            => $authRequest[ConnectRequestFields::CHARGE_TOTAL]*100,
            Entity::ORDER_ID          => $authRequest[ConnectRequestFields::ORDER_ID],
        );

        return $attributes;
    }

    protected function getCallbackFields($callbackBody)
    {
        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::TDATE                       => $callbackBody[ConnectResponseFields::TDATE],
            Entity::STATUS                      => $callbackBody[ConnectResponseFields::STATUS],
            Entity::PROCESSOR_RESPONSE_CODE     => $callbackBody[ConnectResponseFields::PROCESSOR_RESPONSE_CODE],
            Entity::TERMINAL_ID                 => $callbackBody[ConnectResponseFields::TERMINAL_ID],
        );

        if (isset($callbackBody[ConnectResponseFields::FAIL_RC]))
        {
            $attributes[Entity::FAIL_RC]     = $callbackBody[ConnectResponseFields::FAIL_RC];
            $attributes[Entity::FAIL_REASON] = $callbackBody[ConnectResponseFields::FAIL_REASON];
        }


        return $attributes;
    }

    protected function getCaptureFields($captureResponse, $paymentInput)
    {
        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::AMOUNT                      => $paymentInput['amount'],
            Entity::TDATE                       => $captureResponse[ApiResponseFields::TDATE],
            Entity::STATUS                      => $captureResponse[ApiResponseFields::TRANSACTION_RESULT],
            Entity::ORDER_ID                    => $captureResponse[ApiResponseFields::ORDER_ID],
            Entity::PROCESSOR_APPROVAL_CODE     => $captureResponse[ApiResponseFields::PROCESSOR_APPROVAL_CODE],
            Entity::PROCESSOR_RESPONSE_CODE     => $captureResponse[ApiResponseFields::PROCESSOR_RESPONSE_CODE],
            Entity::PROCESSOR_RESPONSE_MESSAGE  => $captureResponse[ApiResponseFields::PROCESSOR_RESPONSE_MESSAGE],
            Entity::TERMINAL_ID                 => $captureResponse[ApiResponseFields::TERMINAL_ID],
        );

        return $attributes;
    }

    protected function getRefundFields($refundResponse, $refundInput)
    {
        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::AMOUNT                      => $refundInput['amount'],
            Entity::TDATE                       => $refundResponse[ApiResponseFields::TDATE],
            Entity::STATUS                      => $refundResponse[ApiResponseFields::TRANSACTION_RESULT],
            Entity::ORDER_ID                    => $refundResponse[ApiResponseFields::ORDER_ID],
            Entity::PROCESSOR_APPROVAL_CODE     => $refundResponse[ApiResponseFields::PROCESSOR_APPROVAL_CODE],
            Entity::PROCESSOR_RESPONSE_CODE     => $refundResponse[ApiResponseFields::PROCESSOR_RESPONSE_CODE],
            Entity::PROCESSOR_RESPONSE_MESSAGE  => $refundResponse[ApiResponseFields::PROCESSOR_RESPONSE_MESSAGE],
            Entity::TERMINAL_ID                 => $refundResponse[ApiResponseFields::TERMINAL_ID],
        );

        return $attributes;
    }

    protected function getVerifyFields($refundResponse, $refundInput)
    {
        $attributes = array(
            Entity::RECEIVED                    => true,
            Entity::AMOUNT                      => $refundInput['amount'],
            Entity::TDATE                       => $refundResponse[ApiResponseFields::TDATE],
            Entity::STATUS                      => $refundResponse[ApiResponseFields::TRANSACTION_RESULT],
            Entity::ORDER_ID                    => $refundResponse[ApiResponseFields::ORDER_ID],
            Entity::PROCESSOR_APPROVAL_CODE     => $refundResponse[ApiResponseFields::PROCESSOR_APPROVAL_CODE],
            Entity::PROCESSOR_RESPONSE_CODE     => $refundResponse[ApiResponseFields::PROCESSOR_RESPONSE_CODE],
            Entity::PROCESSOR_RESPONSE_MESSAGE  => $refundResponse[ApiResponseFields::PROCESSOR_RESPONSE_MESSAGE],
            Entity::TERMINAL_ID                 => $refundResponse[ApiResponseFields::TERMINAL_ID],
        );

        return $attributes;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $content = $this->getVerifyRequestContentArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $content);

        $ipgApiActionResponse = $this->postActionRequestAndParseResponse($content);

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
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->gatewaySuccess = $this->getVerifyGatewayStatus($content);

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($gatewayPayment, $content);

        return $verify->status;
    }

    protected function saveVerifyContentIfNeeded($gatewayPayment, $response)
    {
        // s($gatewayPayment->toArray());
        // s($response->children('a1', true));
    }

    protected function getVerifyGatewayStatus($ipgApiActionResponse)
    {
        $transactionValues = $ipgApiActionResponse->children('a1', true);

        foreach ( $ipgApiActionResponse->children('a1', true) as $hi )
        {
            s($hi->children('v1', true));
        }

        $transactionValuesArray = (json_decode(json_encode($transactionValues), true)[ApiResponseFields::TRANSACTION_VALUES]);

        $latestTransactionState = end($transactionValuesArray)[ApiResponseFields::TRANSACTION_STATE];

        $gatewayStatus = in_array($latestTransactionState, array(Status::SETTLED, Status::CAPTURED)) ? true : false;

        return $gatewayStatus;
    }

    protected function getVerifyApiStatus($gatewayPayment, $input)
    {
        if (($input['payment'][Payment\Entity::STATUS] === 'failed') or
            ($input['payment'][Payment\Entity::STATUS] === 'created'))
        {
            $apiStatus = false;

            if (($gatewayPayment[Entity::RECEIVED] === true) or
                ($gatewayPayment[Entity::STATUS] === 'APPROVED'))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'gateway_payment'   => $gatewayPayment,
                        'payment'           => $input['payment']
                    ]);
            }
        }
        else
        {
            $apiStatus = true;

            if (($gatewayPayment[Entity::RECEIVED] === false) or
                ($gatewayPayment[Entity::STATUS] !== 'APPROVED'))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'gateway_payment'   => $gatewayPayment,
                        'payment'           => $input['payment']
                    ]);
            }
        }

        return $apiStatus;
    }

    protected function postSoapRequest($content, $requestType = Constants::ORDER_REQUEST)
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

    protected function postOrderRequestAndParseResponse($content)
    {
        $xml = $this->postSoapRequest($content, ApiRequestFields::ORDER_REQUEST);

        if ($xml->children('SOAP-ENV', true)->Body->Fault->count() > 0)
        {
            $gatewayCode = $xml->children('SOAP-ENV', true)->Body->Fault->children()->detail->children('ipgapi', true)->IPGApiOrderResponse->ApprovalCode->__toString();

            $desc = $xml->children('SOAP-ENV', true)->Body->Fault->children()->detail->children('ipgapi', true)->IPGApiOrderResponse->ErrorMessage->__toString();

            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED, $gatewayCode, $desc);
        }

        $ipgApiOrderResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $xmlBody = $ipgApiOrderResponse->children('ipgapi', true);

        $body = json_decode(json_encode($xmlBody), true);

        return $body;
    }

    protected function postActionRequestAndParseResponse($content)
    {
        $xml = $this->postSoapRequest($content, ApiRequestFields::ACTION_REQUEST);

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
            $type = Constants::SERVICES;
        }
        else
        {
            $type = Constants::PROCESSING;
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

    protected function getPreauthRequestContentArray($input)
    {
        $content = $this->getRequestContentArray($input);

        $content[ConnectRequestFields::TXN_TYPE] = TxnType::AUTH;

        $method = $input['card'][Card\Entity::NETWORK_CODE];

        $content[ConnectRequestFields::PAYMENT_METHOD] = Mapping::PAYMENT_METHOD_CODES[$method];

        $this->setCardDetails($content, $input);
        $this->setCallbackUrls($content, $input);

        return $content;
    }

    protected function setCardDetails(& $content, $input)
    {
        $content[ConnectRequestFields::CARD_NUMBER] = $input['card'][Card\Entity::NUMBER];
        $content[ConnectRequestFields::NAME]        = $input['card'][Card\Entity::NAME];
        $content[ConnectRequestFields::EXP_MONTH]   = $input['card'][Card\Entity::EXPIRY_MONTH];
        $content[ConnectRequestFields::EXP_YEAR]    = $input['card'][Card\Entity::EXPIRY_YEAR];
        $content[ConnectRequestFields::CVV]         = $input['card'][Card\Entity::CVV];
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
        $storeId = $this->getStoreName();
        $sharedSecret = $this->getSecret();

        $stringToHash = $storeId . $txnDateTime . $chargeTotal . $currencyCode . $sharedSecret;

        $hash_algorithm = strtolower(Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    // This is a SHA hash of the following fields :
    // sharedsecret + approvalcode + chargetotal + currency + txndatetime + storename.
    protected function getExpectedResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime)
    {
        $storeId = $this->getStoreName();
        $sharedSecret = $this->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;

        $hash_algorithm = strtolower(Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    protected function getRequestContentArray($input)
    {
        $createdAt = $input['payment'][Payment\Entity::CREATED_AT];
        $dateTime = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata');
        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];

        $content = array(
            ConnectRequestFields::TIME_ZONE                 => 'Asia/Kolkata',
            ConnectRequestFields::TXN_DATE_TIME             => $txnDateTime,
            ConnectRequestFields::HASH_ALGORITHM            => Codes::FIRST_DATA_HASH_ALGORITHM,
            ConnectRequestFields::HASH                      => $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode),
            ConnectRequestFields::STORE_NAME                => $this->getStoreName(),
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

        $request[ApiRequestFields::A1_ACTION][ApiRequestFields::A1_INQUIRY_ORDER][ApiRequestFields::A1_ORDER_ID] = $gatewayPayment[Entity::ORDER_ID];

        return $request;
    }

    protected function getIpgApiOrderContentArray($input, $txnType)
    {
        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $currency     = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];
        $orderId      = $gatewayPayment[Entity::ORDER_ID];
        $amountEntity = TxnType::$amountEntity[$txnType];

        $body[ApiRequestFields::V1_CREDITCARDTXTYPE][ApiRequestFields::V1_TYPE]      = $txnType;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CHARGETOTAL]        = $input[$amountEntity][Payment\Entity::AMOUNT]/100;
        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY]           = $currencyCode;
        $body[ApiRequestFields::V1_TRANSACTIONDETAILS][ApiRequestFields::V1_ORDERID] = $gatewayPayment[Entity::ORDER_ID];

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
        if ($wrap != null)
        {
            $xml = "<$wrap>".$xml."</$wrap>";
        }

        return $xml;
    }


    protected function verifyPaymentCallbackResponse($input)
    {
        if ((isset($input['gateway'][ConnectResponseFields::APPROVAL_CODE]) === false) or
            ($input['gateway'][ConnectResponseFields::APPROVAL_CODE][0] !== 'Y'))
        {
            $gatewayPayment = $this->repo
                            ->findByPaymentIdAndActionOrFail($input['gateway'][ConnectResponseFields::ORDER_ID], Base\Action::AUTHORIZE);

            $attributes = $this->getCallbackFields($input['gateway']);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);

            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }
    }

    private function verifyResponseHash($input)
    {
        $approvalCode   = $input[ConnectResponseFields::APPROVAL_CODE];
        $txnDateTime    = $input[ConnectResponseFields::TXN_DATE_TIME];
        $chargeTotal    = $input[ConnectResponseFields::CHARGE_TOTAL];
        $currencyCode   = $input[ConnectResponseFields::CURRENCY];

        $expectedHash  = $this->getExpectedResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime);

        if (!hash_equals($expectedHash, $input[ConnectResponseFields::RESPONSE_HASH]))
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, array($input, $expectedHash));

            throw new Exception\BadRequestValidationFailureException('Failed response_hash verification');
        }
    }

    protected function getStoreName()
    {
        if ($this->mode ===Mode::TEST)
        {
            return $this->config[Constants::TEST_STORE_ID];
        }

        return $this->terminal['gateway_merchant_id'];
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
            'username' => $terminal['gateway_terminal_id'],
            'password' => $terminal['gateway_terminal_password']
        );

        return $auth;
    }

    protected function getServerCertificate()
    {
        return storage_path() . '/' . $this->config[Constants::SERVER_CERTIFICATE_PATH];
    }

    protected function getClientCertificate()
    {
        return storage_path() . '/' . $this->config[Constants::CLIENT_CERTIFICATE_PATH];
    }

    protected function getClientCertificateKey()
    {
        return storage_path() . '/' . $this->config[Constants::CLIENT_CERTIFICATE_KEY_PATH];
    }

    protected function getSharedSecret()
    {

        if ($this->mode === Mode::TEST)
        {
            return $this->config[Constants::TEST_HASH_SECRET];
        }

        return $this->terminal['gateway_secure_secret'];
    }
}
