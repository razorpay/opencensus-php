<?php

namespace RZP\Gateway\FirstData;

class SoapWrapper
{
    const SOAP_SKELETON = "
        <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
            <SOAP-ENV:Header/>
            <SOAP-ENV:Body>
                <ipgapi:IPGApiOrderResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'/>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
    ";

    public static function defaultWrapper($content, $requestType)
    {
        $soapWrapper = "
            <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
                <SOAP-ENV:Header/>
                <SOAP-ENV:Body>
                    <ipgapi:$requestType xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>$content</ipgapi:$requestType>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
        ";

        return $soapWrapper;
    }

    public static function verifyResponseWrapper($oid, $timestamp, $tdates, $approvalCode, $tdateformatted)
    {
        $authTdate = $tdates['auth'];
        $refundTdate = $tdates['refund'];
        $captureTdate = $tdates['capture'];

        $soapContent = "
            <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
            <SOAP-ENV:Header/>
            <SOAP-ENV:Body>
                <ipgapi:IPGApiActionResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>
                    <ipgapi:successfully>true</ipgapi:successfully>
                    <ipgapi:OrderId>$oid</ipgapi:OrderId>
                    <v1:Billing>
                        <v1:Name>name</v1:Name>
                    </v1:Billing>
                    <v1:Shipping/>
                    <a1:TransactionValues>
                        <v1:CreditCardTxType>
                            <v1:Type>preauth</v1:Type>
                        </v1:CreditCardTxType>
                        <v1:CreditCardData>
                            <v1:CardNumber>card_number</v1:CardNumber>
                            <v1:ExpMonth>12</v1:ExpMonth>
                            <v1:ExpYear>18</v1:ExpYear>
                            <v1:Brand>VISA</v1:Brand>
                        </v1:CreditCardData>
                        <v1:Payment>
                            <v1:ChargeTotal>3</v1:ChargeTotal>
                            <v1:Currency>356</v1:Currency>
                        </v1:Payment>
                        <v1:TransactionDetails>
                            <v1:InvoiceNumber>$oid</v1:InvoiceNumber>
                            <v1:OrderId>$oid</v1:OrderId>
                            <v1:Ip>182.74.201.50</v1:Ip>
                            <v1:TDate>$authTdate</v1:TDate>
                            <v1:TransactionOrigin>ECI</v1:TransactionOrigin>
                        </v1:TransactionDetails>
                        <ipgapi:IPGApiOrderResponse>
                            <ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode>
                            <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                            <ipgapi:Brand>VISA</ipgapi:Brand>
                            <ipgapi:OrderId>$oid</ipgapi:OrderId>
                            <ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel>
                            <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                            <ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode>
                            <ipgapi:ProcessorCCVResponse/>
                            <ipgapi:ReferencedTDate>$authTdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$authTdate</ipgapi:TDate>
                            <ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted>
                            <ipgapi:TerminalID>44000025</ipgapi:TerminalID>
                        </ipgapi:IPGApiOrderResponse>
                        <a1:TraceNumber>625915</a1:TraceNumber>
                        <a1:TransactionState>AUTHORIZED</a1:TransactionState>
                        <a1:SubmissionComponent>CONNECT</a1:SubmissionComponent>
                    </a1:TransactionValues>
                    <a1:TransactionValues>
                        <v1:CreditCardTxType>
                            <v1:Type>postauth</v1:Type>
                        </v1:CreditCardTxType>
                        <v1:CreditCardData>
                            <v1:CardNumber>card_number</v1:CardNumber>
                            <v1:ExpMonth>12</v1:ExpMonth>
                            <v1:ExpYear>18</v1:ExpYear>
                            <v1:Brand>VISA</v1:Brand>
                        </v1:CreditCardData>
                        <v1:Payment>
                            <v1:ChargeTotal>3</v1:ChargeTotal>
                            <v1:Currency>356</v1:Currency>
                        </v1:Payment>
                        <v1:TransactionDetails>
                            <v1:InvoiceNumber>$oid</v1:InvoiceNumber>
                            <v1:OrderId>$oid</v1:OrderId>
                            <v1:Ip>182.74.201.50</v1:Ip>
                            <v1:TDate>$captureTdate</v1:TDate>
                            <v1:TransactionOrigin>ECI</v1:TransactionOrigin>
                        </v1:TransactionDetails>
                        <ipgapi:IPGApiOrderResponse>
                            <ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode>
                            <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                            <ipgapi:Brand>VISA</ipgapi:Brand>
                            <ipgapi:OrderId>$oid</ipgapi:OrderId>
                            <ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel>
                            <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                            <ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode>
                            <ipgapi:ProcessorCCVResponse/>
                            <ipgapi:ReferencedTDate>$captureTdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$captureTdate</ipgapi:TDate>
                            <ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted>
                            <ipgapi:TerminalID>44000025</ipgapi:TerminalID>
                        </ipgapi:IPGApiOrderResponse>
                        <a1:TraceNumber>625915</a1:TraceNumber>
                        <a1:TransactionState>CAPTURED</a1:TransactionState>
                        <a1:UserID>1</a1:UserID>
                        <a1:SubmissionComponent>API</a1:SubmissionComponent>
                    </a1:TransactionValues>
                    <a1:TransactionValues>
                        <v1:CreditCardTxType>
                            <v1:Type>credit</v1:Type>
                        </v1:CreditCardTxType>
                        <v1:CreditCardData>
                            <v1:CardNumber>card_number</v1:CardNumber>
                            <v1:ExpMonth>12</v1:ExpMonth>
                            <v1:ExpYear>18</v1:ExpYear>
                            <v1:Brand>VISA</v1:Brand>
                        </v1:CreditCardData>
                        <v1:Payment>
                            <v1:ChargeTotal>3</v1:ChargeTotal>
                            <v1:Currency>356</v1:Currency>
                        </v1:Payment>
                        <v1:TransactionDetails>
                            <v1:InvoiceNumber>$oid</v1:InvoiceNumber>
                            <v1:OrderId>$oid</v1:OrderId>
                            <v1:Ip>182.74.201.50</v1:Ip>
                            <v1:TDate>$refundTdate</v1:TDate>
                            <v1:TransactionOrigin>ECI</v1:TransactionOrigin>
                        </v1:TransactionDetails>
                        <ipgapi:IPGApiOrderResponse>
                            <ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode>
                            <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                            <ipgapi:Brand>VISA</ipgapi:Brand>
                            <ipgapi:OrderId>$oid</ipgapi:OrderId>
                            <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                            <ipgapi:ProcessorApprovalCode>014932</ipgapi:ProcessorApprovalCode>
                            <ipgapi:ProcessorCCVResponse/>
                            <ipgapi:ReferencedTDate>$refundTdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$refundTdate</ipgapi:TDate>
                            <ipgapi:TDateFormatted>$tdateformatted</ipgapi:TDateFormatted>
                            <ipgapi:TerminalID>44000025</ipgapi:TerminalID>
                        </ipgapi:IPGApiOrderResponse>
                        <a1:TraceNumber>625915</a1:TraceNumber>
                        <a1:TransactionState>SETTLED</a1:TransactionState>
                        <a1:UserID>1</a1:UserID>
                        <a1:SubmissionComponent>API</a1:SubmissionComponent>
                    </a1:TransactionValues>
                </ipgapi:IPGApiActionResponse>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
       ";

       return $soapContent;
    }
}
