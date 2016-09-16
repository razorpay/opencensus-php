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

        $tdate = $dateTime->format('YmdHis').random_integer(5);
        $txndate_processed = $dateTime->format(FirstData\Codes::DATE_TIME_FORMAT);

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
            FirstData\Entity::CC_BIN                    => '',
            FirstData\Entity::CC_BRAND                  => '',
            FirstData\Entity::CC_COUNTRY                => '',
            FirstData\Entity::FAIL_RC                   => '',
            FirstData\Entity::FAIL_REASON               => '',
            FirstData\Entity::ORDER_ID                  => $oid,
            FirstData\Entity::PROCESSOR_RESPONSE_CODE   => 00,
            FirstData\Entity::REF_NUMBER                => $this->generateId('REF0000'),
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

    public function capture($input)
    {
        parent::capture($input);

        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('v1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $dateTime = Carbon::now('Asia/Kolkata');

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
            "ReferencedTDate"            => (string) $dateTime->getTimeStamp(),
            "TDate"                      => (string) $dateTime->getTimeStamp(),
            "TDateFormatted"             => (string) $dateTime->format("Y.m.d H:i:s (T)"),
            "TerminalID"                 => "random_terminal_id",
            "TransactionResult"          => "APPROVED",
            "TransactionTime"            => (string) $dateTime->getTimeStamp(),
            "Version"                    => "5.4.0-200",
            "BuildTime"                  => (string) $dateTime->format("Y.m.d @ H:i:s T"),
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
            "ReferencedTDate"            => (string) $dateTime->getTimeStamp(),
            "TDate"                      => (string) $dateTime->getTimeStamp(),
            "TDateFormatted"             => (string) $dateTime->format("Y.m.d H:i:s (T)"),
            "TerminalID"                 => "random_terminal_id",
            "TransactionResult"          => "APPROVED",
            "TransactionTime"            => (string) $dateTime->getTimeStamp(),
            "Version"                    => "5.4.0-200",
            "BuildTime"                  => (string) $dateTime->format("Y.m.d @ H:i:s T"),
        );


        $captureResponse = $this->buildIpgApiOrderResponse($content);

        return $this->prepareResponse($captureResponse);
    }

    public function verify($input)
    {
        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('a1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $oid = $body['Action']['InquiryOrder']['OrderId'];
        $dateTime = Carbon::now('Asia/Kolkata');
        $tdate = (string) $dateTime->getTimeStamp();
        $approvalCode = $this->getApprovalCode();
        $tdateformatted = (string) $dateTime->format("Y.m.d H:i:s (T)");

        $soapContent = FirstData\SoapWrapper::verifyResponseWrapper($oid, $dateTime, $tdate, $approvalCode, $tdateformatted);

        return $this->prepareResponse($soapContent);
    }

    protected function getApprovalCode()
    {
        return 'Y'.':'.random_integer(6).':'.random_integer(10).':PPX :'.random_integer(12);
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
