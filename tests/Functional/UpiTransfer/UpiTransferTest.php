<?php

namespace RZP\Tests\Functional\UpiTransfer;

use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiTransferTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:vpa_terminal');
        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal');

        $this->vpa = $this->createVirtualAccount();
    }

    public function testProcessUpiTransferPayment()
    {
        $this->markTestSkipped();

        $this->processUpiTransfer();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($this->vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($upiTransfer['expected'], true);
    }

    public function testProcessUpiTransferRefund()
    {
        $this->processUpiTransfer();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($this->vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($upiTransfer['expected'], true);

        // Being used in scrooge checks
        $this->gateway = $payment['gateway'];

        $this->refundPayment(
            $payment['id'],
            4000,
            [
                'is_fta' => true,
                'fta_data' => [
                    'vpa' => [
                        'address' => $payment['vpa']
                    ],
                ],
            ]
        );

        $refund = $this->getDbLastEntity('refund');

        $this->assertEquals(1, $refund['is_scrooge']);
        $this->assertEquals('processed', $refund['status']);

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals($refund->getId(), $fta['source_id']);
        $this->assertEquals('refund', $fta['source_type']);
        $this->assertNotNull($fta['vpa_id']);
    }

    public function testProcessUpiTransferUnexpectedPayment()
    {
        $this->markTestSkipped();

        $this->processUpiTransfer(__FUNCTION__);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($upiTransfer['expected'], false);
    }

    public function testProcessUpiTransferWithVpaPricing()
    {
        $this->markTestSkipped();

        $pricingPlanId = $this->fixtures->create('pricing:upi_transfer_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $this->processUpiTransfer();

        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 1%
        $this->assertEquals($transaction['amount'] * 1 / 100, $transaction['fee'] - $transaction['tax']);
    }

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000')
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $vpa = $response['receivers'][0];

        return $vpa;
    }

    protected function processUpiTransfer($function = __FUNCTION__)
    {
        $this->ba->privateAuth();

        $request = $this->testData[$function];

        $data = $request['content'];

        $request['content']['meRes'] = $this->mockServer(Gateway::UPI_MINDGATE)->encrypt($data['meRes']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['valid']);

        return $response;
    }
}
