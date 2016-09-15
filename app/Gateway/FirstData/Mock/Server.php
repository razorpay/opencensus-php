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

    public function verify($input)
    {
        $xml   = simplexml_load_string($input);
        $xmlBody = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true)->children('a1', true);
        $body = json_decode(json_encode($xmlBody), true);

        $oid = $body['Action']['InquiryOrder']['OrderId'];
        $timestamp = Carbon::now('Asia/Kolkata');
        $tdate = (string) $timestamp->getTimeStamp();
        $approvalCode = $this->getApprovalCode();
        $tdateformatted = (string) $timestamp->format("Y.m.d H:i:s (T)");

        $soapContent="<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\"><SOAP-ENV:Header/><SOAP-ENV:Body><ipgapi:IPGApiActionResponse xmlns:ipgapi=\"http://ipg-online.com/ipgapi/schemas/ipgapi\" xmlns:a1=\"http://ipg-online.com/ipgapi/schemas/a1\" xmlns:pay_1_0_0=\"http://api.clickandbuy.com/webservices/pay_1_0_0/\" xmlns:v1=\"http://ipg-online.com/ipgapi/schemas/v1\"><ipgapi:successfully>true</ipgapi:successfully><ipgapi:OrderId>$oid</ipgapi:OrderId><v1:Billing><v1:Name>name</v1:Name></v1:Billing><v1:Shipping/><a1:TransactionValues><v1:CreditCardTxType><v1:Type>preauth</v1:Type></v1:CreditCardTxType><v1:CreditCardData><v1:CardNumber>card_number</v1:CardNumber><v1:ExpMonth>12</v1:ExpMonth><v1:ExpYear>18</v1:ExpYear><v1:Brand>VISA</v1:Brand></v1:CreditCardData><v1:Payment><v1:ChargeTotal>3</v1:ChargeTotal><v1:Currency>356</v1:Currency></v1:Payment><v1:TransactionDetails><v1:InvoiceNumber>$oid</v1:InvoiceNumber><v1:OrderId>$oid</v1:OrderId><v1:Ip>182.74.201.50</v1:Ip><v1:TDate>$tdate</v1:TDate><v1:TransactionOrigin>ECI</v1:TransactionOrigin></v1:TransactionDetails><ipgapi:IPGApiOrderResponse><ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode><ipgapi:AVSResponse>PPX</ipgapi:AVSResponse><ipgapi:Brand>VISA</ipgapi:Brand><ipgapi:OrderId>$oid</ipgapi:OrderId><ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel><ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType><ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode><ipgapi:ProcessorCCVResponse> </ipgapi:ProcessorCCVResponse><ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate><ipgapi:TDate>$tdate</ipgapi:TDate><ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted><ipgapi:TerminalID>44000025</ipgapi:TerminalID></ipgapi:IPGApiOrderResponse><a1:TraceNumber>625915</a1:TraceNumber><a1:TransactionState>AUTHORIZED</a1:TransactionState><a1:SubmissionComponent>CONNECT</a1:SubmissionComponent></a1:TransactionValues><a1:TransactionValues><v1:CreditCardTxType><v1:Type>postauth</v1:Type></v1:CreditCardTxType><v1:CreditCardData><v1:CardNumber>card_number</v1:CardNumber><v1:ExpMonth>12</v1:ExpMonth><v1:ExpYear>18</v1:ExpYear><v1:Brand>VISA</v1:Brand></v1:CreditCardData><v1:Payment><v1:ChargeTotal>3</v1:ChargeTotal><v1:Currency>356</v1:Currency></v1:Payment><v1:TransactionDetails><v1:InvoiceNumber>$oid</v1:InvoiceNumber><v1:OrderId>$oid</v1:OrderId><v1:Ip>182.74.201.50</v1:Ip><v1:TDate>1473952020</v1:TDate><v1:TransactionOrigin>ECI</v1:TransactionOrigin></v1:TransactionDetails><ipgapi:IPGApiOrderResponse><ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode><ipgapi:AVSResponse>PPX</ipgapi:AVSResponse><ipgapi:Brand>VISA</ipgapi:Brand><ipgapi:OrderId>$oid</ipgapi:OrderId><ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel><ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType><ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode><ipgapi:ProcessorCCVResponse> </ipgapi:ProcessorCCVResponse><ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate><ipgapi:TDate>1473952020</ipgapi:TDate><ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted><ipgapi:TerminalID>44000025</ipgapi:TerminalID></ipgapi:IPGApiOrderResponse><a1:TraceNumber>625915</a1:TraceNumber><a1:TransactionState>CAPTURED</a1:TransactionState><a1:UserID>1</a1:UserID><a1:SubmissionComponent>API</a1:SubmissionComponent></a1:TransactionValues><a1:TransactionValues><v1:CreditCardTxType><v1:Type>credit</v1:Type></v1:CreditCardTxType><v1:CreditCardData><v1:CardNumber>card_number</v1:CardNumber><v1:ExpMonth>12</v1:ExpMonth><v1:ExpYear>18</v1:ExpYear><v1:Brand>VISA</v1:Brand></v1:CreditCardData><v1:Payment><v1:ChargeTotal>3</v1:ChargeTotal><v1:Currency>356</v1:Currency></v1:Payment><v1:TransactionDetails><v1:InvoiceNumber>$oid</v1:InvoiceNumber><v1:OrderId>$oid</v1:OrderId><v1:Ip>182.74.201.50</v1:Ip><v1:TDate>$tdate</v1:TDate><v1:TransactionOrigin>ECI</v1:TransactionOrigin></v1:TransactionDetails><ipgapi:IPGApiOrderResponse><ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode><ipgapi:AVSResponse>PPX</ipgapi:AVSResponse><ipgapi:Brand>VISA</ipgapi:Brand><ipgapi:OrderId>$oid</ipgapi:OrderId><ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType><ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode><ipgapi:ProcessorCCVResponse> </ipgapi:ProcessorCCVResponse><ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate><ipgapi:TDate>$tdate</ipgapi:TDate><ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted><ipgapi:TerminalID>44000025</ipgapi:TerminalID></ipgapi:IPGApiOrderResponse><a1:TraceNumber>625915</a1:TraceNumber><a1:TransactionState>CAPTURED</a1:TransactionState><a1:UserID>1</a1:UserID><a1:SubmissionComponent>API</a1:SubmissionComponent></a1:TransactionValues></ipgapi:IPGApiActionResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>";

        return $this->prepareResponse($soapContent);
    }

    protected function getApprovalCode()
    {
        return 'Y'.':'.random_integer(6).':'.random_integer(10).':PPX :'.random_integer(12);
    }

    protected function wrapSoap($content)
    {
        $soapWrapper = "<SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'><SOAP-ENV:Header/><SOAP-ENV:Body><ipgapi:IPGApiActionResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>$content</ipgapi:IPGApiActionResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>";

        return $soapWrapper;
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
