<?php

namespace RZP\Gateway\FirstData;

use Requests;
use Requests_Hooks;
use RZP\Error;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Trace\Trace;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\VerifyResult;
use Carbon\Carbon;

class Gateway extends Base\Gateway
{
    const TXN_TYPE                          = 'txntype';
    const TIME_ZONE                         = 'timezone';
    const TXN_DATE_TIME                     = 'txndatetime';
    const HASH_ALGORITHM                    = 'hash_algorithm';
    const HASH                              = 'hash';
    const STORE_NAME                        = 'storename';
    const MODE                              = 'mode';
    const CHARGE_TOTAL                      = 'chargetotal';
    const CURRENCY                          = 'currency';
    const ORDER_ID                          = 'oid';
    const TDATE                             = 'tdate';
    const NAME                              = 'bname';
    const PAYMENT_METHOD                    = 'paymentMethod';
    const CUSTOMER_ID                       = 'customerid';
    const INVOICE_NUMBER                    = 'invoicenumber';
    const CARD_FUNCTION                     = 'cardFunction';
    const COMMENTS                          = 'comments';
    const RESPONSE_SUCCESS_URL              = 'responseSuccessURL';
    const RESPONSE_FAIL_URL                 = 'responseFailURL';
    const DYNAMIC_MERCHANT_NAME             = 'dynamicMerchantName';
    const LANGUAGE                          = 'language';
    const HASH_EXTENDED                     = 'hashExtended';
    const NUMBER_OF_INSTALLMENTS            = 'numberOfInstallments';
    const TRX_ORIGIN                        = 'trxOrigin';
    const DCC_INQUIRY_ID                    = 'dccInquiryId';

    const CARD_NUMBER                       = 'cardnumber';
    const EXP_MONTH                         = 'expmonth';
    const EXP_YEAR                          = 'expyear';
    const CVM                               = 'cvm';

    const APPROVAL_CODE                     = 'approval_code';
    const RESPONSE_HASH                     = 'response_hash';
    const ORDER_REQUEST                     = 'IPGApiOrderRequest';
    const ACTION_REQUEST                    = 'IPGApiActionRequest';

    const TEST_STORE_ID                     = 'test_store_id';
    const TEST_HASH_SECRET                  = 'test_hash_secret';

    const SERVER_CERTIFICATE_PATH           = 'server_certificate_path';
    const CLIENT_CERTIFICATE_PATH           = 'client_certificate_path';
    const CLIENT_CERTIFICATE_KEY_PATH       = 'client_certificate_key_path';

    protected $gateway = \RZP\Constants\Entity::FIRST_DATA;

    public function authorize(array $input)
    {
        $input['card']['type']='credit';

        parent::authorize($input);

        $content = $this->getPreauthRequestContentArray($input);

        $payment = $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardConnectRequestArray($content, 'post');

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifyPaymentCallbackResponse($input);

        $payment = $this->getRepo()
                        ->findByPaymentIdAndActionOrFail($input['gateway']['oid'], Base\Action::AUTHORIZE);

        $this->verifyHash($input['gateway'],$payment);

        $attributes = array(
            Entity::RECEIVED            => true,
            Entity::TDATE               => $input['gateway'][Entity::TDATE],
            Entity::APPROVAL_CODE       => $input['gateway'][Entity::APPROVAL_CODE],
            Entity::STATUS              => $input['gateway'][Entity::STATUS],
            Entity::TXNDATE_PROCESSED   => $input['gateway'][Entity::TXNDATE_PROCESSED],
        );

        $payment->fill($attributes);
        $payment->saveOrFail();
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->getRepo()->retrieveCapturedByPaymentId($input['payment']['id']);

        if (($gatewayPayment !== null) and
            ($gatewayPayment['amount'] === $input['payment']['amount']))
        {
            return;
        }

        $content = $this->getCaptureRequestContentArray($input);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $content);

        $response = $this->postOrderRequestAndParseResponse($content);
        $this->trace->info(TraceCode::GATEWAY_CAPTURE_RESPONSE, [$response]);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        $attributes = array(
            Entity::STATUS  => $response['TransactionResult'],
        );

        $payment->fill($attributes);
        $payment->saveOrFail();
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestContentArray($input);

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

        $content = array(
            Entity::TDATE       => $ipgApiActionResponse->children('a1',true)->children('ipgapi',true)->IPGApiOrderResponse->TDate->__toString(),
            Entity::STATUS      => $ipgApiActionResponse->children('a1',true)->TransactionValues->TransactionState->__tostring(),
        );

