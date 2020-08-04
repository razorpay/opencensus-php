<?php

namespace RZP\Tests\Functional\UpiTransfer;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class UpiTransferTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;

    protected $virtualAccountId;

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

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal');

        $this->vpa = $this->createVirtualAccount();
    }

    public function testProcessMindgateUpiTransferPayment()
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

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
    }

    public function testProcessMindgateUpiTransferPaymentIgnoreCase()
    {
        $this->processUpiTransferIgnoreCase();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($this->vpa['address'], $upiTransfer['payee_vpa'], '', 0.0, 10, false, true);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
    }

    public function testProcessFailedMindgateUpiTransferPayment()
    {
        $this->processUpiTransfer(__FUNCTION__, false);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertNull($payment);
        $this->assertNull($upiTransfer);
        $this->assertNull($upi);
    }

    public function testProcessMindgateUpiTransferRefund()
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

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

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

    public function testProcessMindgateUpiTransferUnexpectedPayment()
    {
        $this->processUpiTransfer(__FUNCTION__);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($upiTransfer['expected'], false);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $upiTransfer['unexpected_reason']);
    }

    public function testProcessMindgateUpiTransferWithVpaPricing()
    {
        $pricingPlanId = $this->fixtures->create('pricing:upi_transfer_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $this->processUpiTransfer();

        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 1%
        $this->assertEquals($transaction['amount'] * 1 / 100, $transaction['fee'] - $transaction['tax']);
    }

    /**
     * This test is to verify the case when merchant doesn't have either UPI or vpa pricing enabled.
     * In that case, default/fallback pricing has to be picked up for payment creation.
     */
    public function testProcessMindgateUpiTransferWithDefaultPricing()
    {
        $pricingPlanId = $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $this->processUpiTransfer();

        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 2%
        $this->assertEquals($transaction['amount'] * 2 / 100, $transaction['fee'] - $transaction['tax']);
    }

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000', $vpaDescriptor = null, $additionalFields = [])
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = array_merge($this->testData[__FUNCTION__], $additionalFields);

        if ($vpaDescriptor !== null)
        {
            $request['content']['receivers']['vpa']['descriptor'] = $vpaDescriptor;
        }
        $response = $this->makeRequestAndGetContent($request);

        $this->virtualAccountId = $response['id'];

        $vpa = $response['receivers'][0];

        return $vpa;
    }

    protected function processUpiTransfer($function = __FUNCTION__, $valid = true, string $gateway = Gateway::UPI_MINDGATE)
    {
        $this->ba->directAuth();

        $request = $this->testData[$function];

        $data = $request['content'];

        $mockServer = $this->mockServer($gateway);

        switch ($gateway)
        {
            case Gateway::UPI_MINDGATE:
            {
                $request['content']['meRes'] = $mockServer->encrypt($data['meRes']);

                break;
            }
            case Gateway::UPI_ICICI:
            {
                $request['raw'] = $mockServer->getAsyncCallbackContentForBharatQr($request['content']);

                break;
            }
        }

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['valid'], $valid);

        return $response;
    }

    protected function processUpiTransferIgnoreCase($function = __FUNCTION__, $valid = true)
    {
        $this->ba->privateAuth();

        $request = $this->testData[$function];

        $data = $request['content'];

        $request['content']['meRes'] = $this->mockServer(Gateway::UPI_MINDGATE)->encrypt($data['meRes']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['valid'], $valid);

        return $response;
    }

    public function testProcessIciciUpiTransferPayment()
    {
        $terminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->enableRazorXTreatmentForRazorXVpaIcici();

        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

    }
    public function testProcessIciciUpiTransferUnexpectedPayment()
    {
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->processUpiTransfer('testProcessIciciUpiTransferPayment', true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($upiTransfer['expected'], false);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $upiTransfer['unexpected_reason']);
    }

    protected function enableRazorXTreatmentForRazorXVpaIcici()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === 'virtual_vpa_icici')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function testProcessMindgateUpiTransferToClosedVaUnexpectedReason()
    {
        $this->closeVirtualAccount($this->virtualAccountId);

        $response = $this->processUpiTransfer();

        $this->assertNull($response['message']);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);

        $this->assertEquals(false, $upiTransfer['expected']);

        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $upiTransfer['unexpected_reason']);
    }

    public function testProcessMindgateUpiTransferToDueToBeClosedVaUnexpectedReason()
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->createVirtualAccount(
            'test',
            10000000000000,
            'anothervpa',
            ['close_by' => $currentTimestamp + (20 * 60)]
        );

        // When close_by time has passed but the next cron execution time is still due.
        $this->fixtures->edit('virtual_account', $this->virtualAccountId, ['close_by' => $currentTimestamp - 60]);

        $response = $this->processUpiTransfer('testProcessMindgateUpiTransferToDueToBeClosedVa');

        $this->assertNull($response['message']);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);

        $this->assertEquals(false, $upiTransfer['expected']);

        $this->assertEquals('VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED', $upiTransfer['unexpected_reason']);
    }

    public function testUpiTransferValidateTpvWithValidPayerDetails()
    {
        $this->processUpiTransferForVaWithTpvEnabled(__FUNCTION__, true);
    }

    public function testUpiTransferValidateTpvWitInvalidPayerDetails()
    {
        $this->processUpiTransferForVaWithTpvEnabled(__FUNCTION__, false, 'VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED');
    }

    protected function processUpiTransferForVaWithTpvEnabled($testFunction, $tpvStatus, $unexpectedReason = null)
    {
        $this->createVirtualAccount('test', '10000000000000', 'testvpatpv', $this->testData['createVAWithAllowedPayer']);

        $this->processUpiTransfer($testFunction);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals($unexpectedReason, $upiTransfer['unexpected_reason']);

        $paymentStatus = ($tpvStatus === true) ? 'captured' : 'refunded';
        $this->assertEquals($paymentStatus, $payment['status']);
    }
}
