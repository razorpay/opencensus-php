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

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(Mockery::type(Jobs\WebHook::class), Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }
}
