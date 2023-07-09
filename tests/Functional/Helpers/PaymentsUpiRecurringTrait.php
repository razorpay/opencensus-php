<?php

namespace RZP\Tests\Functional\Helpers;

use Mockery;
use Carbon\Carbon;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\UpiMandate;
use RZP\Models\Base\Entity;
use RZP\Models\Customer\Token;
use RZP\Services\Mock\Reminders;
use RZP\Models\Payment\UpiMetadata;
use RZP\Gateway\Mozart\Mock\Server;
use RZP\Exception\GatewayErrorException;

trait PaymentsUpiRecurringTrait
{
    use PaymentsUpiTrait;
    use DbEntityFetchTrait;

    protected $merchantId               = '10000000000000';

    protected $terminalId               = '1000SharpTrmnl';

    protected $customerId               = '100000customer';
    /**
     * @var Order\Entity
     */
    protected $order                    = null;
    /**
     * @var UpiMandate\Entity
     */
    protected $upiMandate               = null;
    /**
     * @var Token\Entity
     */
    protected $token                    = null;
    /**
     * @var UpiMetadata\Entity
     */
    protected $lastUpiMetadata          = null;
    protected $lastUpiRecurringEntities = [];
    protected $mockedReminderService    = null;

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
                'recurring_value' => 31,
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

