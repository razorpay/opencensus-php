<?php

$xml = <<<VERIFYXML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Report SYSTEM "https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd">

<Report xmlns="https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd" Name="Transaction Detail" Version="1.1" MerchantID="{$input['merchantID']}" ReportStartDate="2016-10-21 23:53:27.977+05:30" ReportEndDate="2016-10-21 23:53:27.977+05:30">
  <Requests>
    <Request MerchantReferenceNumber="{$input['merchantReferenceNumber']}" RequestDate="2016-08-05T18:48:27+05:30" RequestID="{$content['ccCaptureService']['requestId']}" SubscriptionID="" Source="SOAP Toolkit API">
      <BillTo>
        <FirstName />
        <LastName />
        <City />
        <Email />
        <Country />
        <Phone />
      </BillTo>
      <PaymentMethod>
        <Card>
          <AccountSuffix>1717</AccountSuffix>
          <ExpirationMonth>4</ExpirationMonth>
          <ExpirationYear>2019</ExpirationYear>
          <CardType>MasterCard</CardType>
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>{$content['ccCaptureService']['amount']}</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_arc">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Service was successful</RMsg>
        </ApplicationReply>
        <ApplicationReply Name="ics_bill">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Request was processed successfully.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <PaymentRequestID>{$content['ccCaptureService']['requestId']}</PaymentRequestID>
        <PaymentProcessor>vdchdfc</PaymentProcessor>
        <Amount>{$content['ccCaptureService']['amount']}</Amount>
        <CurrencyCode>INR</CurrencyCode>
        <TotalTaxAmount>0.00</TotalTaxAmount>
        <AuthorizationCode>{$content['ccAuthService']['authCode']}</AuthorizationCode>
        <AVSResult>G</AVSResult>
        <AVSResultMapped>G</AVSResultMapped>
        <CVResult>M</CVResult>
      </PaymentData>
    </Request>
    <Request MerchantReferenceNumber="{$input['merchantReferenceNumber']}" RequestDate="2016-08-05T18:48:23+05:30" RequestID="{$content['ccAuthService']['requestId']}" SubscriptionID="" Source="SOAP Toolkit API">
      <BillTo>
        <FirstName>NOREAL</FirstName>
        <LastName>NAME</LastName>
        <Address1>1295 Charleston Rd</Address1>
        <City>Mountain View</City>
        <State>CA</State>
        <Zip>94043</Zip>
        <Email>payment-user@razorpay.com</Email>
        <Country>US</Country>
        <Phone />
      </BillTo>
      <PaymentMethod>
        <Card>
          <AccountSuffix>1717</AccountSuffix>
          <ExpirationMonth>4</ExpirationMonth>
          <ExpirationYear>2019</ExpirationYear>
          <CardType>MasterCard</CardType>
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>1731.00</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_arc">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Service was successful</RMsg>
        </ApplicationReply>
        <ApplicationReply Name="ics_auth">
          <RCode>1</RCode>
          <RFlag>{$content['ccAuthService']['RFlag']}</RFlag>
          <RMsg>Request was processed successfully.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <PaymentRequestID>{$content['ccAuthService']['requestId']}</PaymentRequestID>
        <PaymentProcessor>vdchdfc</PaymentProcessor>
        <Amount>{$content['ccAuthService']['amount']}</Amount>
        <CurrencyCode>INR</CurrencyCode>
        <TotalTaxAmount>0.00</TotalTaxAmount>
        <AuthorizationCode>146572</AuthorizationCode>
        <AVSResult>G</AVSResult>
        <AVSResultMapped>G</AVSResultMapped>
        <CVResult>M</CVResult>
        <PayerAuthenticationInfo>
          <ECI>{$content['ccAuthService']['eci']}</ECI>
          <AAV_CAVV>jAt2OkgfBuDnCBAAAJDIBBkAAAA=</AAV_CAVV>
          <XID>bWJWb1RsYzN1dEpTVUVvQ1NBMDA=</XID>
        </PayerAuthenticationInfo>
      </PaymentData>
    </Request>
    <Request MerchantReferenceNumber="{$input['merchantReferenceNumber']}" RequestDate="2016-08-05T18:48:22+05:30" RequestID="{$content['ccAuthService']['requestId']}" SubscriptionID="" Source="SOAP Toolkit API">
      <BillTo>
        <FirstName>NOREAL</FirstName>
        <LastName>NAME</LastName>
        <Address1>1295 Charleston Rd</Address1>
        <City>Mountain View</City>
        <State>CA</State>
        <Zip>94043</Zip>
        <Email>payment-user@goodbox.in</Email>
        <Country>US</Country>
        <Phone />
      </BillTo>
      <PaymentMethod>
        <Card>
          <AccountSuffix />
          <ExpirationMonth />
          <ExpirationYear />
          <CardType />
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>{$content['ccAuthService']['amount']}</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_pa_validate">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>ics_pa_validate service was successful</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <CurrencyCode>USD</CurrencyCode>
        <PayerAuthenticationInfo>
          <ECI>{$content['ccAuthService']['eci']}</ECI>
          <AAV_CAVV>jAt2OkgfBuDnCBAAAJDIBBkAAAA=</AAV_CAVV>
          <XID>bWJWb1RsYzN1dEpTVUVvQ1NBMDA=</XID>
        </PayerAuthenticationInfo>
      </PaymentData>
    </Request>
    <Request MerchantReferenceNumber="{$input['merchantReferenceNumber']}" RequestDate="2016-08-05T18:48:00+05:30" RequestID="{$content['payerAuthEnrollService']['requestId']}" SubscriptionID="" Source="SOAP Toolkit API">
      <BillTo>
        <FirstName />
        <LastName />
        <City />
        <Email />
        <Country />
        <Phone />
      </BillTo>
      <PaymentMethod>
        <Card>
          <AccountSuffix />
          <ExpirationMonth />
          <ExpirationYear />
          <CardType />
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>{$content['payerAuthEnrollService']['amount']}</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_pa_enroll">
          <RCode>0</RCode>
          <RFlag>DAUTHENTICATE</RFlag>
          <RMsg>The cardholder is enrolled in Payer Authentication.  Please authenticate before proceeding with authorization.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <CurrencyCode>USD</CurrencyCode>
        <PayerAuthenticationInfo>
          <XID>bWJWb1RsYzN1dEpTVUVvQ1NBMDA=</XID>
        </PayerAuthenticationInfo>
      </PaymentData>
    </Request>
  </Requests>
</Report>
VERIFYXML;

return trim($xml);