        $verify->verifyResponseContent = $ipgApiActionResponse;

        return $content;
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
        $transactionValuesArray = (json_decode(json_encode($transactionValues), true)['TransactionValues']);

        $latestTransactionState = end($transactionValuesArray)['TransactionState'];

        $gatewayStatus = in_array($latestTransactionState, array('AUTHORIZED','CAPTURED')) ? true : false;

        return $gatewayStatus;
    }

    protected function getVerifyApiStatus($gatewayPayment, $input)
    {
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $apiStatus = false;

            if (($gatewayPayment['received'] === true) or
                ($gatewayPayment['status'] === 'APPROVED'))
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

            if (($gatewayPayment['received'] === false) or
                ($gatewayPayment['status'] !== 'APPROVED'))
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

    protected function postSoapRequest($content, $action = self::ORDER_REQUEST)
    {
        $xmlRequest = $this->arrayToXml($content);
        $content = $this->wrapSoap($xmlRequest, $action);

        $options = $this->getRequestOptions();
        $request = $this->getStandardApiRequestArray($content, $options);

        $response = $this->sendGatewayRequest($request);
        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [$response->body]);

        $xml   = simplexml_load_string($response->body);

        return $xml;
    }

    protected function postOrderRequestAndParseResponse($content)
    {
        $xml = $this->postSoapRequest($content, self::ORDER_REQUEST);

        if ( $xml->children('SOAP-ENV', true)->Body->Fault->count() > 0)
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
        $xml = $this->postSoapRequest($content, self::ACTION_REQUEST);

        $ipgApiActionResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $successful = $ipgApiActionResponse->IPGApiActionResponse->successfully->__toString();
        if ( $successful == 'false' )
        {
            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }

        return $ipgApiActionResponse;
    }

    protected function wrapSoap($content, $action)
    {
        $soapWrapper = "<?xml version='1.0' encoding='UTF-8'?><SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'><SOAP-ENV:Body><ipgapi:$action xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1' xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1'>$content</ipgapi:$action></SOAP-ENV:Body></SOAP-ENV:Envelope>";

        return $soapWrapper;
    }

    protected function getStandardConnectRequestArray($content = [], $method = 'post')
    {
        $request = array(
            'url'       => $this->getUrl('processing'),
            'content'   => $content,
            'method'    => $method
        );

        return $request;
    }

    protected function getStandardApiRequestArray($content = [], $options = [], $method = 'post')
    {
        $request = array(
            'url'       => $this->getUrl('services'),
            'content'   => $content,
            'method'    => $method,
            'options'   => $options
        );

        return $request;
    }

    protected function getPreauthRequestContentArray($input)
    {
        $content = $this->getRequestContentArray($input);

        $content[self::TXN_TYPE] = Codes::TXN_TYPE_PREAUTH;

        $method = $input['card']['network_code'];

        $content[self::PAYMENT_METHOD] = Mapping::$paymentMethodCodes[$method];

        $this->setCardDetails($content, $input);
        $this->setCallbackUrls($content, $input);

        return $content;
    }

    protected function setCardDetails(&$content, $input)
    {
        // $content[self::CARD_NUMBER] = Card\Tokenex::getCardNumber($input['card']['vault_token']);
        $content[self::CARD_NUMBER] = $input['card']['number'];

        $content[self::NAME]      = $input['card']['name'];
        $content[self::EXP_MONTH] = $input['card']['expiry_month'];
        $content[self::EXP_YEAR ] = $input['card']['expiry_year'];
        $content[self::CVM]       = $input['card']['cvv'];
    }

    protected function setCallbackUrls(&$content, $input)
    {
        $content[self::RESPONSE_SUCCESS_URL]      = $input['callbackUrl'];
        $content[self::RESPONSE_FAIL_URL]         = $input['callbackUrl'];
    }

    protected function createGatewayPaymentEntity($content)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->fill($content);
        $payment->setPaymentId($content[self::ORDER_ID]);
        $payment->setAction($this->action);
        $payment->saveOrFail();

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
        $createdAt = $input['payment']['created_at'];
        $dateTime = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata');
        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment']['amount'] / 100;
        $chargeTotal = number_format($chargeTotal,2,'.','');

        $currency = $input['payment']['currency'];
        $currencyCode = Mapping::$isoNumericCodes[$currency];

        $content = array(
            self::TIME_ZONE                 => 'Asia/Kolkata',
            self::TXN_DATE_TIME             => $txnDateTime,
            self::HASH_ALGORITHM            => Codes::FIRST_DATA_HASH_ALGORITHM,
            self::HASH                      => $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode),
            self::STORE_NAME                => $this->getStoreName(),
            self::MODE                      => Codes::PAYMENT_MODE_PAYONLY,
            self::CHARGE_TOTAL              => $chargeTotal,
            self::CURRENCY                  => $currencyCode,

            self::ORDER_ID                  => $input['payment']['id'],
            self::INVOICE_NUMBER            => $input['payment']['id'],

            self::CARD_FUNCTION             => $input['card']['type'],
            self::COMMENTS                  => '',

            self::DYNAMIC_MERCHANT_NAME     => 'Razorpay Payments',
            self::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
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

        // $options['verify'] = $this->getServerCertificate();

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
        $gatewayPayment = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $request['a1:Action']['a1:InquiryOrder']['a1:OrderId'] = $gatewayPayment['oid'];

        return $request;
    }

    protected function getCaptureRequestContentArray($input)
    {
        $gatewayPayment = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $currency = $input['payment']['currency'];
        $currencyCode = Mapping::$isoNumericCodes[$currency];
        $orderId = $gatewayPayment['oid'];

        $body['v1:CreditCardTxType']['v1:Type'] = Codes::TXN_TYPE_POSTAUTH;
        $body['v1:Payment']['v1:ChargeTotal'] = $input['payment']['amount']/100;
        $body['v1:Payment']['v1:Currency'] = $currencyCode;
        $body['v1:TransactionDetails']['v1:OrderId'] = $gatewayPayment['oid'];

        $request['v1:Transaction'] = $body;

        return $request;
    }

    protected function getRefundRequestContentArray($input)
    {
        $gatewayPayment = $this->getRepo()->retrieveByPaymentIdOrFail($input['payment']['id']);

        $currency = $input['payment']['currency'];
        $currencyCode = Mapping::$isoNumericCodes[$currency];
        $orderId = $gatewayPayment['oid'];

        $body['v1:CreditCardTxType']['v1:Type'] = Codes::TXN_TYPE_REFUND;
        $body['v1:Payment']['v1:ChargeTotal'] = $input['refund']['amount']/100;
        $body['v1:Payment']['v1:Currency'] = $currencyCode;
        $body['v1:TransactionDetails']['v1:OrderId'] = $gatewayPayment['oid'];
        $body['v1:ClientLocale']['v1:Language'] = Codes::ENGLISH_UK_LANG_CODE_API;

        $request['v1:Transaction'] = $body;

        return $request;
    }

    private function arrayToXml($array, $wrap=null)
    {
        // set initial value for XML string
        $xml = '';
        foreach ($array as $key => $value)
        {
            if ( is_array($value) == true )
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
        if ((isset($input['gateway']['approval_code']) === false) or
            ($input['gateway']['approval_code'][0] !== 'Y'))
        {
            $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, [$input['gateway']]);

            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
        }
    }

    private function verifyHash($input)
    {
        $approvalCode = $input[self::APPROVAL_CODE];

        $txnDateTime = $input[self::TXN_DATE_TIME];
        $chargeTotal = $input[self::CHARGE_TOTAL];
        $currencyCode = $input[self::CURRENCY];

        $expectedHash = $this->getResponseHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime);

        if ($expectedHash != $input[self::RESPONSE_HASH])
        {
            $this->trace->error(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE, array($input,$expectedHash));

            throw new Exception\BadRequestValidationFailureException('Failed response_hash verification');
        }
    }

    protected function getStoreName()
    {
        $terminal = $this->terminal;

        if ($this->mode ===Mode::TEST)
        {
            return $this->config[self::TEST_STORE_ID];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    protected function getCredentials()
    {
        $terminal = $this->terminal;

        $auth = array(
            'username' => $terminal['gateway_terminal_id'],
            'password' => $terminal['gateway_terminal_password']
        );

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config['test_user_id'],
                'password' => $this->config['test_password']
            );
        }

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
        $terminal = $this->terminal;

        if ($this->mode ===Mode::TEST)
        {
            return $this->config[self::TEST_HASH_SECRET];
        }

        return $this->terminal['gateway_secure_secret'];
    }
}
