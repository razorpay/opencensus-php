<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\App;
use RZP\Http;
use RZP\Gateway\Cybersource;

class Server extends Base\Mock\Server
{
    protected $repo;

    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        $response = $this->getEnrollAuthorizeResponse($input);

        $this->content($response);

        return $response;
    }

    public function capture($input)
    {
        $this->validateActionInput($input, 'capture');

        return $this->getCaptureResponse($input);
    }

    public function enroll($input)
    {
        $this->validateActionInput($input, 'enroll');

        return $this->getEnrollResponse($input);
    }

    public function refund($input)
    {
        $this->validateActionInput($input, 'refund');

        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;
        $response['requestID'] = '4661549029556297301014';
        $response['merchantReferenceCode'] = 'razorpay';

        $ccCreditReply = array();
        $ccCreditReply['reconciliationID'] = 'razorpay';

        $response['ccCreditReply'] = $ccCreditReply;
        $response['ccCreditReply'] = $ccCreditReply;

        return $response;
    }

    public function verify($input)
    {
        $this->validateActionInput($input, 'verify');

        assertTrue(isset($this->mockRequest['options']['auth'][0]) and
                is_string($this->mockRequest['options']['auth'][0]));

        assertTrue(isset($this->mockRequest['options']['auth'][1]) and
                is_string($this->mockRequest['options']['auth'][1]));

        $verifyResponseBody = $this->createVerifyResponse($input);

        return $this->makeResponse($verifyResponseBody);
    }

    public function authValidate($input)
    {
        $this->validateActionInput($input, 'auth_validate');

        return $this->getAuthEnrolledRequest($input);
    }

    public function getCaptureResponse($input)
    {
        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;
        $response['requestID'] = '4661468455476856801016';

        $ccCaptureReply = array();
        $ccCaptureReply['reconciliationID'] = $input['merchantReferenceCode'];;

        $response['ccCaptureReply'] = $ccCaptureReply;

        return $response;
     }

     protected function getAuthEnrolledRequest($input)
     {
        $response = array();

        $response['decision'] = 'ACCEPT';
        $response['reasonCode'] = Cybersource\Result::SUCCESS;

        $payerAuthValidateReply = array();
        $payerAuthValidateReply['eci'] = '05';
        $payerAuthValidateReply['xid'] = 'TktUb3hwZVp0eTMxcTh5UlZUODA=';
        $payerAuthValidateReply['paresStatus'] = 'Y';
        $payerAuthValidateReply['commerceIndicator'] = 'Internet';
        $payerAuthValidateReply['cavv'] = '1';

        if ($input['card']['accountNumber'] === '4111460212312338')
        {
            $payerAuthValidateReply['eci'] = '07';
            $payerAuthValidateReply['paresStatus'] = 'U';
            $payerAuthValidateReply['authenticationStatusMessage'] = 'Issuer unable to perform authentication';
        }

        $response['payerAuthValidateReply'] = $payerAuthValidateReply;

        return $response;
     }

    protected function getEnrollAuthorizeResponse($input)
    {
        if ((isset($input['ccAuthService']['commerceIndicator']) === true) and
            ($input['ccAuthService']['commerceIndicator'] === 'recurring'))
        {
            return [
                'merchantReferenceCode' => "64VOYMNaWmKfQv",
                'requestID' => "4707408464166875104008",
                'decision' => "ACCEPT",
                'reasonCode' => 100,
                'requestToken' => "Ahj/7wSR/mDU4ntLegYQ5lmjdg3aMHDRs0Ytmzhu1YsGjBg4S36dGFvgFLfp0YW+WQPkYToZNJMt0gPFojgGkf5g1OJ7S3oGEAAA/wYA",
                'purchaseTotals' => [
                    'currency' => "INR",
                ],
                'ccAuthReply' => [
                    'reasonCode' => 100,
                    'amount' => "1.00",
                    'authorizationCode' => "831000",
                    'avsCode' => "Y",
                    'avsCodeRaw' => "Y",
                    'authorizedDateTime' => "2016-08-09T11:07:26Z",
                    'processorResponse' => "00",
                    'reconciliationID' => "4707408464166875104008",
                    'merchantAdviceCode' => "01",
                    'merchantAdviceCodeRaw' => "M001",
                    'cavvResponseCode' => "2",
                    'cavvResponseCodeRaw' => "2",
                    'paymentNetworkTransactionID' => "016153570198200",
                ],
                'receiptNumber' => "166565",
                'additionalData' => "ABC",
            ];
        }

        $response = array();

        $result = Cybersource\Result::SUCCESS;
        $decision = 'ACCEPT';

        if ($input['card']['accountNumber'] === '4000000000000002')
        {
            $decision = 'REJECT';
            $result = 203;
        }

        $response['decision'] = $decision;
        $response['reasonCode'] = $result;
        $response['requestID'] = '4661454138166750401025';

        $ccAuthReply = array();
        $ccAuthReply['reconciliationID'] = $input['ccAuthService']['reconciliationID'];

        $response['ccAuthReply'] = $ccAuthReply;

        return $response;
    }

    public function getEnrollResponse($request)
    {
        $response = array();

        $payerAuthEnrollReply = array();
        $response['payerAuthEnrollReply'] = $payerAuthEnrollReply;

        $response['merchantReferenceCode'] = 'razorpay';
        $response['requestID'] = '4661468455476856801012';

        switch ($request['card']['accountNumber'])
        {
            case '41476700000006':
                throw new \SoapFault('HTTP', 'Error Fetching http headers');
                break;

            case '4012001038443335':
            case '4111460212312338':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = Cybersource\Result::ENROLLED;

                $params = array('gateway' => 'cybersource');
                $response['payerAuthEnrollReply']['acsURL'] = Http\Route::getUrl('mock_cybersource_acs', $params);
                $response['payerAuthEnrollReply']['paReq'] = 'eNpVUttygjAQfc9XMP0AkiAw';
                $response['payerAuthEnrollReply']['xid'] = 'cGdKQXF5STA1TFl3OUtueHJnWDA';
                $response['payerAuthEnrollReply']['veresEnrolled'] = 'Y';
                break;

            case '4280951000002433':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = 101;
                $response['payerAuthEnrollReply'] = [
                    'reasonCode' => 101
                ];
                $response['missingField'] = 'c:authRequestID';
                $response['requestToken'] = 'AhjjLwSR/H2rNiTcqkX45p6D4dUQCsgfIwdIy6SZbpAeLRGAdmIW';
                break;

            case '4000400000000004':
                $response['decision'] = 'REJECT';
                $response['reasonCode'] = 151;
                $response['payerAuthEnrollReply'] = [
                    'reasonCode' => 151
                ];

                $response['merchantReferenceCode'] = '5vrAvHg6CqQlkS';
                $response['missingField'] = 'c:authRequestID';
                $response['requestID'] = '4690000690226079802108';
                $response['requestToken'] = 'AhjjLwSR/H2rNiTcqkX45p6D4dUQCsgfIwdIy6SZbpAeLRGAdmIW';
                break;

            case '555555555555558':
                $response['decision'] = 'ACCEPT';
                $response['reasonCode'] = Cybersource\Result::SUCCESS;

                $response['payerAuthEnrollReply']['veresEnrolled'] = 'U';
                $response['payerAuthEnrollReply']['commerceIndicator'] = 'spa';
                $response['payerAuthEnrollReply']['ucafCollectionIndicator'] = '1';
                break;

            default:
                $response['decision'] = 'ACCEPT';
                $response['reasonCode'] = Cybersource\Result::SUCCESS;

                $response['payerAuthEnrollReply']['commerceIndicator'] = 'internet';
                $response['payerAuthEnrollReply']['veresEnrolled']= 'U';
                $response['payerAuthEnrollReply']['eci'] = '05';
                break;
        }

        return $response;
    }

    public function acs($input)
    {
        $this->validateAuthenticateInput($input);

        return array('PaRes' => 'eNpVUttygjAQfc9XMP0AkiAw',
                     'MD' => $input['MD'],
                     'TermUrl' => $input['TermUrl']);
    }

    protected function createVerifyResponse($input)
    {
        $xml = ''.
            '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE Report SYSTEM "https://ebctest.cybersource.com/ebctest/reports/dtd/tdr_1_1.dtd">

            <Report xmlns="https://ebctest.cybersource.com/ebctest/reports/dtd/tdr_1_1.dtd" Name="Transaction Detail" Version="1.1" MerchantID="'.$input['merchantID'].'" ReportStartDate="2016-07-21 13:03:06.814+05:30" ReportEndDate="2016-07-21 13:03:06.814+05:30">
              <Requests>
                <Request MerchantReferenceNumber="5wX38AI8BKFtXs" RequestDate="2016-07-20T13:02:54+05:30" RequestID="'.$input['requestID'].'" SubscriptionID="" Source="SOAP Toolkit API">
                  <BillTo>
                    <FirstName>SHASHANK</FirstName>
                    <LastName>A</LastName>
                    <Address1>a</Address1>
                    <City>a</City>
                    <State>a</State>
                    <Zip>5</Zip>
                    <Email>test@razorpay.com</Email>
                    <Country>IN</Country>
                    <Phone />
                  </BillTo>
                  <PaymentMethod>
                    <Card>
                      <AccountSuffix>3335</AccountSuffix>
                      <ExpirationMonth>11</ExpirationMonth>
                      <ExpirationYear>2020</ExpirationYear>
                      <CardType>Visa</CardType>
                    </Card>
                  </PaymentMethod>
                  <LineItems>
                    <LineItem Number="0">
                      <FulfillmentType />
                      <Quantity>1</Quantity>
                      <UnitPrice>500.00</UnitPrice>
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
                      <RFlag>SOK</RFlag>
                      <RMsg>Request was processed successfully.</RMsg>
                    </ApplicationReply>
                  </ApplicationReplies>
                  <PaymentData>
                    <PaymentRequestID>'.$input['requestID'].'</PaymentRequestID>
                    <PaymentProcessor>vdchdfc</PaymentProcessor>
                    <Amount>500.00</Amount>
                    <CurrencyCode>INR</CurrencyCode>
                    <TotalTaxAmount>0.00</TotalTaxAmount>
                    <AuthorizationCode>831000</AuthorizationCode>
                    <AVSResult>Y</AVSResult>
                    <AVSResultMapped>Y</AVSResultMapped>
                    <PayerAuthenticationInfo>
                      <ECI>5</ECI>
                      <AAV_CAVV>AAABAWFlmQAAAABjRWWZEEFgFz+=</AAV_CAVV>
                      <XID>eW5DZTVGTkVaRWF3VnowSXYzNzA=</XID>
                    </PayerAuthenticationInfo>
                  </PaymentData>
                </Request>
              </Requests>
            </Report>';

        return $xml;
    }

    protected function makeResponse($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
