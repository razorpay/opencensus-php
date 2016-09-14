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

        $timestamp = Carbon::now('Asia/Kolkata');

        $tdate = $timestamp->format('YmdHis').random_integer(5);
        $txndate_processed = $timestamp->format(FirstData\Codes::DATE_TIME_FORMAT);

        $approvalCode = $this->getApprovalCode();
        $txnDateTime = $input['txndatetime'];
        $chargeTotal = $input['chargetotal'];
        $currencyCode = $input['currency'];
        $storeId = $input['storename'];

        $response_hash = $this->getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeId);

        $oid = $this->generateId('ORD0000');
        if (isset($input['oid']) == true)
            $oid = $input['oid'];

        $content = array(
            FirstData\Entity::APPROVAL_CODE             => $approvalCode,
            FirstData\Entity::CCBIN                     => '',
            FirstData\Entity::CCBRAND                   => '',
            FirstData\Entity::CCCOUNTRY                 => '',
            FirstData\Entity::FAIL_RC                   => '',
            FirstData\Entity::FAIL_REASON               => '',
            FirstData\Entity::OID                       => $oid,
            FirstData\Entity::PROCESSOR_RESPONSE_CODE   => 00,
            FirstData\Entity::REFNUMBER                 => $this->generateId('REF0000'),
            FirstData\Entity::RESPONSE_HASH             => $response_hash,
            FirstData\Entity::STATUS                    => FirstData\Codes::STATUS_AUTHORIZED,
            FirstData\Entity::TDATE                     => $tdate,
            FirstData\Entity::TXNDATE_PROCESSED         => $txndate_processed,
        );

        $content = array_merge($content,$input);

        $this->content($content);

        $url = $input['responseSuccessURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    protected function getApprovalCode()
    {
        return 'Y'.':'.random_integer(6).':'.random_integer(10).':PPX :'.random_integer(12);
    }

    public function capture($input)
    {
        parent::capture($input);

        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $timestamp = Carbon::now('Asia/Kolkata');

        $content = array(
            "ApprovalCode"               => $this->getApprovalCode(),
            "AVSResponse"                => "random",
            "Brand"                      => "MASTERCARD",
            "Country"                    => "RANDOM_COUNTRY_CODE",
            "CommercialServiceProvider"  => "random",
            "OrderId"                    => $body['Transaction']['TransactionDetails']['OrderId'],
            "IpgTransactionId"           => random_integer(10),
            "PaymentType"                => "RANDOM_PAYMENT_TYPE",
            "ProcessorApprovalCode"      => "007121",
            "ProcessorResponseCode"      => "00",
            "ProcessorResponseMessage"   => "Function performed error-free",
            "ReferencedTDate"            => (string) $timestamp->getTimeStamp(),
            "TDate"                      => (string) $timestamp->getTimeStamp(),
            "TDateFormatted"             => (string) $timestamp->format("Y.m.d H:i:s (T)"),
            "TerminalID"                 => "random_terminal_id",
            "TransactionResult"          => "APPROVED",
            "TransactionTime"            => (string) $timestamp->getTimeStamp(),
            "Version"                    => "5.4.0-200",
            "BuildTime"                  => (string) $timestamp->format("Y.m.d @ H:i:s T"),
        );


        $captureResponse = $this->buildCaptureResponse($content);

        return $this->prepareResponse($captureResponse);
    }

    protected function buildCaptureResponse($array)
    {
        $xml = new \SimpleXMLElement("<SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'><SOAP-ENV:Header/><SOAP-ENV:Body><ipgapi:IPGApiOrderResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'></ipgapi:IPGApiOrderResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>");
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
        return '' . random_integer(5);
    }
}
