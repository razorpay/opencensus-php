<?php

namespace RZP\Tests\Functional\Helpers;

use Mockery;
use Carbon\Carbon;
use RZP\Models\UpiMandate;
use RZP\Services\Mock\Reminders;

trait PaymentsUpiRecurringTrait
{
    use DbEntityFetchTrait;

    protected $mockedReminderService;

    protected function createUpiRecurringOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
            'token'           => [
                'max_amount'      => 150000,
                'frequency'       => 'monthly',
                'recurring_type'  => 'before',
                'recurring_value' => 30,
                'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
            ]
        ];

        $content = array_merge($content, $override);

        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url' => '/orders',
        ];

        $order = $this->makeRequestAndGetContent($request);

        return $order['id'];
    }

    protected function createUpiOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
        ];

        $content = array_merge_recursive($content, $override);

        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url' => '/orders',
        ];

        $order = $this->makeRequestAndGetContent($request);

        return $order['id'];
    }

    public function createFirstUpiRecurringPayment(
        array $paymentData = [],
        array $orderData = [],
        bool $assert = true): UpiMandate\Entity
    {
        $orderId = $this->createUpiRecurringOrder($orderData);

        $payment = $this->getDefaultUpiRecurringPaymentArray();
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['vpa'] = 'success@razorpay';

        $this->doAuthPayment(array_merge($payment, $paymentData));

        // Basic Assertions, helpful for second recurring
        $payment = $this->getDbLastPayment();
        $mandate = $this->getDbLastEntity('upi_mandate');
        $token = $this->getDbLastEntity('token');

        if ($assert === true)
        {
            $this->assertEquals($mandate['token_id'], $token['id']);
            $this->assertEquals($mandate['customer_id'], $token['customer_id']);
            $this->assertEquals('confirmed', $mandate['status']);

            $this->assertArraySubset([
                'method'            => 'upi',
                'recurring_status'  => 'confirmed',
                'recurring'         => true,
            ], $token->toArray(), true);

            $this->assertArraySubset([
                'token_id'      => $token->getId(),
                'order_id'      => $payment->getApiOrderId(),
                'status'        => 'confirmed',
                'umn'           => sprintf('%s@razorpay', $payment->getId()),
                'rrn'           => '001000100001',
                'npci_txn_id'   => 'RZP12345678910111213141516',
            ], $mandate->toArray(), true);
        }

        return $mandate;
    }

    protected function mockReminderService(string $method, callable $assert = null, callable $response = null)
    {
        if (empty($this->mockedReminderService))
        {
            $this->mockedReminderService = Mockery::mock(Reminders::class)->makePartial();
        }

        if (is_null($assert) === true)
        {
            $assert = function($param1, $param2 = null, $param3 = null)
            {
                $this->assertTrue(is_array($param1));
            };
        }

        if (is_null($response) === true)
        {
            $response = function()
            {
                return 'TestReminderId';
            };
        }

        $this->mockedReminderService->shouldReceive($method)
            ->andReturnUsing(function($request, $merchantId) use ($assert, $response)
            {
                $assert($request, $merchantId);

                return ['id' => $response()];
            });

        $this->app->instance('reminders', $this->mockedReminderService);
    }

    protected function sendReminderRequest($reminder)
    {
        $request = [
            'url'       => '/v1/' . $reminder['callback_url'],
            'method'    => 'post',
            'content'   => [],
        ];

        $this->ba->appAuth('rzp_test', 'api');
        return $this->makeRequestAndGetContent($request);
    }
}
