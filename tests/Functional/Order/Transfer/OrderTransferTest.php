<?php

namespace RZP\Tests\Functional\Order\Transfers;

use Mockery;
use Closure;
use Carbon\Carbon;
use RZP\Models\Transfer;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Webhook;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTransferTest extends TestCase
{
    use MocksDnsTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/OrderTransferTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $this->linkedAccountId = $account['id'];
    }

    public function testCreateOrderTransfers()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderTransfersInsufficientBalance()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('failed', $transfer['status']);

        $this->assertEquals(PublicErrorDescription::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE, $transfer['message']);
    }

    public function testProcessOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->capturePaymentProcessOrderTransfers($order); // Order transfers are automatically processed post payment capture

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals($order['id'], $transfer['source']);

        $this->assertEquals('processed', $transfer['status']);

        Transfer\Entity::verifyIdAndSilentlyStripSign($transfer['id']);

        $payment = $this->getDbEntity('payment', ['transfer_id' => $transfer['id']]);

        $this->assertArraySelectiveEquals(['roll_no' => 'iec2011025'], $payment->getNotes()->toArray());
    }

    public function testProcessOrderTransfersPartialPayment()
    {
        $this->startTest();
    }

    public function testGetOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->capturePaymentProcessOrderTransfers($order);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/orders/' . $order['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);
    }

    public function testReverseOrderTransfer()
    {
        $order = $this->testCreateOrderTransfers();

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/transfers/' . $transfer['id'] . '/reversals';

        $this->ba->privateAuth();

        $reversal = $this->runRequestResponseFlow($data);

        $this->assertEquals($transfer['id'], $reversal['transfer_id']);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('reversed', $transfer['status']);
    }

    public function testWebhookOrderTransferProcessed()
    {
        $this->createWebhook(
            [
                'events' => [
                    'transfer.processed' => '1',
                ]
            ]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertEquals('transfer.processed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->testProcessOrderTransfers();
    }

    public function testCronProcessFailedOrderTransfers()
    {
        $order = $this->testCreateOrderTransfers();

        $this->fixtures->merchant->editBalance(100);

        $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('failed', $transfer['status']);

        $this->fixtures->merchant->editBalance(100000);

        $timestamp = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $this->fixtures->transfer->editProcessedAt($timestamp - 10, $transfer['id']);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $orderIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('processed', $transfer['status']);

        $this->assertEquals($order['id'], 'order_' . $orderIds[0]);
    }

    protected function capturePaymentProcessOrderTransfers($order, $paymentAmount = null)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];

        if ($paymentAmount !== null)
        {
            $payment['amount'] = $paymentAmount;
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function mockInfernoFire(Closure $closure, $times = 1)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }

}
