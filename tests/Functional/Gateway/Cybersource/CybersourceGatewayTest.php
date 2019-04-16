<?php

namespace RZP\Tests\Functional\Gateway\Cybersource;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Cybersource\Fields;
use RZP\Jobs\CorePaymentServiceSync;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'cybersource';

        $this->mockCardVault();
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // $this->assertNull($payment['verify_at']);
    }

    public function testPaymentEnrolledCard()
    {
        $enrolledCard = [
            'card' => [
                'number'    => '4000000000000002',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2024',
                'cvv'               => '566',
            ]
        ];

        $payment = $this->defaultAuthPayment($enrolledCard);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        //    $this->assertNotNull($payment['reference2']);
        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testCybersourceCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        // After capture verify_at is set to current_time()
        // $this->assertNull($payment['verify_at']);
    }

    public function testPaymentWithSavedCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['token'] = '1000gcardtoken';
        $payment['app_token'] = 'capp_1000000custapp';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($response['razorpay_payment_id'], $payment['id']);
        $this->assertTestResponse($payment);
    }

    public function testNotEnrolledPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['two_factor_auth'],
            \RZP\Models\Payment\TwoFactorAuth::NOT_APPLICABLE);
    }

    public function testGatewayFullRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->assertRefundAmount($payment['amount']);

        $this->refundPayment($payment['id']);

        $cybersource = $this->getLastEntity('cybersource', true);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($payment['id']);

        $this->assertEquals($paymentId, $cybersource['payment_id']);

        $this->assertNotNull($cybersource['refund_id']);
        $this->assertTestResponse($cybersource);
    }

    public function testGatewayPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refundAmount = (int) ($payment['amount'] / 5);

        $this->assertRefundAmount($refundAmount);

        $this->refundPayment($payment['id'], $refundAmount);

        $cybersource = $this->getLastEntity('cybersource', true);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($payment['id']);

        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertNotNull($cybersource['refund_id']);
        $this->assertTestResponse($cybersource);
    }

    public function testAuthorizedPaymentRefund()
    {
        $this->fixtures->merchant->addFeatures('reverse');

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];
        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);
        $this->assertTestResponse($refund);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('reverse', $cybersource['action']);
    }

    public function testGatewayPaymentMatchVerify()
    {
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $response = $this->verifyPayment($payment['razorpay_payment_id']);

        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
        $this->assertSame($response['gateway']['gateway'], 'cybersource');
        $this->assertSame($response['gateway']['gatewayPayment']['status'], 'authorized');
    }

    public function testGatewayAuthorizedPaymentMatchVerify()
    {
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_content')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
            }
        });

        $data = $this->testData['testGatewayPaymentMismatchVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $response = $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    public function testGatewayFailedPaymentMismatchVerify()
    {
        $this->mockTimeout('processor');

        $this->makeRequestAndCatchException(function()
        {
            $this->mockServerContentFunction(function(&$content, $action = null)
            {
                if ($action === 'pay_init')
                {
                    $content['success'] = false;
                    $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                    $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                    $content['error']['gateway_error_description'] = 'Do not honour';
                }
            });

            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testGatewayPaymentMismatchVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testGatewayRefundVerify()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'verify_xml')
            {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Report SYSTEM "https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd">
<Report xmlns="https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd" Name="Transaction Detail" Version="1.1" MerchantID="razorpaycybs" ReportStartDate="2017-04-20 11:33:58.208+05:30" ReportEndDate="2017-04-20 11:33:58.208+05:30">
  <Requests>
    <Request MerchantReferenceNumber="'.$refund['id'].'" RequestDate="2017-04-04T00:01:12+05:30" RequestID="4912442722396160004013" SubscriptionID="" Source="SOAP Toolkit API">
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
          <AccountSuffix>8371</AccountSuffix>
          <ExpirationMonth>4</ExpirationMonth>
          <ExpirationYear>2018</ExpirationYear>
          <CardType>MasterCard</CardType>
        </Card>
      </PaymentMethod>
      <LineItems>
        <LineItem Number="0">
          <FulfillmentType />
          <Quantity>1</Quantity>
          <UnitPrice>2267.00</UnitPrice>
          <TaxAmount>0.00</TaxAmount>
          <ProductCode>default</ProductCode>
        </LineItem>
      </LineItems>
      <ApplicationReplies>
        <ApplicationReply Name="ics_credit">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Request was processed successfully.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <PaymentRequestID>4912442722396160004013</PaymentRequestID>
        <PaymentProcessor>vdcaxis</PaymentProcessor>
        <Amount>'. $refund['amount'] / 100 .'</Amount>
        <CurrencyCode>INR</CurrencyCode>
        <TotalTaxAmount>0.00</TotalTaxAmount>
        <AuthorizationCode>292540</AuthorizationCode>
      </PaymentData>
      <MerchantDefinedData>
        <field1>1</field1>
        <field2>'. $refund['payment_id'] .'</field2>
      </MerchantDefinedData>
    </Request>
  </Requests>
</Report>
';
            }
        });

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);
    }

    public function testGatewayVerifyRefundVerifyFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'refund')
            {
               throw new \SoapFault('HTTP', 'Random SoapFault Exception');
            }
        });

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->scroogeRefund($refund);

        $this->assertEquals(false, $response['success']);
        $this->assertEquals('SERVER_ERROR_RUNTIME_ERROR', $response['status_code']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('created', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);
    }

    public function testGatewayVerifyRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'verify_xml')
            {
               throw new \SoapFault('HTTP', 'Random SoapFault Exception');
            }
        });

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);

        $this->assertEquals('created', $actualRefund['status']);

        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);
    }

    public function testGatewayRefundVerifyMultipleTimes()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'verify_xml')
            {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE Report SYSTEM "https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd">
<Report xmlns="https://ebc.cybersource.com/ebc/reports/dtd/tdr_1_1.dtd" Name="Transaction Detail" Version="1.1" MerchantID="razorpaycybs" ReportStartDate="2017-04-20 11:33:58.208+05:30" ReportEndDate="2017-04-20 11:33:58.208+05:30">
  <Requests>
    <Request MerchantReferenceNumber="'.$refund['id'].'" RequestDate="2017-04-04T00:01:12+05:30" RequestID="4912442722396160004013" SubscriptionID="" Source="SOAP Toolkit API">
      <ApplicationReplies>
        <ApplicationReply Name="ics_credit">
          <RCode>1</RCode>
          <RFlag>SOK</RFlag>
          <RMsg>Request was processed successfully.</RMsg>
        </ApplicationReply>
      </ApplicationReplies>
      <PaymentData>
        <PaymentRequestID>4912442722396160004013</PaymentRequestID>
        <PaymentProcessor>vdcaxis</PaymentProcessor>
        <Amount>'. $refund['amount'] / 100 .'</Amount>
        <CurrencyCode>INR</CurrencyCode>
        <TotalTaxAmount>0.00</TotalTaxAmount>
        <AuthorizationCode>292540</AuthorizationCode>
      </PaymentData>
    </Request>
  </Requests>
</Report>
';
            }
        });

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('processed', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(true, $actualRefund['gateway_refunded']);

        $response = $this->retryFailedRefunds();

        $this->assertEquals($response['status'], []);
    }

    public function testGatewayRefundVerifyMultipleFailedAttempts()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockRefundTimeout('processor');

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function(&$xml, $action) use ($refund)
        {
            if ($action === 'verify_xml')
            {
               throw new \SoapFault('HTTP', 'Random SoapFault Exception');
            }
        });

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefunds();
        $response = $this->retryFailedRefunds();
        $response = $this->retryFailedRefunds();

        $this->assertEquals($response['status'], []);

        $id = explode('_', $refund['id'], 2)[1];

        $actualRefund = $this->getEntityById('refund', $id, true);

        $this->assertEquals($refund['amount'], $actualRefund['amount']);
        $this->assertEquals('created', $actualRefund['status']);
        $this->assertEquals(1, $actualRefund['attempts']);
        $this->assertEquals(false, $actualRefund['gateway_refunded']);
    }

    public function testGatewayRefundVerifySuccess()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $response = $this->scroogeRefund($refund);

        // Adding the following checks just to assert scrooge response - at this point the refund is already processed
        $this->assertEquals(true, $response['success']);
        $this->assertEquals('REFUND_SUCCESSFUL', $response['status_code']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $time = Carbon::now(Timezone::IST)->addMinutes(35);
        Carbon::setTestNow($time);

        $response = $this->retryFailedRefunds();

        $this->assertEquals($response['status'], []);
    }

    public function testGatewayVerifyPaymentNotFound()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['status'] = 'authorize_failed';
            }
        });

        $this->makeRequestAndCatchException(function()
        {
            $this->doAuthPayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'verify_content')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_FAILED';
                $content['error']['gateway_error_code'] = 'DONOTHONOUR';
                $content['error']['gateway_error_description'] = 'Do not honour';
                $content['data']['status'] = 'authorize_failed';
            }
        });

        $response = $this->verifyPayment($payment['id']);

        $this->assertSame($response['payment']['status'], 'failed');
        $this->assertSame($response['payment']['verified'], 1);
        $this->assertSame($response['gateway']['status'], 'status_match');
        $this->assertSame($response['gateway']['gateway'], 'cybersource');
        $this->assertSame($response['gateway']['gatewayPayment']['status'], 'authorize_failed');
    }

    public function testAuthorizeFailedPayment()
    {
        $enrolledCard = [
            'card' => [
                'number'    => '4000000000000002',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2024',
                'cvv'               => '566',
            ]
        ];

        $defaultPayment = $this->getDefaultPaymentArray();

        $payment = array_merge($defaultPayment, $enrolledCard);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['error']['internal_error_code'] = 'GATEWAY_ERROR_TIMED_OUT';
                $content['data']['xid'] = null;
            }
        });

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['gateway'], 'cybersource');
        $this->assertEquals($payment['internal_error_code'], 'GATEWAY_ERROR_TIMED_OUT');

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');
        $this->assertNull($payment['internal_error_code']);
        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertNotNull($cybersource['ref']);
        $this->assertTestResponse($cybersource);
    }

    public function testGatewayPaymentXidMisMatch()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000000000000002';

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth_verify')
            {
                $content['data']['xid'] = 'random_xid';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayPaymentInvalidEci()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth_init')
            {
                $content['data']['eci'] = null;
            }

            if ($action === 'auth_verify')
            {
                $content['data']['eci'] = null;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testGatewayInvalidReasonCode()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            $content['success'] = false;
            $content['error']['internal_error_code'] = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCpsGatewayEntitySync()
    {
        $payment = $this->fixtures->create('payment:status_created');

        $gatewayData = [
            'mode'       => 'test',
            'timestamp'  => 294832,
            'payment_id' => $payment->getId(),
            'gateway'    => 'cybersource',
            'input'      => [
                'payment'       => [
                    'id'       => $payment->getId(),
                    'amount'   => 500000,
                    'currency' => 'INR',
                ],
                'terminal'      => [
                    'gateway_acquirer' => 'hdfc',
                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction'       => [
                'payment_id'    => $payment->getId(),
                'acquirer'      => 'hdfc',
                'action'        => 'authorize',
                'received'      => false,
                'amount'        => 50000,
                'currency'      => 'INR',
                'status'        => 'created',
                'xid'           => 'aFM3NktkemM4OW1sSGNoOERXUzE=',
                'veresEnrolled' => 'Y',
                'ref'           => '466146845543214129700',
                'reason_code'   => 475,
            ],
        ];

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'created');

        $gatewayData['gateway_transaction']['status'] = 'authenticated';

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals($cybersource['status'], 'authenticated');
    }

    public function testGatewayVerifyAuthResponseFailure()
    {$this->markTestSkipped();
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            $content['reasonCode'] = 202;
            $content['decision'] = 'REJECT';
            $content['ccAuthReply'] = [
                'reasonCode' => 202
            ];
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSoapFaultException()
    {$this->markTestSkipped();
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function(&$content)
        {
            throw new \SoapFault('HTTP', 'Random SoapFault Exception');
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentAuthenticateCard()
    {$this->markTestSkipped();
        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('2RecurringTerm', $paymentEntity['terminal_id']);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($paymentId);

        $cybersource = $this->getLastEntity('cybersource', true);

        $cybersourceData = $this->testData['cybersourceRecurringEntity'];

        $this->assertNotNull($cybersource['ref']);
        $this->assertNotNull($cybersource['authorizationCode']);
        $this->assertEquals($paymentId, $cybersource['payment_id']);
        $this->assertArraySelectiveEquals($cybersourceData, $cybersource);
    }

    public function testManualGatewayCapture()
    {$this->markTestSkipped();
        $paymentData = $this->defaultAuthPayment();

        $payment = $this->fixtures->payment->edit($paymentData['id'], [
            'status' => 'captured',
            'captured_at' => time()
        ]);

        list($txn, $feeSplit) = $this->createTransactionForPaymentAuthorized($payment);
        $txn->saveOrFail();
        $payment->saveOrFail();

        $data = [
            'request' => [
                'url' => '/payments/'.$payment['public_id'] . '/gateway/capture',
                'method' => 'POST',
            ],

            'response' => [
                'content' => [
                    'payment_id' => $payment['id'],
                    'result'     => true
                ]
            ]
        ];

        $this->ba->adminAuth();

        $this->runRequestResponseFlow($data);

        $cybersource = $this->getLastEntity('cybersource', true);

        $this->assertEquals('capture', $cybersource['action']);
        $this->assertEquals('captured', $cybersource['status']);
    }

    public function testStatusAfterFailedAutoCapturePayment()
    {$this->markTestSkipped();
        $order = $this->fixtures->create('order:payment_capture_order');

        $this->mockServerContentFunction(function($input, $action)
        {
            if ($action === 'validate_capture')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid Capture');
            }
        });

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order['amount'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['captured_at']);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals(false, $payment['auto_captured']);
        $this->assertEquals('authorized', $payment['status']);

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(true, $order['authorized']);
    }

    // @todo: refactor
    protected function transaction(callable $callable)
    {
        $db = \DB::getFacadeRoot();

        return $db->transaction($callable);
    }

    // -------- helpers ----------

    protected function assertRefundAmount($expectedAmount)
    {
        $this->mockServerContentFunction(function($content, $action = null) use ($expectedAmount)
        {
            if ($action === 'validate_refund')
            {
                $actualRefundAmount = (int) ($content['purchaseTotals']['grandTotalAmount'] * 100);

                $assertion = ($actualRefundAmount === $expectedAmount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });
    }

    protected function mockTimeout($type = 'gateway')
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($type)
        {
            if ($action === 'enrollment')
            {
                if ($type === 'gateway')
                {
                    throw new \SoapFault('HTTP', 'Error Fetching http headers');
                }

                if ($type === 'processor')
                {
                    $content['decision'] = 'REJECT';
                    $content['reasonCode'] = 151;
                    $content['payerAuthEnrollReply'] = [
                        'reasonCode' => 151
                    ];

                    unset($content['purchaseTotals']);
                }
            }
        });
    }

    protected function mockRefundTimeout($type = 'gateway')
    {
        $this->mockServerContentFunction(function(&$content, $action = null) use ($type)
        {
            if ($action === 'refund')
            {
                if ($type === 'processor')
                {
                    $content['decision'] = 'ERROR';
                    $content['reasonCode'] = 150;
                    $content['ccCreditReply'] = [
                        'reasonCode' => 150
                    ];

                    unset($content['purchaseTotals']);
                }
            }
        });
    }

    public function testGatewayAuthorizedPaymentVerifyFailure()
    { $this->markTestSkipped();
        $payment = $this->doAuthPayment();

        $this->fixtures->base->editEntity(
            'payment', $payment['razorpay_payment_id'], ['authorized_at' => strtotime('-1 min')]);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'verify_xml')
            {
                $content = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html lang="en">
    <head>
        <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
        <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
        <META HTTP-EQUIV="EXPIRES" CONTENT="0">

        <title>Cybersource Business Center - System Error</title>
        <link rel="shortcut icon" href="/ebc/images/favicon.ico;JSESSIONID=499B1A0350391B448EDE9BDC1A39DF8C.localhost_bc" type="image/x-icon" />
        <link rel="STYLESHEET" type="text/css" href="/ebc/css/ubc_style.css.jsp;JSESSIONID=499B1A0350391B448EDE9BDC1A39DF8C.localhost_bc">
    </head>

    <body>

<table width="95%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="pagetitle" id="systemErrorPageTitle">System Error</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td id="systemErrorMessage">An error has occurred. Please try again. If you continue to receive an error, please contact Customer Support.</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>

    </body>
</html> ';
            }
        });

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            },
            \RZP\Exception\GatewayTimeoutException::class);
    }
}
