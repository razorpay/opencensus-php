<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Jobs\CorePaymentServiceSync;


class UpiAirtelGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiAirtelGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_airtel';

        $this->setMockGatewayTrue();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');
    }

    public function testPayment($status = 'created')
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function testFailedCallbackResponse($status = 'created')
    {
        $this->markTestSkipped();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getFailedAsyncCallbackContent($payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($content)
        {
            $this->makeS2SCallbackAndGetContent($content);
        });

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('failed', $payment['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentVerifyFailed()
    {
        $this->markTestSkipped();

        $payment = $this->testPayment();

        $paymentId = $payment['id'];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['success'] = false;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentId)
        {
            $this->verifyPayment($paymentId);
        });

        $this->assertSame($this->payment['payment']['verified'], 0);
    }

    public function testRefundPayment()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id'], 200, ['failed' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount' => $refund['amount']]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id'], 200, ['failed' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount' => $refund['amount']]);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

    }

    public function testCpsGatewayEntitySync()
    {
        $payment = $this->fixtures->create('payment:status_created');
        $gatewayData = [
            'mode'                => 'test',
            'timestamp'           => 294832,
            'payment_id'          => $payment->getId(),
            'gateway'             => 'upi_airtel',
            'input'               => [
                'payment'  => [
                    'id'       => $payment->getId(),
                    'amount'   => 100,
                    'currency' => 'INR',
                    'gateway'  => 'upi_airtel',
                ],
                'terminal' => [

                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction' => [
                "amount"=>100,
                "code"=>"0",
                "errorCode"=>"000",
                "expiry_time"=>5,
                "hash"=>"85eda95c91bd5ca7a573b232d18375b130afdadd500af1fd3d51e3d5c206df858cf7c025d18173782a486597b6a84133e1dee5721a7c6d95869c8f3014bb66f3",
                "hdnOrderID"=>"DR9fyiL6mQoHGB",
                "message"=>"Success",
                "payment_id"=>$payment->getId(),
                "rrn"=>"928016135785",
                "status"=>"authorization_inititated",
                "type"=>"collect",
                "vpa"=>"razorpay@mairtel",
                "acquirer"=>[
                    "gateway_reference_id1"=>null,
                    "rrn"=>"928016135785",
                    "vpa"=>"7829250063@upi"
                ],
                "mid"=>"MER0000000548542",
                "paymentId"=>$payment->getId(),
                "txnStatus"=>"SUCCESS",
            ],
        ];
        $cpsSync = new CorePaymentServiceSync($gatewayData);
        $cpsSync->handle();
        $mozart = $this->getLastEntity('mozart', true);
        $this->assertEquals($mozart['action'], 'authorize');
        $this->assertEquals($mozart['gateway'], 'upi_airtel');
        $this->assertEquals($mozart['payment_id'], $payment->getId());
        $this->assertArraySelectiveEquals(json_decode($mozart['raw'], true), $gatewayData['gateway_transaction']);
        $this->assertArraySelectiveEquals($mozart['data'], $gatewayData['gateway_transaction']);
    }



    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }
}
