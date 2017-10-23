<?php

namespace RZP\Gateway\FirstData;

class SoapWrapper
{
    const SOAP_SKELETON = "
        <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
            <SOAP-ENV:Header/>
            <SOAP-ENV:Body>
                <ipgapi:IPGApiOrderResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1'
                    xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi'
                    xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/'
                    xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'/>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
    ";

    const ERROR_SOAP_SKELETON = "
        <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
            <SOAP-ENV:Header/>
            <SOAP-ENV:Body>
                <SOAP-ENV:Fault>
                    <faultcode>SOAP-ENV:Client</faultcode>
                    <faultstring xml:lang='en'>ProcessingException</faultstring>
                    <detail>
                        <ipgapi:IPGApiOrderResponse
                            xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1'
                            xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi'
                            xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/'
                            xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'/>
                    </detail>
                </SOAP-ENV:Fault>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
    ";

    const ERROR_ACTION_RESPONSE = "
        <SOAP-ENV:Envelope
            xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
            <SOAP-ENV:Header/>
            <SOAP-ENV:Body>
                <ipgapi:IPGApiActionResponse
                    xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi'
                    xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1'
                    xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/'
                    xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>
                    <ipgapi:successfully>false</ipgapi:successfully>
                </ipgapi:IPGApiActionResponse>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
    ";

    public static function defaultWrapper($content, $requestType)
    {
        $soapWrapper = "
            <?xml version='1.0' encoding='UTF-8'?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
                <SOAP-ENV:Header/>
                <SOAP-ENV:Body>
                    <ipgapi:$requestType
                        xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1'
                        xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi'
                        xmlns:pay_1_0_0='http://api.clickandbuy.com/webservices/pay_1_0_0/'
                        xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>$content</ipgapi:$requestType>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
        ";

        return trim($soapWrapper);
    }

    public static function verifyRefundResponseWrapper(string $merchantTxnId)
    {
        $soapContent = "
            <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
                <SOAP-ENV:Header/>
                <SOAP-ENV:Body>
                    <ipgapi:IPGApiActionResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>
                        <ipgapi:successfully>true</ipgapi:successfully>
                        <a1:TransactionValues>
                            <v1:CreditCardTxType>
                                <v1:Type>credit</v1:Type>
                            </v1:CreditCardTxType>
                            <v1:CreditCardData>
                                <v1:CardNumber>460133...0386</v1:CardNumber>
                                <v1:ExpMonth>06</v1:ExpMonth>
                                <v1:ExpYear>21</v1:ExpYear>
                                <v1:Brand>VISA</v1:Brand>
                            </v1:CreditCardData>
                            <v1:Payment>
                                <v1:ChargeTotal>1498</v1:ChargeTotal>
                                <v1:Currency>356</v1:Currency>
                            </v1:Payment>
                            <v1:TransactionDetails>
                                <v1:InvoiceNumber>88PebpcMHnE3ub</v1:InvoiceNumber>
                                <v1:OrderId>88PebpcMHnE3ub</v1:OrderId>
                                <v1:MerchantTransactionId>$merchantTxnId</v1:MerchantTransactionId>
                                <v1:TDate>1498764075</v1:TDate>
                            </v1:TransactionDetails>
                            <ipgapi:IPGApiOrderResponse>
                                <ipgapi:ApprovalCode>Y:883432:5344412350:PPX :718019165231</ipgapi:ApprovalCode>
                                <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                                <ipgapi:Brand>VISA</ipgapi:Brand>
                                <ipgapi:Country>IND</ipgapi:Country>
                                <ipgapi:OrderId>88PebpcMHnE3ub</ipgapi:OrderId>
                                <ipgapi:IpgTransactionId>65344412350</ipgapi:IpgTransactionId>
                                <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                                <ipgapi:ProcessorApprovalCode>883432</ipgapi:ProcessorApprovalCode>
                                <ipgapi:ProcessorCCVResponse/>
                                <ipgapi:ReferencedTDate>1498764075</ipgapi:ReferencedTDate>
                                <ipgapi:TDate>1498764075</ipgapi:TDate>
                                <ipgapi:TDateFormatted>2017.06.30 00:51:15 (IST)</ipgapi:TDateFormatted>
                                <ipgapi:TerminalID>00226943</ipgapi:TerminalID>
                            </ipgapi:IPGApiOrderResponse>
                            <a1:TraceNumber>718019</a1:TraceNumber>
                            <a1:TransactionState>CAPTURED</a1:TransactionState>
                            <a1:UserID>1</a1:UserID>
                            <a1:SubmissionComponent>API</a1:SubmissionComponent>
                        </a1:TransactionValues>
                    </ipgapi:IPGApiActionResponse>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
        ";

        return $soapContent;
    }

