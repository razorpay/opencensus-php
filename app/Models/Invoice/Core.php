<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $lineItemCore;

    public function __construct()
    {
        parent::__construct();

        $this->lineItemCore = new LineItem\Core;
    }

    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_CREATE_REQUEST,
            $input
        );

        if ((empty($input[Entity::DRAFT]) === false) and
            ($input[Entity::DRAFT] === '1'))
        {
            $invoice = (new Generator($merchant))->generateDraft($input);
        }
        else
        {
            $invoice = (new Generator($merchant))->generate($input);
        }

        $this->trace->info(
            TraceCode::INVOICE_CREATED,
            $invoice->toArrayPublic()
        );

        return $invoice;
    }

    public function update(Entity $invoice, array $input, Merchant\Entity $merchant)
    {
        $status = $invoice->getStatus();

        $ruleValidator = 'edit_' . $status;

        $invoice->edit($input, $ruleValidator);

        $updateFunction = 'update_' . studly_case($status) . 'Invoice';

        $this->$updateFunction($invoice, $input);

        $this->repo->saveOrFail($invoice);

        // $this->repo->transaction(
        //     function() use ($invoice, $merchant, $input)
        //     {
        //         $invoice->edit($input);
        //
        //         $this->consumeExtraInputKeys($invoice, $input);
        //
        //         (new Generator($merchant, $invoice))->update($input);
        //
        //         $this->repo->saveOrFail($invoice);
        //     }
        // );

        return $invoice;
    }

    public function updateDraftInvoice(Entity $invoice, array $input)
    {
        // TODO: email and sms status should be generated based on the update input received
        // ref_num uniques check and proper error to be thrown
        // handle customer edits in draft here
        // ensure this whole thing is in transaction since customer may also get created here
    }

    public function updateIssuedInvoice(Entity $invoice, array $input)
    {
        // TODO: ref_num unique check

        // $invoice->getValidator()->validateOperation();
    }

    public function delete(Entity $invoice)
    {
        $invoice->getValidator()
                ->validateOperation($invoice->getStatus());

        $this->repo->invoice->deleteOrFail($invoice);

        return [];
    }

    public function addLineItem(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant)
    {
        $invoice->getValidator()
                ->validateOperation($invoice->getStatus());

        $this->repo->transaction(
            function() use ($invoice, $input, $merchant)
            {
                $this->lineItemCore->create($input, $merchant, $invoice);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            }
        );

        return $invoice;
    }

    public function updateLineItem(
        Entity $invoice,
        LineItem\Entity $lineItem,
        array $input,
        Merchant\Entity $merchant)
    {
        $invoice->getValidator()
                ->validateOperation($invoice->getStatus());

        $this->repo->transaction(
            function() use ($invoice, $lineItem, $input, $merchant)
            {
                $this->lineItemCore->update(
                    $lineItem,
                    $input,
                    $merchant,
                    $invoice
                );

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            }
        );

        return $invoice;
    }

    public function removeLineItem(
        Entity $invoice,
        LineItem\Entity $lineItem)
    {
        $invoice->getValidator()
                ->validateOperation($invoice->getStatus());

        $this->repo->transaction(
            function() use ($lineItem, $invoice)
            {
                $this->lineItemCore->delete($lineItem);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            }
        );

        return $invoice;
    }

    public function sendNotification(Entity $invoice, $medium)
    {
        $this->trace->info(
            TraceCode::INVOICE_SEND_NOTIFICATION,
            [
                'invoice_id' => $invoice->getId(),
                'medium'     => $medium,
            ]);

        $invoice->getValidator()
                ->validateSendNotificationRequest($invoice, $medium);

        $notifier = new Notifier($invoice);
        $commFunc = 'send' . studly_case($medium) . 'NotificationToCustomer';

        $response = $notifier->$commFunc();

        $this->repo->saveOrFail($invoice);

        return ['success' => $response];
    }

    public function expireInvoices()
    {
        $expiredInvoices = $this->repo->invoice->getExpiredInvoices();

        // TODO: Ensure that when the payment is being made,
        //       the invoice is in `issued` state only.
        foreach ($expiredInvoices as $expiredInvoice)
        {
            $expiredInvoice->setStatus(Status::EXPIRED);
            $this->repo->saveOrFail($expiredInvoice);
        }

        $summary = [
            'total'         => $expiredInvoices->count(),
            'invoice_ids'   => $expiredInvoices->getIds(),
        ];

        $this->trace->info(
            TraceCode::EXPIRE_INVOICES,
            $summary
        );

        return $summary;
    }

    public function fetchStatus(Entity $invoice)
    {
        $paymentId = $invoice->getPaymentId();

        $invoiceStatus = $invoice->getStatus();

        if ($invoiceStatus !== Status::PAID)
        {
            return [
                Entity::STATUS => $invoice->getStatus()
            ];
        }

        return [
            'razorpay_payment_id' => $paymentId
        ];
    }

    public function getFormattedInvoiceData($invoiceId, Merchant\Entity $merchant)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId = $invoice->getOrderId();

        $customer = $invoice->customer;

        $data['invoice'] = [
            'order_id'  => Order\Entity::getSignedId($orderId),
            'url'       => $invoice->getShortUrl(),
            'amount'    => $invoice->getAmount(),
        ];

        if ($customer)
        {
            $data['customer'] = $customer->toArrayPublic();
        }

        return $data;
    }

    /**
     * Pulls customer details from payment entity if
     * does not exist already or is not created during
     * invoice creation.
     *
     * @param Payment\Entity $payment
     *
     * @return Payment\Entity
     */
    public function setCustomerDetailsFromPaymentIfAbsent(Payment\Entity $payment)
    {
        $invoice = $payment->order->invoice;

        // If invoice is already associated with a customer,
        // don't do anything.
        if ($invoice->customer)
        {
            return;
        }

        // If payment has a customer associated, then associate that to invoice.
        // Otherwise simply copy the email and contact details from payment.

        $paymentCustomer = $payment->customer;

        if ($paymentCustomer !== null)
        {
            $invoice->customer()->associate($paymentCustomer);
            $invoice->setCustomerDetails($paymentCustomer);
        }
        else
        {
            $invoice->setCustomerEmail($payment->getEmail());
            $invoice->setCustomerContact($payment->getContact());
        }

        $this->repo->saveOrFail($invoice);
    }

    // -------------------- Protected methods --------------------

    /**
     * Whenever invoice gets updated via add/update/delete of it's line items,
     * The invoice amount is calculated and set again.
     *
     */
    protected function recomputeInvoiceAmount(Entity $invoice)
    {
        $totalAmount = 0;

        foreach ($invoice->lineItems()->get() as $lineItem) {

            $totalAmount += ($lineItem->getQuantity() * $lineItem->item->getAmount());
        }

        $invoice->setAmount($totalAmount);
    }

    /**
     * Invoice/Entity has few generators which are dependent on extra request
     *     input keys. Those need to be run again in case of put request.
     *
     */
    protected function consumeExtraInputKeys(Entity $invoice, array $input)
    {
        $invoice->generateStatus($input);
        $invoice->generateEmailStatus($input);
        $invoice->generateSmsStatus($input);
    }
}
