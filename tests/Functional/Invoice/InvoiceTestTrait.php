<?php

namespace RZP\Tests\Functional\Invoice;

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


    protected function makePaymentForInvoiceAndAssertForMyMerchant(array $invoice)
    {
        $payment = $this->getDefaultPaymentArrayForMYMerchant();

        $this->app['config']->set('applications.pg_router.mock', true);

        $payment['order_id'] = $invoice['order_id'];
        $payment['amount']   = $invoice['amount'];
        $payment['invoice_id']   = $invoice['id'];

        $payment = $this->doAuthAndGetPaymentForMyMerchant(
            $payment,
            [
                'status'   => 'captured',
                'order_id' => $invoice['order_id'],
                'invoice_id' => $invoice['id'],
            ]
        );

        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');

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
}