    protected function createUpiRecurringTpvOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'bank_account'    => [
                'name'            => 'Test Recurring TPV',
                'account_number'  => '12345678921',
                'ifsc'            => 'ICIC0001183'
            ],
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
            'token'           => [
                'max_amount'      => 150000,
                'frequency'       => 'monthly',
                'recurring_type'  => 'before',
                'recurring_value' => 31,
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

        $payment = $this->getDbLastPayment();

        (new Payment\Service)->s2scallback($payment->getPublicId(), [
            'status'        => 'authorized',
            'rrn'           => '001000100002',
            'npci_txn_id'   => 'npci_txn_id_for_' . $payment->getId(),
        ]);

        // Basic Assertions, helpful for second recurring
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

    protected function sendReminderRequest(array $reminder, bool $assert = true)
    {
        $request = [
            'url'       => '/v1/' . $reminder['callback_url'],
            'method'    => 'post',
            'content'   => [],
        ];

        $this->ba->appAuth('rzp_test', 'api');

        $response =  $this->makeRequestAndGetContent($request);

        if ($assert)
        {
            $this->assertArrayHasKey('success' , $response, 'Failure on reminder request');
            $this->assertTrue($response['success'], 'Failure on reminder request');
        }

        return $response;
    }

    protected function createDbUpiOrder(array $oInput = [])
    {
        $oInput = array_merge([
            'merchant_id'       => $this->merchantId,
            'customer_id'       => '100000customer',
            'amount'            => 50000,
            'currency'          => 'INR',
            'method'            => 'upi',
            'payment_capture'   => 1,
            'status'            => Order\Status::CREATED,
            'notes'             => []
        ], $oInput);

        $this->order = new Order\Entity();
        $this->order->forceFill($oInput);
        $this->order->saveOrFail();
    }

    protected function createDbUpiMandate(array $mInput = [], array $oInput = [])
    {
        $this->createDbUpiOrder($oInput);

        $mInput = array_merge([
            'merchant_id'       => $this->merchantId,
            'customer_id'       => $this->customerId,
            'order_id'          => $this->order->getId(),
            'max_amount'        => 150000,
            'frequency'         => 'monthly',
            'recurring_type'    => 'before',
            'recurring_value'   => 31,
            'start_time'        => Carbon::now()->addDay(1)->getTimestamp(),
            'end_time'          => Carbon::now()->addDay(60)->getTimestamp(),
            'status'            => UpiMandate\Status::CREATED,
        ], $mInput);

        $this->upiMandate = new UpiMandate\Entity();
        $this->upiMandate->forceFill($mInput);
        $this->upiMandate->saveOrFail();
    }

    protected function createDbUpiToken(array $tInput = [], array $mInput = [])
    {
        $vpa = $this->createUpiPaymentsLocalCustomerVpa();

        $tInput = array_merge([
            'merchant_id'       => $this->merchantId,
            'customer_id'       => $this->customerId,
            'terminal_id'       => $this->terminalId,
            'token'             => '1000TokenToken',
            'recurring_status'  => 'confirmed',
            'method'            => 'upi',
            'recurring'         => 1,
            'vpa_id'            => $vpa->getId(),
        ], $tInput);

        $this->token = new Token\Entity();
        $this->token->forceFill($tInput);
        $this->token->saveOrFail();

        // When token is created the mandate is also updated
        $mInput = array_merge([
            'token_id'      => $this->token->getId(),
            'status'        => UpiMandate\Status::CONFIRMED,
            'umn'           => 'FirstUpiRecPayment@razorpay',
            'rrn'           => '001000100001',
            'confirmed_at'  => $this->getMockConfirmedAt($this->upiMandate->getFrequency()),
            'npci_txn_id'   => 'RZP12345678910111213141516',
            'used_count'    => 1,
        ], $mInput);

        $this->upiMandate->forceFill($mInput);
        $this->upiMandate->saveOrFail();

        // We are not making a payment just mocking the data
        // Idea is any subsequent logic should not rely on
        // existence of first payment rather status of mandate and token
        // Later if needed we add another helper to create a payment
    }

    protected function getDbUpiAutoRecurringPayment(array $override = [])
    {
        $payment = $this->getDefaultUpiRecurringPaymentArray();
        $payment['token'] = $this->token->getPublicId();

        $this->createDbUpiOrder();
        $payment['order_id'] = $this->order->getPublicId();
        unset($payment['vpa']);

        $payment = array_merge($payment, $override);

        return $payment;
    }

    protected function getUpiDbLastEntity($entity)
    {
        $this->lastUpiRecurringEntities[$entity] = $this->getDbLastEntity($entity);
        return $this->lastUpiRecurringEntities[$entity];
    }

    protected function assertUpiDbLastEntity(string $entity, array $actualDiff = [], bool $strict = true)
    {
        // Last one was never set
        if ((isset($this->lastUpiRecurringEntities[$entity]) === false) or
            ($strict === false))
        {
            $newEntity = $this->getUpiDbLastEntity($entity);
            $this->assertArraySubset($actualDiff, $newEntity->toArray(), true);
        }
        else
        {
            $newEntity = $this->getDbLastEntity($entity);
            $oldEntity = $this->lastUpiRecurringEntities[$entity];

            $oldEntity->forceFill($actualDiff);
            $expected = array_except($oldEntity->toArray(), $oldEntity->getEntityDates());

            $this->assertArraySubset($expected, $newEntity->toArray(), true);
        }

        return $newEntity;
    }

    protected function getAndAssertUpiDbEntity(string $entity, array $actualDiff = [], array $conditionParam)
    {
        $newEntity = $this->getDbEntity($entity, $conditionParam);
        if($newEntity !== null)
        {
            $this->assertArraySubset($actualDiff, $newEntity->toArray(), true);
        }
        return $newEntity;
    }

    protected function assertUpiMetadataStatus(string $status, UpiMetadata\Entity $entity = null)
    {
        if ($entity === null)
        {
            $entity = $this->getUpiDbLastEntity('upi_metadata');
        }

        $this->assertSame($status, $entity->getInternalStatus());
    }

    protected function assertReminderRequest(string $action, & $reminder, & $metadata)
    {
        // The request which we have sent to create the reminder
        $this->mockReminderService($action,
            function($request, $merchantId) use (& $reminder, & $metadata)
            {
                $reminder = $request;
                $metadata = $this->getUpiDbLastEntity('upi_metadata');

                $this->assertSame($metadata['remind_at'], $metadata->getRemindAt());
            });
    }

    protected function buildGatewayErrorDescription($message = null, $code = null, $description = null)
    {
        return implode("\n", [
            $message ?? 'Payment processing failed due to error at bank or wallet gateway',
            'Gateway Error Code: ' . $code,
            'Gateway Error Desc: ' . $description,
        ]);
    }

    protected function makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway = null)
    {
        try
        {
            return $this->makeS2SCallbackAndGetContent($content, $gateway, true);
        }
        catch (GatewayErrorException $exception)
        {
            $this->assertTrue(in_array(
                $gateway,
                [
                    Payment\Gateway::UPI_ICICI,
                ],
                'Exception should not be thrown in callback for ' . $gateway));

            return $exception;
        }
    }

    protected function mockMozartServer(): Server
    {
        return $this->getMockServer();
    }

    protected function getMockConfirmedAt($frequency)
    {
        switch ($frequency) {
            case UpiMandate\Frequency::MONTHLY:
                $confirmedAt = Carbon::parse('first day of last month', 'UTC');
                break;

            case UpiMandate\Frequency::AS_PRESENTED:
                //Choose a time between now() and 10 minutes.
                $confirmedAt = Carbon::now()->addMinute(2);
                break;

            default:
                $confirmedAt = Carbon::now();
                break;
        }

        return $confirmedAt->timestamp;
    }
}
