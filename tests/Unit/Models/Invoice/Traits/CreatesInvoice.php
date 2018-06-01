<?php

namespace RZP\Tests\Unit\Models\Invoice\Traits;

use RZP\Models\Invoice;

/**
 * Contains helper methods which get used in Invoice related unit tests.
 */
trait CreatesInvoice
{
    public function createInvoice(
        array $invoiceAttributes = [],
        array $orderAttributes = []): Invoice\Entity
    {
        $defaultOrderAttributes = [
            'id'              => '100000000order',
            'amount'          => 100000,
            'payment_capture' => true,
        ];
        $orderAttributes = array_merge($defaultOrderAttributes, $orderAttributes);

        $order = $this->fixtures->create('order', $orderAttributes);

        $defaultInvoiceAttributes = [
            'issued_at'    => time(),
            'date'         => time(),
            'tax_amount'   => 0,
            'gross_amount' => 0,
        ];
        $invoiceAttributes = array_merge($defaultInvoiceAttributes, $invoiceAttributes);

        return $this->fixtures->create('invoice', $invoiceAttributes);
    }

    public function createInvoiceWithPayment(): Invoice\Entity
    {
        $invoiceAttributes = [
            'status'  => 'paid',
            'paid_at' => time(),
        ];
        $orderAttributes = [
            'status'      => 'paid',
            'amount_paid' => 100000,
            'attempts'    => 1,
        ];

        $invoice = $this->createInvoice($invoiceAttributes, $orderAttributes);

        $paymentAttributes = [
            'invoice_id' => '1000000invoice',
            'order_id'   => '100000000order',
        ];

        $this->fixtures->create('payment:captured', $paymentAttributes);

        return $invoice;
    }

    public function createSubscriptionInvoice(): Invoice\Entity
    {
        $subscriptionAttributes = [
            'id'          => '10subscription',
            'plan_id'     => '1000000000plan',
            'schedule_id' => '100000schedule',
        ];

        $schedule     = $this->fixtures->create('schedule', ['id' => '100000schedule']);
        $plan         = $this->fixtures->plan->create();
        $subscription = $this->fixtures->create('subscription', $subscriptionAttributes);
        $invoice      = $this->createInvoice();

        $invoice->subscription()->associate($subscription);

        return $invoice;
    }
}