    public static function verifyReverseResponseWrapper(string $merchantTxnId)
    {
        $soapContent = "
            <SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'>
                <SOAP-ENV:Header/>
                <SOAP-ENV:Body>
                    <ipgapi:IPGApiActionResponse xmlns:a1='http://ipg-online.com/ipgapi/schemas/a1' xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>
                        <ipgapi:successfully>true</ipgapi:successfully>
                        <a1:TransactionValues>
                            <v1:CreditCardTxType>
                                <v1:Type>preauth</v1:Type>
                            </v1:CreditCardTxType>
                            <v1:CreditCardData>
                                <v1:CardNumber>524193...6003</v1:CardNumber>
                                <v1:ExpMonth>04</v1:ExpMonth>
                                <v1:ExpYear>20</v1:ExpYear>
                                <v1:Brand>MASTERCARD</v1:Brand>
                            </v1:CreditCardData>
                            <v1:Payment>
                                <v1:ChargeTotal>11728</v1:ChargeTotal>
                                <v1:Currency>356</v1:Currency>
                            </v1:Payment>
                            <v1:TransactionDetails>
                                <v1:InvoiceNumber>88HjdtRg3zHNlD</v1:InvoiceNumber>
                                <v1:OrderId>88HjdtRg3zHNlD</v1:OrderId>
                                <v1:MerchantTransactionId>$merchantTxnId</v1:MerchantTransactionId>
                                <v1:TDate>1498640277</v1:TDate>
                            </v1:TransactionDetails>
                            <ipgapi:IPGApiOrderResponse>
                                <ipgapi:ApprovalCode>Y:815527:5341037593:PPX :062808398661</ipgapi:ApprovalCode>
                                <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                                <ipgapi:Brand>MASTERCARD</ipgapi:Brand>
                                <ipgapi:Country>IND</ipgapi:Country>
                                <ipgapi:OrderId>88HjdtRg3zHNlD</ipgapi:OrderId>
                                <ipgapi:IpgTransactionId>65341037593</ipgapi:IpgTransactionId>
                                <ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel>
                                <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                                <ipgapi:ProcessorApprovalCode>815527</ipgapi:ProcessorApprovalCode>
                                <ipgapi:ProcessorCCVResponse/>
                                <ipgapi:ReferencedTDate>1498640277</ipgapi:ReferencedTDate>
                                <ipgapi:TDate>1498640277</ipgapi:TDate>
                                <ipgapi:TDateFormatted>2017.06.28 14:27:57 (IST)</ipgapi:TDateFormatted>
                                <ipgapi:TerminalID>00224579</ipgapi:TerminalID>
                            </ipgapi:IPGApiOrderResponse>
                            <a1:TraceNumber>062808</a1:TraceNumber>
                            <a1:TransactionState>AUTHORIZED</a1:TransactionState>
                            <a1:SubmissionComponent>CONNECT</a1:SubmissionComponent>
                        </a1:TransactionValues>
                    </ipgapi:IPGApiActionResponse>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
        ";

        return $soapContent;
    }

    public static function verifyResponseWrapper($oid, $timestamp, $tdate, $approvalCode, $tdateformatted)
    {
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
                            <v1:CardNumber>scrubbed_card_number</v1:CardNumber>
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
                            <v1:TDate>$tdate</v1:TDate>
                            <v1:TransactionOrigin>ECI</v1:TransactionOrigin>
                        </v1:TransactionDetails>
                        <ipgapi:IPGApiOrderResponse>
                            <ipgapi:ApprovalCode>$approvalCode</ipgapi:ApprovalCode>
                            <ipgapi:AVSResponse>PPX</ipgapi:AVSResponse>
                            <ipgapi:Brand>VISA</ipgapi:Brand>
                            <ipgapi:OrderId>$oid</ipgapi:OrderId>
                            <ipgapi:PayerSecurityLevel>1</ipgapi:PayerSecurityLevel>
                            <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                            <ipgapi:ProcessorApprovalCode>543210</ipgapi:ProcessorApprovalCode>
                            <ipgapi:ProcessorCCVResponse/>
                            <ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$tdate</ipgapi:TDate>
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
                            <v1:CardNumber>scrubbed_card_number</v1:CardNumber>
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
                            <v1:TDate>$tdate</v1:TDate>
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
                            <ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$tdate</ipgapi:TDate>
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
                            <v1:CardNumber>scrubbed_card_number</v1:CardNumber>
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
                            <v1:TDate>$tdate</v1:TDate>
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
                            <ipgapi:ReferencedTDate>$tdate</ipgapi:ReferencedTDate>
                            <ipgapi:TDate>$tdate</ipgapi:TDate>
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
