<?php

namespace RZP\Gateway\FirstData\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\FirstData;
use RZP\Gateway\FirstData\Action;
use RZP\Models\Card;
use \WpOrg\Requests\Response;
use RZP\Models\Payment;
use RZP\Gateway\FirstData\ApiRequestFields;
use RZP\Gateway\FirstData\ApiResponseFields;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new FirstData\Repository;
    }

    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        $response = [
            ApiResponseFields::MD       => $input[ApiResponseFields::MD],
            ApiResponseFields::PA_RES   => base64_encode($input[ApiResponseFields::PA_REQ]),
            ApiResponseFields::TERM_URL => $input[ApiResponseFields::TERM_URL]
        ];

        $this->content($response, Action::AUTHENTICATE);

        return $response;
    }

    public function purchase($input)
    {
        $body = $this->parseRequest($input);

        $this->request($body);

        return $this->capture($input);
    }

    public function authorize($input)
    {
        parent::authorize($input);

        if ($this->isS2sFlow($input) === true)
        {
            $request = $this->parseRequest($input);

            $this->validateEnrollInput($request);

            $this->request($input);

            if ($request['Transaction']['CreditCardData']['CardNumber'] ===
                Constants::DOMESTIC_NOT_ENROLLED_CARD)
            {
                $response = $this->getNotEnrolledResponse($request);
            }
            else if ($request['Transaction']['CreditCardData']['CardNumber'] ===
                Constants::DOMESTIC_CARD_INSUFFICIENT_BALCANCE)
            {
                $response = $this->getDirectAuthorizeResponse($request);

                $response['ipgapi:ApprovalCode'] = 'random';
                $response['ipgapi:TransactionResult'] = 'declined';

                $response = $this->buildS2sResponse($response);
            }
            else if ($request['Transaction']['CreditCardData']['CardNumber'] ===
                Constants::INTERNATIONAL_CARD)
            {
                $response = $this->getDirectAuthorizeResponse($request);

                $response = $this->buildS2sResponse($response);
            }
            else
            {
                $response = $this->getEnrollResponse($request);
            }

            return $this->prepareResponse($response);
        }
        else
        {
            $this->request($input);

            $this->validateAuthorizeInput($input);

            $dateTime = Carbon::now(Timezone::IST);

            $tdate = $dateTime->getTimestamp() . random_integer(5);

            $txnDateProcessed = $dateTime->format(FirstData\Codes::DATE_TIME_FORMAT);

            $approvalCode = $this->getApprovalCode();

            $txnDateTime = $input[FirstData\ConnectRequestFields::TXN_DATE_TIME];

            $chargeTotal = $input[FirstData\ConnectRequestFields::CHARGE_TOTAL];

            $currencyCode = $input[FirstData\ConnectRequestFields::CURRENCY];

            $cardNumber = $input[FirstData\ConnectRequestFields::CARD_NUMBER];

            $paymentMethod = $input[FirstData\ConnectRequestFields::PAYMENT_METHOD];

            $scrubbedCardNumber = $this->scrub($cardNumber, $paymentMethod);

            $oid = $this->generateId('ORD0000');

            if (isset($input['oid']) === true)
            {
                $oid = $input['oid'];
            }

            $content = [
                FirstData\ConnectResponseFields::APPROVAL_CODE           => $approvalCode,
                FirstData\ConnectResponseFields::BNAME                   => $input
                                                                            [FirstData\ConnectRequestFields::NAME],
                FirstData\ConnectResponseFields::CARD_NUMBER             => $scrubbedCardNumber,
                FirstData\ConnectResponseFields::CC_BIN                  => '',
                FirstData\ConnectResponseFields::CC_BRAND                => '',
                FirstData\ConnectResponseFields::CC_COUNTRY              => '',
                FirstData\ConnectResponseFields::CHARGE_TOTAL            => $chargeTotal,
                FirstData\ConnectResponseFields::CURRENCY                => $currencyCode,
                FirstData\ConnectResponseFields::ENDPOINT_TRANSACTION_ID => '',
                FirstData\ConnectResponseFields::EXP_MONTH               => $input
                                                                            [FirstData\ConnectRequestFields::EXP_MONTH],
                FirstData\ConnectResponseFields::EXP_YEAR                => $input
                                                                            [FirstData\ConnectRequestFields::EXP_YEAR],
                FirstData\ConnectResponseFields::HASH_ALGORITHM          => $input
                                                                       [FirstData\ConnectRequestFields::HASH_ALGORITHM],
                FirstData\ConnectResponseFields::INVOICE_NUMBER          => $input
                                                                       [FirstData\ConnectRequestFields::INVOICE_NUMBER],
                FirstData\ConnectResponseFields::IPG_TRANSACTION_ID      => $this->generateId(),
                FirstData\ConnectResponseFields::ORDER_ID                => $oid,
                FirstData\ConnectResponseFields::PAYMENT_METHOD          => '',
                FirstData\ConnectResponseFields::PROCESSOR_RESPONSE_CODE => 00,
                FirstData\ConnectResponseFields::RESPONSE_CODE_3DSECURE  => '',
                FirstData\ConnectResponseFields::STATUS                  => FirstData\Status::APPROVED,
                FirstData\ConnectResponseFields::TDATE                   => $tdate,
                FirstData\ConnectResponseFields::TERMINAL_ID             => $this->generateId(),
                FirstData\ConnectResponseFields::TIMEZONE                => $input
                                                                            [FirstData\ConnectRequestFields::TIME_ZONE],
                FirstData\ConnectResponseFields::TXN_DATE_TIME           => $txnDateTime,
                FirstData\ConnectResponseFields::TXNDATE_PROCESSED       => $txnDateProcessed,
                FirstData\ConnectResponseFields::TXN_TYPE                => $input
                                                                            [FirstData\ConnectRequestFields::TXN_TYPE],
            ];

            $this->content($content, Action::CALLBACK);

            $this->setResponseHash($input, $content);

            $url = $input['responseSuccessURL'];

            $url .= '?' . http_build_query($content);

            return $url;
        }
    }

    public function capture($input)
    {
        parent::capture($input);

        $body = $this->parseRequest($input);

        $dateTime = Carbon::now(Timezone::IST);

        $content = [
            FirstData\ApiResponseFields::APPROVAL_CODE               => $this->getApprovalCode(),
            FirstData\ApiResponseFields::AVS_RESPONSE                => 'random',
            FirstData\ApiResponseFields::BRAND                       => 'MASTERCARD',
            FirstData\ApiResponseFields::COUNTRY                     => 'RANDOM_COUNTRY_CODE',
            FirstData\ApiResponseFields::COMMERCIAL_SERVICE_PROVIDER => 'random',
            FirstData\ApiResponseFields::ORDER_ID                    => $body['Transaction']['TransactionDetails']['OrderId'],
            FirstData\ApiResponseFields::IPG_TRANSACTION_ID          => random_integer(10),
            FirstData\ApiResponseFields::PAYMENT_TYPE                => 'RANDOM_PAYMENT_TYPE',
            FirstData\ApiResponseFields::PROCESSOR_APPROVAL_CODE     => '007121',
            FirstData\ApiResponseFields::PROCESSOR_RESPONSE_CODE     => '00',
            FirstData\ApiResponseFields::PROCESSOR_RESPONSE_MESSAGE  => 'Function performed error-free',
            FirstData\ApiResponseFields::TDATE                       => (string) $dateTime->getTimestamp() . random_integer(5),
            FirstData\ApiResponseFields::TDATE_FORMATTED             => (string) $dateTime->format('Y.m.d H:i:s (T)'),
            FirstData\ApiResponseFields::TERMINAL_ID                 => 'random_terminal_id',
            FirstData\ApiResponseFields::TRANSACTION_RESULT          => FirstData\Status::APPROVED,
            FirstData\ApiResponseFields::TRANSACTION_TIME            => (string) $dateTime->getTimestamp(),
        ];

        $this->content($content);

        $captureResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($captureResponse);
    }

    public function refund($input)
    {
        parent::refund($input);

        $xml = simplexml_load_string($input);

        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);

        $body = json_decode(json_encode($xmlBody), true);

        $dateTime = Carbon::now(Timezone::IST);

        $content = [
            FirstData\ApiResponseFields::APPROVAL_CODE               => $this->getApprovalCode(),
            FirstData\ApiResponseFields::AVS_RESPONSE                => "random",
            FirstData\ApiResponseFields::BRAND                       => "MASTERCARD",
            FirstData\ApiResponseFields::BUILDTIME                   => (string) $dateTime->format("Y.m.d @ H:i:s T"),
            FirstData\ApiResponseFields::COMMERCIAL_SERVICE_PROVIDER => "random",
            FirstData\ApiResponseFields::COUNTRY                     => "RANDOM_COUNTRY_CODE",
            FirstData\ApiResponseFields::IPG_TRANSACTION_ID          => random_integer(10),
            FirstData\ApiResponseFields::ORDER_ID                    => $body['Transaction']['TransactionDetails']['OrderId'],
            FirstData\ApiResponseFields::PAYMENT_TYPE                => "RANDOM_PAYMENT_TYPE",
            FirstData\ApiResponseFields::PROCESSOR_APPROVAL_CODE     => "007121",
            FirstData\ApiResponseFields::PROCESSOR_RESPONSE_CODE     => "00",
            FirstData\ApiResponseFields::PROCESSOR_RESPONSE_MESSAGE  => "Function performed error-free",
            FirstData\ApiResponseFields::REFERENCED_TDATE            => (string) $dateTime->getTimestamp(),
            FirstData\ApiResponseFields::TDATE                       => (string) $dateTime->getTimestamp() . random_integer(5),
            FirstData\ApiResponseFields::TDATE_FORMATTED             => (string) $dateTime->format("Y.m.d H:i:s (T)"),
            FirstData\ApiResponseFields::TERMINAL_ID                 => "random_terminal_id",
            FirstData\ApiResponseFields::TRANSACTION_RESULT          => "APPROVED",
            FirstData\ApiResponseFields::TRANSACTION_TIME            => (string) $dateTime->getTimestamp(),
            FirstData\ApiResponseFields::VERSION                     => "5.4.0-200",
        ];

        $this->content($content);

        $refundResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($refundResponse);
    }

    public function reverse($input)
    {
        parent::reverse($input);

        $xml = simplexml_load_string($input);

        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);

        $body = json_decode(json_encode($xmlBody), true);

        $dateTime = Carbon::now(Timezone::IST);

        $content = [
            FirstData\ApiResponseFields::APPROVAL_CODE               => $this->getApprovalCode(),
            FirstData\ApiResponseFields::IPG_TRANSACTION_ID          => random_integer(10),
            FirstData\ApiResponseFields::ORDER_ID                    => $body['Transaction']['TransactionDetails']['OrderId'],
            FirstData\ApiResponseFields::PROCESSOR_APPROVAL_CODE     => "007121",
            FirstData\ApiResponseFields::TDATE                       => (string) $dateTime->getTimestamp() . random_integer(5),
            FirstData\ApiResponseFields::TERMINAL_ID                 => "random_terminal_id",
            FirstData\ApiResponseFields::TRANSACTION_RESULT          => "APPROVED",
        ];

        $this->content($content);

        $refundResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($refundResponse);
    }

    public function verify($input)
    {
        $xml = simplexml_load_string($input);

        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('a1', true);

        $body = json_decode(json_encode($xmlBody), true);

        $inquiryOrder = $body[FirstData\ApiRequestFields::ACTION][FirstData\ApiRequestFields::INQUIRY_ORDER];

        $oid = $inquiryOrder[FirstData\ApiRequestFields::ORDER_ID];

        $this->content($content, 'verify_action');

        // to ensure backward compatibility we will be setting the content to true in s2s flow test cases and to false
        // for old flow and rupay flow..
        if ($content === true)
        {
            $soapContent = FirstData\SoapWrapper::s2sVerifyResponseWrapper($oid);
        }
        else
        {
            $soapContent = FirstData\SoapWrapper::verifyResponseWrapper($oid);
        }
        $this->content($soapContent, $this->action);

        return $this->prepareResponse($soapContent);
    }

    public function callback($input)
    {
        $authorizeRequest = $this->parseRequest($input);

        $authType = $this->decideAuthorizationType($authorizeRequest);
        if ($authType === 'pre_auth')
        {
            $this->validateActionInput($authorizeRequest, 'pre_auth');
        }
        else
        {
            $this->validateActionInput($authorizeRequest, 'authorize');
        }

        $this->content($content, Action::CALLBACK);

        $shouldFailAuthorize = $this->checkForFailedAuthorize($content);

        if ($shouldFailAuthorize === true)
        {
            $response = $this->getFailedAuthorizeResponse();

            $response = $this->buildFailedS2sResponse($response);

            return $this->prepareResponse($response, '500');
        }
        else
        {
            $content = $this->getAuthorizeResponse($authorizeRequest);

            $response = $this->buildS2sResponse($content);

            return $this->prepareResponse($response);
        }
    }

    private function decideAuthorizationType($input)
    {
        if (isset($input['Transaction']['TransactionDetails']['OrderId']) === true)
        {
            $payment = (new Payment\Repository)->find(
                $input['Transaction']['TransactionDetails']['OrderId']);

            if (($payment !== null) and
                ($payment['authentication_gateway'] === 'mpi_blade'))
            {
                return 'pre_auth';
            }
        }
        return 'old_flow';
    }

    public function verifyRefund($input)
    {
        $this->action = Action::VERIFY_REFUND;

        return $this->verify($input);
    }

    public function verifyReverse($input)
    {
        $xml = simplexml_load_string($input);

        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('a1', true);

        $body = json_decode(json_encode($xmlBody), true);

        $inquiryTransaction = $body[FirstData\ApiRequestFields::ACTION][FirstData\ApiRequestFields::INQUIRY_TRANSACTION];

        $merchantTxnId = $inquiryTransaction[FirstData\ApiRequestFields::MERCHANT_TXN_ID];

        $soapContent = FirstData\SoapWrapper::verifyReverseResponseWrapper($merchantTxnId);

        return $this->prepareResponse($soapContent);
    }

    protected function getAuthorizeResponse($request)
    {
        $dateTime = Carbon::now(Timezone::IST)->getTimestamp();

        $txnDateProcessed = Carbon::now(Timezone::IST)->format(FirstData\Codes::DATE_TIME_FORMAT);

        $transactionId =  $request['Transaction']['TransactionDetails']['IpgTransactionId'] ?? 'random';

        $content = [
            Constants::XML_IPGAPI_APPROVAL_CODE                => $this->getApprovalCode(),
            Constants::XML_AVS_RESPONSE                        => 'PPX',
            Constants::XML_IPGAPI_BRAND                        => 'VISA',
            Constants::XML_IPGAPI_COUNTRY                      => 'IND',
            Constants::XML_IPGAPI_COMMERCIAL_SERVICE_PROVIDER  => 'IMS',
            Constants::XML_IPGAPI_TRANSACTION_ID               => $transactionId,
            Constants::XML_IPGAPI_ORDER_ID                     => 'razorpay_payment_id',
            Constants::XML_IPGAPI_PAYMENT_TYPE                 => 'CREDITCARD',
            Constants::XML_PROCESSOR_RESPONSE_CODE             => '00',
            Constants::XML_PROCESSOR_APPROVAL_CODE             => '001088',
            Constants::XML_PROCESSOR_RESPONSE_MESSAGE          => 'Function performed error-free',
            Constants::XML_IPGAPI_TDATE                        => $dateTime,
            Constants::XML_IPGAPI_TERMINAL_ID                  => random_integer(8),
            Constants::XML_IPGAPI_TDATE_FORMATTED              => $txnDateProcessed,
            Constants::XML_TRANSACTION_RESULT                  => 'APPROVED',
            Constants::XML_IPGAPI_TRANSACTION_TIME             => $dateTime,
            Constants::XML_IPGAPI_SECURE_3D_RESPONSE           => [
                Constants::XML_V1_RESPONSE_CODE_3D_SECURE  => '1',
            ],
        ];

        $this->content($content, Action::CALLBACK);

        return $content;
    }

    protected function getDirectAuthorizeResponse($input)
    {
        $response = $this->getAuthorizeResponse($input);

        return $response;
    }

    protected function parseRequest(string $input)
    {
        $xml = simplexml_load_string($input);

        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);

        return json_decode(json_encode($xmlBody), true);
    }

    protected function setResponseHash($input, & $content)
    {
        $approvalCode = $content[FirstData\ConnectResponseFields::APPROVAL_CODE];

        $txnDateTime = $content[FirstData\ConnectResponseFields::TXN_DATE_TIME];

        $chargeTotal = $content[FirstData\ConnectRequestFields::CHARGE_TOTAL];

        $currencyCode = $content[FirstData\ConnectRequestFields::CURRENCY];

        $storeName = $input[FirstData\ConnectRequestFields::STORE_NAME];

        $response_hash = $this->getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeName);

        $content[FirstData\ConnectResponseFields::RESPONSE_HASH] = $response_hash;
    }

    protected function getApprovalCode()
    {
        $code = random_integer(6);

        return 'Y' . ':' . $code . ':' . random_integer(10) . ':PPX :' . random_integer(12);
    }

    protected function scrub($cardnumber, $paymentMethod)
    {
        $networkCode = 'UNKNOWN';

        if (is_null($paymentMethod) === false)
        {
            $methodMap = FirstData\PaymentMethod::METHOD_MAP;

            unset($methodMap[Card\Network::UNKNOWN]);

            $networkCode = array_flip($methodMap)[$paymentMethod];
        }

        return '(' . $networkCode . ')  ... ' . substr($cardnumber, -4);
    }

    protected function buildIpgApiOrderResponse($array)
    {
        if ($array[FirstData\ApiResponseFields::APPROVAL_CODE][0] === 'Y')
        {
            return $this->buildFairOrderResponse($array);
        }
        else
        {
            return $this->buildFaultOrderResponse($array);
        }
    }

    protected function buildFaultOrderResponse($array)
    {
        $xml = new \SimpleXMLElement(FirstData\SoapWrapper::ERROR_SOAP_SKELETON);

        foreach ($array as $key => $value)
        {
            $xml->children('SOAP-ENV', true)->Body->Fault->children()
                ->detail->children('ipgapi', true)->addChild($key, $value);
        }

        return $xml->asXML();
    }

    protected function buildFairOrderResponse($array)
    {
        $xml = new \SimpleXMLElement(FirstData\SoapWrapper::SOAP_SKELETON);

        foreach ($array as $key => $value)
        {
            $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->addChild($key, $value);
        }

        return $xml->asXML();
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/xml');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeId)
    {
        $sharedSecret = $this->getGatewayInstance()->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;

        $hash = hash(HashAlgo::SHA1, bin2hex($stringToHash));

        return $hash;
    }

    protected function generateId($prefix = '')
    {
        return $prefix . random_integer(5);
    }

    protected function buildS2sResponse($content)
    {
        $xml = $this->arrayToXml($content);

        $response = FirstData\SoapWrapper::defaultWrapper($xml, Constants::IPGAPI_ORDER_RESPONSE);

        return $response;
    }

    protected function buildFailedS2sResponse($content)
    {
        $xml = $this->arrayToXml($content);

        $response = FirstData\SoapWrapper::errorWrapper($xml);

        return $response;
    }

    protected function arrayToXml(array $array, string $wrap = null)
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

    protected function isS2sFlow($input)
    {
        return (is_array($input) === false);
    }

    protected function getNotEnrolledResponse(array $request)
    {
        $dateTime = Carbon::now(Timezone::IST)->getTimestamp();

        $txnDateProcessed = Carbon::now(Timezone::IST)->format(FirstData\Codes::DATE_TIME_FORMAT);

        $content = [
            Constants::XML_IPGAPI_APPROVAL_CODE                => 'N:87:Bad Track Data',
            Constants::XML_AVS_RESPONSE                        => 'PPX',
            Constants::XML_IPGAPI_BRAND                        => 'VISA',
            Constants::XML_IPGAPI_COMMERCIAL_SERVICE_PROVIDER  => 'random',
            Constants::XML_IPGAPI_ORDER_ID                     => $request['Transaction']['TransactionDetails']
                                                                          ['OrderId'],
            Constants::XML_PROCESSOR_APPROVAL_CODE             => '000000',
            Constants::XML_PROCESSOR_RESPONSE_CODE             => '87',
            Constants::XML_PROCESSOR_RESPONSE_MESSAGE          => 'Bad Track Data',
            Constants::XML_ERROR_MESSAGE                       => 'SGS-070087: Bad Track Data',
            Constants::XML_IPGAPI_TERMINAL_ID                  => '44000400',
            Constants::XML_TRANSACTION_RESULT                  => 'DECLINED',
            Constants::XML_IPGAPI_SECURE_3D_RESPONSE           => [
                Constants::XML_V1_RESPONSE_CODE_3D_SECURE => '7',
            ],
            Constants::TRANSACTION_ID                          => 'gatewayId',
            Constants::XML_IPGAPI_PAYMENT_TYPE                 => 'randomPaymentType',
            Constants::XML_IPGAPI_TDATE                        => $dateTime,
            Constants::XML_IPGAPI_TDATE_FORMATTED              => $txnDateProcessed,
            Constants::XML_IPGAPI_TRANSACTION_TIME             => $dateTime,
        ];

        $this->content($content, Action::AUTHORIZE);

        $response = $this->buildFailedS2sResponse($content);

        return $response;
    }

    protected function getEnrollResponse(array $request)
    {
        $dateTime = Carbon::now(Timezone::IST)->getTimestamp();

        $txnDateProcessed = Carbon::now(Timezone::IST)->format(FirstData\Codes::DATE_TIME_FORMAT);

        $this->acsUrl = $this->route->getUrl('mock_acs', ['gateway' => 'first_data']);

        $content = [
            Constants::XML_IPGAPI_APPROVAL_CODE                     => '?:waiting 3dsecure',
            Constants::XML_IPGAPI_BRAND                             => 'VISA',
            Constants::XML_IPGAPI_COUNTRY                           => 'IND',
            Constants::XML_IPGAPI_COMMERCIAL_SERVICE_PROVIDER       => 'random',
            Constants::XML_IPGAPI_ORDER_ID                          => $request['Transaction']
                                                                               ['TransactionDetails']['OrderId'],
            Constants::XML_IPGAPI_TRANSACTION_ID                    => 'gatewayId',
            Constants::XML_IPGAPI_PAYMENT_TYPE                      => 'randomPaymentType',
            Constants::XML_IPGAPI_TDATE                             => $dateTime,
            Constants::XML_IPGAPI_TDATE_FORMATTED                   => $txnDateProcessed,
            Constants::XML_IPGAPI_TRANSACTION_TIME                  => $dateTime,
            Constants::XML_IPGAPI_SECURE_3D_RESPONSE                => [
                Constants::XML_V1_SECURE_3D_VERIFICATION_RESPONSE   => [
                    Constants::XML_VERIFICATION_REDIRECT_RESPONSE   => [
                        Constants::XML_V1_ACS      => $this->acsUrl,
                        Constants::XML_V1_PA_REQ   => base64_encode($this->acsUrl),
                        Constants::XML_V1_MD       => 'gatewayPayment',
                        Constants::XML_V1_TERM_URL => 'random url'
                    ],
                ]
            ],
        ];

        $this->content($content, Action::AUTHORIZE);

        $response = $this->buildS2sResponse($content);

        return $response;
    }

    protected function getFailedAuthorizeResponse()
    {
        $dateTime = Carbon::now(Timezone::IST)->getTimestamp();

        $txnDateProcessed = Carbon::now(Timezone::IST)->format(FirstData\Codes::DATE_TIME_FORMAT);

        $content = [
            Constants::XML_IPGAPI_APPROVAL_CODE                     => 'N:-5101:3D Secure authentication failed',
            Constants::XML_IPGAPI_BRAND                             => 'VISA',
            Constants::XML_IPGAPI_COUNTRY                           => 'IND',
            Constants::XML_IPGAPI_COMMERCIAL_SERVICE_PROVIDER       => 'random',
            Constants::XML_IPGAPI_ORDER_ID                          => random_integer(14),
            Constants::XML_IPGAPI_TRANSACTION_ID                    => 'gatewayId',
            Constants::XML_IPGAPI_PAYMENT_TYPE                      => 'randomPaymentType',
            Constants::XML_ERROR_MESSAGE                            => 'SGS-005101: Transaction declined.'
                                                                        .'3D Secure authentication failed.',
            Constants::XML_IPGAPI_TRANSACTION_RESULT                => 'FAILED',
            Constants::XML_IPGAPI_TDATE                             => $dateTime,
            Constants::XML_IPGAPI_TDATE_FORMATTED                   => $txnDateProcessed,
            Constants::XML_IPGAPI_TRANSACTION_TIME                  => $dateTime,
            Constants::XML_IPGAPI_SECURE_3D_RESPONSE           => [
            Constants::XML_V1_RESPONSE_CODE_3D_SECURE  => '3',
            ],
        ];

        return $content;
    }

    protected function checkForFailedAuthorize($response)
    {
        if ((isset($response['status']) === true) and ($response['status'] === 'DECLINED'))
        {
            return true;
        }

        return false;
    }
}
