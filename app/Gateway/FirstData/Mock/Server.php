<?php

namespace RZP\Gateway\FirstData\Mock;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new FirstData\Repository;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $dateTime = Carbon::now('Asia/Kolkata');

        $tdate = $dateTime->getTimeStamp().random_integer(5);
        $txndate_processed = $dateTime->format(FirstData\Codes::DATE_TIME_FORMAT);

        $approvalCode = $this->getApprovalCode();
        $txnDateTime = $input[FirstData\ConnectRequestFields::TXN_DATE_TIME];
        $chargeTotal = $input[FirstData\ConnectRequestFields::CHARGE_TOTAL];
        $currencyCode = $input[FirstData\ConnectRequestFields::CURRENCY];
        $storeId = $input[FirstData\ConnectRequestFields::STORE_NAME];
        $cardnumber = $input[FirstData\ConnectRequestFields::CARD_NUMBER];
        $paymentMethod = $input[FirstData\ConnectRequestFields::PAYMENT_METHOD];
        $scrubbed_cardnumber = $this->scrub($cardnumber, $paymentMethod);

        $response_hash = $this->getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeId);

        $oid = $this->generateId('ORD0000');
        if (isset($input['oid']) == true)
            $oid = $input['oid'];

        $content = array(
            FirstData\ConnectResponseFields::APPROVAL_CODE             => $approvalCode,
            FirstData\ConnectResponseFields::BNAME                     => $input[FirstData\ConnectRequestFields::NAME],
            FirstData\ConnectResponseFields::CARD_NUMBER               => $scrubbed_cardnumber,
            FirstData\ConnectResponseFields::CC_BIN                    => '',
            FirstData\ConnectResponseFields::CC_BRAND                  => '',
            FirstData\ConnectResponseFields::CC_COUNTRY                => '',
            FirstData\ConnectResponseFields::CHARGE_TOTAL              => $chargeTotal,
            FirstData\ConnectResponseFields::CURRENCY                  => $currencyCode,
            FirstData\ConnectResponseFields::ENDPOINT_TRANSACTION_ID   => '',
            FirstData\ConnectResponseFields::EXP_MONTH                 => $input[FirstData\ConnectRequestFields::EXP_MONTH],
            FirstData\ConnectResponseFields::EXP_YEAR                  => $input[FirstData\ConnectRequestFields::EXP_YEAR],
            FirstData\ConnectResponseFields::FAIL_RC                   => '',
            FirstData\ConnectResponseFields::FAIL_REASON               => '',
            FirstData\ConnectResponseFields::HASH_ALGORITHM            => $input[FirstData\ConnectRequestFields::HASH_ALGORITHM],
            FirstData\ConnectResponseFields::INVOICE_NUMBER            => $input[FirstData\ConnectRequestFields::INVOICE_NUMBER],
            FirstData\ConnectResponseFields::IPG_TRANSACTION_ID        => $this->generateId(),
            FirstData\ConnectResponseFields::ORDER_ID                  => $oid,
            FirstData\ConnectResponseFields::PAYMENT_METHOD            => '',
            FirstData\ConnectResponseFields::PROCESSOR_RESPONSE_CODE   => 00,
            FirstData\ConnectResponseFields::RESPONSE_CODE_3DSECURE    => '',
            FirstData\ConnectResponseFields::RESPONSE_HASH             => $response_hash,
            FirstData\ConnectResponseFields::STATUS                    => FirstData\Status::APPROVED,
            FirstData\ConnectResponseFields::TDATE                     => $tdate,
            FirstData\ConnectResponseFields::TERMINAL_ID               => $this->generateId(),
            FirstData\ConnectResponseFields::TIMEZONE                  => $input[FirstData\ConnectRequestFields::TIME_ZONE],
            FirstData\ConnectResponseFields::TXN_DATE_TIME             => $txnDateTime,
            FirstData\ConnectResponseFields::TXNDATE_PROCESSED         => $txndate_processed,
            FirstData\ConnectResponseFields::TXN_TYPE                  => $input[FirstData\ConnectRequestFields::TXN_TYPE],
        );

        $this->content($content);

        $url = $input['responseSuccessURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function capture($input)
    {
        parent::capture($input);

        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $dateTime = Carbon::now('Asia/Kolkata');

        $content = array(
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
            FirstData\ApiResponseFields::REFERENCED_TDATE            => (string) $dateTime->getTimeStamp(),
            FirstData\ApiResponseFields::TDATE                       => (string) $dateTime->getTimeStamp().random_integer(5),
            FirstData\ApiResponseFields::TDATE_FORMATTED             => (string) $dateTime->format("Y.m.d H:i:s (T)"),
            FirstData\ApiResponseFields::TERMINAL_ID                 => "random_terminal_id",
            FirstData\ApiResponseFields::TRANSACTION_RESULT          => FirstData\Status::APPROVED,
            FirstData\ApiResponseFields::TRANSACTION_TIME            => (string) $dateTime->getTimeStamp(),
            FirstData\ApiResponseFields::VERSION                     => "5.4.0-200",
        );

        $captureResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($captureResponse);
    }

    public function refund($input)
    {
        parent::refund($input);

        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $dateTime = Carbon::now('Asia/Kolkata');

        $content = array(
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
            FirstData\ApiResponseFields::REFERENCED_TDATE            => (string) $dateTime->getTimeStamp(),
            FirstData\ApiResponseFields::TDATE                       => (string) $dateTime->getTimeStamp().random_integer(5),
            FirstData\ApiResponseFields::TDATE_FORMATTED             => (string) $dateTime->format("Y.m.d H:i:s (T)"),
            FirstData\ApiResponseFields::TERMINAL_ID                 => "random_terminal_id",
            FirstData\ApiResponseFields::TRANSACTION_RESULT          => "APPROVED",
            FirstData\ApiResponseFields::TRANSACTION_TIME            => (string) $dateTime->getTimeStamp(),
            FirstData\ApiResponseFields::VERSION                     => "5.4.0-200",
        );


        $captureResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($captureResponse);
    }

    public function verify($input)
    {
        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('a1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $inquiryOrder = $body[FirstData\ApiRequestFields::ACTION][FirstData\ApiRequestFields::INQUIRY_ORDER];

        $oid = $inquiryOrder[FirstData\ApiRequestFields::ORDER_ID];

        $dateTime = Carbon::now('Asia/Kolkata');


        $authGatewayPayment = (new FirstData\Repository)->findByPaymentIdAndActionOrFail($oid, Base\Action::AUTHORIZE);
        $tdates['auth'] = $authGatewayPayment->getTdate();
        $captureGatewayPayment = (new FirstData\Repository)->findByPaymentIdAndActionOrFail($oid, Base\Action::CAPTURE);
        $tdates['capture'] = $captureGatewayPayment->getTdate();
        $refundGatewayPayment = (new FirstData\Repository)->findByPaymentIdAndActionOrFail($oid, Base\Action::REFUND);
        $tdates['refund'] = $refundGatewayPayment->getTdate();

        $tdate = (string) $dateTime->getTimeStamp();
        $approvalCode = $this->getApprovalCode();
        $tdateformatted = (string) $dateTime->format("Y.m.d H:i:s (T)");

        $soapContent = FirstData\SoapWrapper::verifyResponseWrapper($oid, $dateTime, $tdates, $approvalCode, $tdateformatted);

        return $this->prepareResponse($soapContent);
    }

    protected function getApprovalCode()
    {
        return 'Y'.':'.random_integer(6).':'.random_integer(10).':PPX :'.random_integer(12);
    }

    protected function scrub($cardnumber, $paymentMethod)
    {
        return '('.array_flip(FirstData\Mapping::PAYMENT_METHOD_CODES)[$paymentMethod].') ... '.substr($cardnumber,-4);
    }

    protected function buildIpgApiOrderResponse($array)
    {
        $xml = new \SimpleXMLElement(FirstData\SoapWrapper::SOAP_SKELETON);

        foreach ($array as $key => $value)
        {
            $xml->children('SOAP-ENV', true)->Body->children('ipgapi',true)->addChild($key,$value);
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
        $hash_algorithm = strtolower(FirstData\Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    protected function generateId($prefix = '')
    {
        return $prefix . random_integer(5);
    }
}
