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

        $payment = $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifyPaymentCallbackResponse($input);

        $payment = $this->getRepository()
                        ->findByPaymentIdAndActionOrFail($input['gateway'][Constants::ORDER_ID], Base\Action::AUTHORIZE);

        $this->verifyHash($input['gateway'],$payment);

        $attributes = array(
            Entity::RECEIVED            => true,
            Entity::TDATE               => $input['gateway'][Entity::TDATE],
            Entity::APPROVAL_CODE       => $input['gateway'][Entity::APPROVAL_CODE],
            Entity::STATUS              => $input['gateway'][Entity::STATUS],
            Entity::TXNDATE_PROCESSED   => $input['gateway'][Entity::TXNDATE_PROCESSED],
        );

        $payment->fill($attributes);

        $this->getRepository()->saveOrFail($payment);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->getRepository()->retrieveCapturedByPaymentId($input['payment'][Payment\Entity::ID]);

        if (($gatewayPayment !== null) and
        ($gatewayPayment[Entity::AMOUNT] === $input['payment'][Payment\Entity::AMOUNT]))
        {
            return;
        }

        $content = $this->getIpgApiOrderContentArray($input, Codes::TXN_TYPE_CAPTURE);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $content);

        $response = $this->postOrderRequestAndParseResponse($content);
        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, [$response]);

        $payment = $this->getRepository()->findByPaymentIdAndActionOrFail($input['payment'][Payment\Entity::ID], Base\Action::AUTHORIZE);

        $attributes = array(
            Entity::STATUS  => $response[Constants::TRANSACTION_RESULT],
        );

        $payment->fill($attributes);

        $this->getRepository()->saveOrFail($payment);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getIpgApiOrderContentArray($input, Codes::TXN_TYPE_REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $content);

        $response = $this->postOrderRequestAndParseResponse($content);
        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [$response]);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
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
            Entity::TDATE       => $verifyResponse->children('a1',true)->children('ipgapi',true)->IPGApiOrderResponse->TDate->__toString(),
            Entity::STATUS      => $verifyResponse->children('a1',true)->TransactionValues->TransactionState->__tostring(),
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

        return $verify->status;
    }

    protected function getVerifyGatewayStatus($ipgApiActionResponse)
    {
        $transactionValues = $ipgApiActionResponse->children('a1',true);
        $transactionValuesArray = (json_decode(json_encode($transactionValues), true)[Constants::TRANSACTION_VALUES]);

        $latestTransactionState = end($transactionValuesArray)[Constants::TRANSACTION_STATE];

        $gatewayStatus = in_array($latestTransactionState, array('AUTHORIZED','CAPTURED')) ? true : false;

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

    protected function postSoapRequest($content, $action = Constants::ORDER_REQUEST)
    {
        $xmlRequest = $this->arrayToXml($content);
        $content = SoapWrapper::defaultWrapper($xmlRequest, $action);

        $options = $this->getRequestOptions();
        $request = $this->getStandardRequestArray($content, $options);

        $response = $this->sendGatewayRequest($request);
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [$response->body]);

        $xml   = simplexml_load_string($response->body);

        return $xml;
    }

    protected function postOrderRequestAndParseResponse($content)
    {
        $xml = $this->postSoapRequest($content, Constants::ORDER_REQUEST);

        if ($xml->children('SOAP-ENV', true)->Body->Fault->count() > 0)
        {
            $gatewayCode = $xml->children('SOAP-ENV', true)->Body->Fault->children()->detail->children('ipgapi',true)->IPGApiOrderResponse->ApprovalCode->__toString();
            $desc = $xml->children('SOAP-ENV', true)->Body->Fault->children()->detail->children('ipgapi',true)->IPGApiOrderResponse->ErrorMessage->__toString();
            throw new Exception\GatewayErrorException(Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED, $gatewayCode, $desc);
        }

        $ipgApiOrderResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $xmlBody = $ipgApiOrderResponse->children('ipgapi', true);
        $body = json_decode(json_encode($xmlBody), true);

        return $body;
    }

    protected function postActionRequestAndParseResponse($content)
    {
        $xml = $this->postSoapRequest($content, Constants::ACTION_REQUEST);

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

        $content[Constants::TXN_TYPE] = Codes::TXN_TYPE_AUTH;

        $method = $input['card'][Card\Entity::NETWORK_CODE];

        $content[Constants::PAYMENT_METHOD] = Mapping::PAYMENT_METHOD_CODES[$method];

        $this->setCardDetails($content, $input);
        $this->setCallbackUrls($content, $input);

        return $content;
    }

    protected function setCardDetails(&$content, $input)
    {
        $content[Constants::CARD_NUMBER] = $input['card'][Card\Entity::NUMBER];

        $content[Constants::NAME]      = $input['card'][Card\Entity::NAME];
        $content[Constants::EXP_MONTH] = $input['card'][Card\Entity::EXPIRY_MONTH];
        $content[Constants::EXP_YEAR]  = $input['card'][Card\Entity::EXPIRY_YEAR];
        $content[Constants::CVV]       = $input['card'][Card\Entity::CVV];
    }

    protected function setCallbackUrls(&$content, $input)
    {
        $content[Constants::RESPONSE_SUCCESS_URL]      = $input['callbackUrl'];
        $content[Constants::RESPONSE_FAIL_URL]         = $input['callbackUrl'];
    }

    protected function createGatewayPaymentEntity($content)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->fill($content);
        $payment->setPaymentId($content[Constants::ORDER_ID]);
        $payment->setAction($this->action);

        $this->getRepository()->saveOrFail($payment);

        return $payment;
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
    protected function getResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime)
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
        $chargeTotal = number_format($chargeTotal,2,'.','');

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];

        $content = array(
            Constants::TIME_ZONE                 => 'Asia/Kolkata',
            Constants::TXN_DATE_TIME             => $txnDateTime,
            Constants::HASH_ALGORITHM            => Codes::FIRST_DATA_HASH_ALGORITHM,
            Constants::HASH                      => $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode),
            Constants::STORE_NAME                => $this->getStoreName(),
            Constants::MODE                      => Codes::PAYMENT_MODE_PAYONLY,
            Constants::CHARGE_TOTAL              => $chargeTotal,
            Constants::CURRENCY                  => $currencyCode,

            Constants::ORDER_ID                  => $input['payment'][Payment\Entity::ID],
            Constants::INVOICE_NUMBER            => $input['payment'][Payment\Entity::ID],

            Constants::CARD_FUNCTION             => $input['card'][Card\Entity::TYPE],
            Constants::COMMENTS                  => '',

            Constants::DYNAMIC_MERCHANT_NAME     => 'Razorpay Payments',
            Constants::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
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

        $options['verify'] = $this->getServerCertificate();

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());
        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    }

    protected function getVerifyRequestContentArray($input)
    {
        $gatewayPayment = $this->getRepository()->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $request['a1:Action']['a1:InquiryOrder']['a1:OrderId'] = $gatewayPayment['oid'];

        return $request;
    }

    protected function getCaptureRequestContentArray($input)
    {
        $gatewayPayment = $this->getRepository()->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];
        $orderId = $gatewayPayment[Constants::ORDER_ID];
        $amountEntity = Codes::$amountEntity[Codes::TXN_TYPE_CAPTURE];

        $body['v1:CreditCardTxType']['v1:Type'] = Codes::TXN_TYPE_CAPTURE;
        $body['v1:Payment']['v1:ChargeTotal'] = $input[$amountEntity][Payment\Entity::AMOUNT]/100;
        $body['v1:Payment']['v1:Currency'] = $currencyCode;
        $body['v1:TransactionDetails']['v1:OrderId'] = $gatewayPayment['oid'];

        $request['v1:Transaction'] = $body;

        return $request;
    }

    protected function getRefundRequestContentArray($input)
    {
        $gatewayPayment = $this->getRepository()->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];
        $orderId = $gatewayPayment[Constants::ORDER_ID];
        $amountEntity = Codes::$amountEntity[Codes::TXN_TYPE_REFUND];

        $body['v1:CreditCardTxType']['v1:Type'] = Codes::TXN_TYPE_REFUND;
        $body['v1:Payment']['v1:ChargeTotal'] = $input[$amountEntity][Payment\Entity::AMOUNT]/100;
        $body['v1:Payment']['v1:Currency'] = $currencyCode;
        $body['v1:TransactionDetails']['v1:OrderId'] = $gatewayPayment['oid'];

        $request['v1:Transaction'] = $body;

        return $request;
    }

    protected function getIpgApiOrderContentArray($input, $txnType)
    {
        $gatewayPayment = $this->getRepository()->retrieveByPaymentIdOrFail($input['payment'][Payment\Entity::ID]);

        $currency = $input['payment'][Payment\Entity::CURRENCY];
        $currencyCode = Mapping::ISO_NUMERIC_CODES[$currency];
        $orderId = $gatewayPayment[Constants::ORDER_ID];
        $amountEntity = Codes::$amountEntity[$txnType];

        $body[Constants::V1_CREDITCARDTXTYPE][Constants::V1_TYPE]      = $txnType;
        $body[Constants::V1_PAYMENT][Constants::V1_CHARGETOTAL]        = $input[$amountEntity][Payment\Entity::AMOUNT]/100;
        $body[Constants::V1_PAYMENT][Constants::V1_CURRENCY]           = $currencyCode;
        $body[Constants::V1_TRANSACTIONDETAILS][Constants::V1_ORDERID] = $gatewayPayment[Constants::ORDER_ID];

        $request[Constants::V1_TRANSACTION] = $body;

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
        if ((isset($input['gateway'][Constants::APPROVAL_CODE]) === false) or
            ($input['gateway'][Constants::APPROVAL_CODE][0] !== 'Y'))
        {
            $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, [$input['gateway']]);

            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }
    }

    private function verifyHash($input)
    {
        $approvalCode   = $input[Constants::APPROVAL_CODE];
        $txnDateTime    = $input[Constants::TXN_DATE_TIME];
        $chargeTotal    = $input[Constants::CHARGE_TOTAL];
        $currencyCode   = $input[Constants::CURRENCY];

        $expectedHash  = $this->getResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime);

        if ($expectedHash != $input[Constants::RESPONSE_HASH])
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, array($input,$expectedHash));

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
