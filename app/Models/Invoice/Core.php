<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->lineItemCore = new LineItem\Core();
    }

    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_CREATE_REQUEST,
            $input
        );

        $invoice = (new Generator($merchant))->generate($input);

        $this->trace->info(
            TraceCode::INVOICE_CREATED,
            $invoice->toArrayPublic()
        );

        return $invoice;
    }

    public function update(Entity $invoice, array $input, Merchant\Entity $merchant)
    {
        $this->checkIfInDrafStatus($invoice);

        $this->repo->transaction(
            function() use ($invoice, $input)
            {
                $invoice->edit($input);

                (new Generator($merchant, $invoice))->ensureCustomerAssociation($input);

                $this->repo->saveOrFail($invoice);
            }
        );

        return $invoice;
    }

    public function issue(Entity $invoice, Merchant\Entity $merchant)
    {
        $this->checkIfInvoiceCanBeIssued($invoice);

        $invoice = (new Generator($merchant, $invoice))->issue();

        $this->repo->saveOrFail($invoice);

        return $invoice;
    }

    public function delete(Entity $invoice)
    {
        $this->checkIfInDrafStatus($invoice);

        $this->repo->invoice->deleteOrFail($invoice);

        return true;
    }

    public function addLineItem(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant)
    {
        $this->checkIfInDrafStatus($invoice);

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
        $this->checkIfInDrafStatus($invoice);

        $this->repo->transaction(
            function() use ($invoice, $lineItem, $input, $merchant)
            {
                $this->lineItemCore->update($lineItem, $input, $merchant, $invoice);

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
        $this->checkIfInDrafStatus($invoice);

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

        $notifier = new Notifier($invoice);
        $commFunc = 'send' . studly_case($medium) . 'NotificationToCustomer';

        if (method_exists($notifier, $commFunc) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Not a valid medium");
        }

        $response = $notifier->$commFunc();

        $this->repo->saveOrFail($invoice);

        return ['success' => $response];
    }

    public function expireInvoices()
    {
        $expiredInvoices = $this->repo->invoice->getExpiredInvoices();

        // TODO: Ensure that when the payment is being made, the invoice is in `issued` state only.
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

    public function getFormattedInvoiceData(Merchant\Entity $merchant, $invoiceId)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId = $invoice->getOrderId();

        $customer = $invoice->customer;

        $data['invoice'] = [
            'order_id'  => Order\Entity::getSignedId($orderId),
            'url'       => $invoice->getShortUrl(),
            'amount'    => $invoice->getAmount(),
        ];

        $data['customer'] = $customer->toArrayPublic();

        return $data;
    }



    // -------------------- Protected methods --------------------

    protected function checkIfInDrafStatus(Entity $invoice)
    {
        if ($invoice->getStatus() !== Status::DRAFT)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVOICE_EDIT_NOT_ALLOWED);
        }
    }

    protected function checkIfInvoiceCanBeIssued(Entity $invoice)
    {
        // Ensure:
        // - In draft status
        // - Customer associated
        // - Line items exists

        $this->checkIfInDrafStatus($invoice);

        if (empty($invoice->customer) or $invoice->lineItems()->count() === 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVOICE_ISSUE_NOT_ALLOWED);
        }
    }

    protected function recomputeInvoiceAmount(Entity $invoice)
    {
        $totalAmount = 0;

        foreach ($invoice->lineItems()->get() as $lineItem) {

            $totalAmount += ($lineItem->getQuantity() * $lineItem->item->getAmount());
        }

        $invoice->setAmount($totalAmount);
    }
}
