<?php


namespace Functional\Payment;

use Carbon\Carbon;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\Fixtures\Fixtures;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;

class SplitPaymentNew extends TestCase
{
    use PaymentTrait;
    use EntityFetchTrait;
    use DbEntityFetchTrait;
    use MocksSplitz;

    protected $payment;
    protected $fixtures;
    protected $order;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/SplitPaymentTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
        $this->fixtures = Fixtures::getInstance();
        $this->fixtures->merchant->enableWallet('10000000000000', 'razorpaywallet');
        $this->fixtures->merchant->addFeatures([Constants::RAZORPAY_WALLET]);
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockAllSplitzTreatment([
            "response" => [
                "variant" => [
                    "name" => 'enabled',
                ]
            ]
        ]);
    }

    protected function getDefaultSplitPaymentArray(array $orderOverrides = [])
    {
        $defaultAttributes = [
            'amount' => 1000
        ];
        foreach ($orderOverrides as $key => $value)
        {
            $defaultAttributes[$key] = $value;
        }
        $this->order = $this->fixtures->order->create($defaultAttributes);

        $payment = $this->getDefaultPaymentArrayNeutral();
        unset($payment['bank']);

        $payment['order_id'] = 'order_' . $this->order['id'];
        $payment['wallet_amount'] = 100;
        $payment['wallet_user_id'] = 'iuser_I9eCvXfHx7nzZF';
        $payment['amount'] = 900;

        return $payment;
    }

    public function testSplitPayment()
    {
        $this->payment = $this->getDefaultSplitPaymentArray();
        $this->startTest();

        $response = $this->getEntities('payment', [
            'order_id' => $this->payment['order_id']
        ]);

        self::assertEquals(2, $response['count']);
        foreach ($response["items"] as $payment)
        {
            self::assertEquals("authorized", $payment['status']);
        }
    }

    public function testSplitPaymentWithAutoCapture()
    {
        $this->payment = $this->getDefaultSplitPaymentArray([
            'payment_capture' => true
        ]);
        $this->startTest();

        $response = $this->getEntities('payment', [
            'order_id' => $this->payment['order_id']
        ]);

        self::assertEquals(2, $response['count']);
        foreach ($response['items'] as $payment)
        {
            self::assertEquals("captured", $payment['status']);
        }

        $order = $this->getDbEntityById('order', $this->payment['order_id']);
        self::assertEquals("paid", $order['status']);
    }

    public function testSplitPaymentCancellation()
    {
        // create fixtures for order, payment
        $order = $this->fixtures->order->create([
            'amount' => 1000
        ]);
        $payment = $this->fixtures->create(
            'payment',
            [
                'created_at' => time() - 10 * 60,
                'status' => 'created',
                'terminal_id' => '1n25f6uN5S1Z5a',
                'method' => 'card',
                'amount' => 900,
                'order_id'  => $order['id']
            ]);
        $walletPayment = $this->fixtures->create(
            'payment',
            [
                'created_at' => time() - 10 * 60,
                'status' => 'created',
                'method' => 'wallet',
                'wallet' => 'razorpaywallet',
                'amount' => 100,
                'order_id'  => $order['id']
            ]
        );
        $this->fixtures->order->createOrderMeta($order['id'], [
            'relations' => [
                $payment['id'] => $walletPayment['id'],
            ]
        ]);

        // initiate cancel action
        $requestData = [
            'url' => '/payments/pay_'.$payment['id'].'/cancel'
        ];
        $testData = $this->testData[__FUNCTION__];
        $this->replaceValuesRecursively($testData['request'], $requestData);
        $this->runRequestResponseFlow($testData);

        // assert payment statuses
        $response = $this->getEntities('payment', [
            'order_id' => 'order_'.$order['id']
        ]);
        self::assertEquals(2, $response['count']);
        foreach ($response['items'] as $payment)
        {
            self::assertEquals('failed', $payment['status']);
        }
    }

    public function testSplitPaymentTimeout()
    {
        // create fixtures for order, payment
        $order = $this->fixtures->order->create([
            'amount' => 1000
        ]);
        $payment = $this->fixtures->create(
            'payment',
            [
                'created_at' => Carbon::now()->subMinutes(15)->getTimestamp(),
                'status' => 'created',
                'method' => 'upi',
                'amount' => 900,
                'order_id'  => $order['id']
            ]);
        $walletPayment = $this->fixtures->create(
            'payment',
            [
                'created_at' => time() - 10 * 60,
                'status' => 'created',
                'method' => 'wallet',
                'wallet' => 'razorpaywallet',
                'amount' => 100,
                'order_id'  => $order['id']
            ]
        );
        $this->fixtures->order->createOrderMeta($order['id'], [
            'relations' => [
                $payment['id'] => $walletPayment['id'],
            ]
        ]);

        // hit timeout request
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = sprintf($this->testData[__FUNCTION__]['request']['url'], $payment['id']);;

        $this->ba->pgRouterAuth();
        $this->runRequestResponseFlow($testData);

        // assert payment statuses
        $response = $this->getEntities('payment', [
            'order_id' => 'order_'.$order['id']
        ]);
        self::assertEquals(2, $response['count']);
        foreach ($response['items'] as $payment)
        {
            self::assertEquals('failed', $payment['status']);
        }
    }

    public function testSplitPaymentRetry()
    {
        // create fixtures for order, payment
        $order = $this->fixtures->order->create([
            'amount' => 1000
        ]);
        $payment = $this->fixtures->create(
            'payment',
            [
                'created_at' => Carbon::now()->subMinutes(15)->getTimestamp(),
                'status' => 'authorized',
                'method' => 'upi',
                'amount' => 900,
                'order_id'  => $order['id']
            ]);
        $walletPayment = $this->fixtures->create(
            'payment',
            [
                'created_at' => time() - 10 * 60,
                'status' => 'failed',
                'method' => 'wallet',
                'wallet' => 'razorpaywallet',
                'amount' => 100,
                'order_id'  => $order['id']
            ]
        );
        $this->fixtures->order->createOrderMeta($order['id'], [
            'relations' => [
                $payment['id'] => $walletPayment['id'],
            ]
        ]);

        $defaultPayment = $this->getDefaultPaymentArrayNeutral();
        $defaultPayment['order_id'] = 'order_' . $order['id'];
        $defaultPayment['wallet_amount'] = 100;
        $defaultPayment['wallet_user_id'] = 'iuser_I9eCvXfHx7nzZF';
        $defaultPayment['amount'] = 900;
        unset($defaultPayment['bank']);

        $this->payment = $defaultPayment;
        $this->startTest();

        // assert payment statuses
        $response = $this->getEntities('payment', [
            'order_id' => 'order_'.$order['id']
        ]);
        self::assertEquals(4, $response['count']);
        foreach ($response['items'] as $item)
        {
            if ($item['wallet'] === 'razorpaywallet')
            {
                if ($item['id'] === 'pay_'.$walletPayment['id'])
                {
                    self::assertEquals('failed', $item['status']);
                }
                else
                {
                    self::assertEquals('authorized', $item['status']);
                }
            }
            else
            {
                self::assertEquals('authorized', $item['status']);
            }
        }
    }

    public function testSplitPaymentWithFeatureDisabled()
    {
        $this->fixtures->merchant->removeFeatures([Constants::RAZORPAY_WALLET]);
        $this->payment = $this->getDefaultSplitPaymentArray();

        $this->startTest();
    }

    public function testSplitPaymentWithIncorrectAmount()
    {
        $this->payment = $this->getDefaultSplitPaymentArray();
        $this->payment['wallet_amount'] = 200;

        $this->startTest();
    }

    public function testSplitPaymentWithFullAmountPositive()
    {
        $this->order = $this->fixtures->order->create([
            'amount' => 1000
        ]);

        $this->payment = $this->getDefaultPaymentArrayNeutral();
        $this->payment['order_id'] = 'order_' . $this->order['id'];
        $this->payment['amount'] = 1000;

        unset($this->payment['bank']);

        $this->startTest();
    }

    public function testSplitPaymentWithFullAmountNegative()
    {
        $this->order = $this->fixtures->order->create([
            'amount' => 1000
        ]);

        $this->payment = $this->getDefaultPaymentArrayNeutral();
        $this->payment['order_id'] = 'order_' . $this->order['id'];
        $this->payment['amount'] = 500;

        unset($this->payment['bank']);

        $this->startTest();
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $payment = $this->payment;
        $testData = $this->testData[$func];
        $this->replaceValuesRecursively($testData['request']['content'], $payment);
        $this->runRequestResponseFlow($testData);
    }
}
