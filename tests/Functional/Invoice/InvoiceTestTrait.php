<?php

namespace RZP\Tests\Functional\Invoice;

use Closure;
use Mockery;

use RZP\Jobs;
use RZP\Models\Merchant\Webhook\Inferno;

trait InvoiceTestTrait
{
    protected function createDraftInvoice(array $with = [])
    {
        return $this->fixtures->create(
            'invoice',
            array_merge(
                [
                    'type'         => 'invoice',
                    'status'       => 'draft',
                    'order_id'     => null,
                    'short_url'    => null,
                    'amount'       => null,
                    'sms_status'   => 'pending',
                    'email_status' => 'pending',
                ],
                $with
            )
        );
    }

    protected function createIssuedInvoice(array $with = [])
    {
        return $this->fixtures->create(
            'invoice',
            array_merge(
                [
                    'status' => 'issued'
                ],
                $with
            )
        );
    }

    /**
     * Helper method to make payment for given invoice and do the necessary
     * assertions.
     */
    protected function makePaymentForInvoiceAndAssert(array $invoice)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $invoice['order_id'];
        $payment['amount']   = $invoice['amount'];

        $payment = $this->doAuthAndGetPayment(
            $payment,
            [
                'status'   => 'captured',
                'order_id' => $invoice['order_id'],
            ]
        );

        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        return $payment;
    }

    /**
     * Returns expected upsert index params for ES client method bulkUpsert method.
     *
     * @param array $with
     *
     * @return array
     */
    protected function getExpectedUpsertIndexParams($with = [])
    {
        $expected = $this->testData['expectedUpsertIndexParams'];

        if (array_key_exists('id', $with))
        {
            $expected['body'][0]['index']['_id'] = $with['id'];
        }

        $expected['body'][1] = array_merge($expected['body'][1], $with);

        return $expected;
    }

    protected function createInfernoMock(array $withMethods = ['fire']): Inferno
    {
        $infernoMock = $this->getMockBuilder(Inferno::class)
                            ->setMethods($withMethods)
                            ->getMock();

        $this->app->instance('webhook.inferno', $infernoMock);

        return $infernoMock;
    }

    /**
     * Sets mocked inferno instance expectations for fire() method.
     *
     * @param Inferno $infernoMock
     * @param array   $testDataKeys
     */
    protected function setMockedInfernoExpectations(Inferno $infernoMock, array $testDataKeys)
    {
        $times = 0;

        // 1st argument of fire method is a job class of type Jobs\Webhook
        $arg1Callback = function ($job)
                        {
                            return $job instanceof Jobs\WebHook;
                        };

        //
        // Iterates over the test data keys and sets callback to assert the 2nd argument
        // of every consecutive call of inferno's fire() method.
        //
        foreach ($testDataKeys as $testDataKey)
        {
            $times = $times + 1;

            $arg2Callback = function ($actualWebhook) use ($testDataKey)
                            {
                                $expectedEvent = $this->testData[$testDataKey];
                                $actualEvent   = json_decode($actualWebhook['event'], true);

                                $this->assertArraySelectiveEquals($expectedEvent, $actualEvent);

                                return true;
                            };

            $with[] = [$this->callback($arg1Callback), $this->callback($arg2Callback)];
        }

        $infernoMock->expects($this->exactly($times))
                    ->method('fire')
                    ->withConsecutive(...$with);
    }
}
