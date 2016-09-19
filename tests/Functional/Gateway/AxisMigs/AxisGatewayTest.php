<?php

namespace RZP\Tests\Functional\Gateway\AxisMigs;

use Mockery;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\TwoFaStatus;
use RZP\Error;
use RZP\Error\PublicErrorCode;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->markTestSkipped('Removed');

        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals(TwoFaStatus::PASSED, $payment[Entity::TWO_FA_STATUS]);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $payment);
    }

    public function testMasterCardPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthPayment($payment);
    }

    public function testFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testMaestroOnMigsFailOnLive()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $this->fixtures->on('live')->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1, 'live' => 1, 'pricing_plan_id' => '1hDYlICobzOCYt']);
        // $merchant = $this->fixtures->merchant->activate();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->ba->publicLiveAuth();
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
        $payment = $this->getLastEntity('axis_migs', true);
        $this->assertEquals('pay', $payment['vpc_Command']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->resetMockServer();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testForceAuthorizePayment()
    {
        $payment = $this->doAuthPayment();
        $migs = $this->getLastEntity('axis_migs', true);
        $txnNo = (int) $migs['vpc_TransactionNo'] - 1;

        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('axis_migs', true);
        $pid1 = 'pay_'.$payment['payment_id'];

        $this->fixtures->edit('axis_migs', $payment['id'], ['received' => '0']);

        $this->resetMockServer();

        $this->forceAuthorizeFailedPayment($pid1, ['vpc_TransactionNo' => $txnNo]);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('axis_migs', true);
        $this->assertEquals($payment['vpc_TransactionNo'], $txnNo);
    }

    public function testFailureWhen3DSFailsForDomesticMerchant()
    {
        $this->fixtures->merchant->disableInternational();

        $testData = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '55553555655655';

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
	        $payment = $this->doAuthPayment($payment);
	    });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(TwoFaStatus::FAILED, $payment[Entity::TWO_FA_STATUS]);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testFailureWhen3DSFailsForRiskyMerchant()
    {
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->enableRisky();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '55553555655655';
            $payment = $this->doAuthPayment($payment);
        });
    }

}
