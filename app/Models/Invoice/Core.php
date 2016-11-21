<?php

namespace RZP\Models\Invoice;

use Mail;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(
            TraceCode::INVOICE_CREATE_REQUEST,
            $input
        );

        $invoice = (new Generator($this->merchant))->generate($input);

        $this->trace->info(
            TraceCode::INVOICE_CREATED,
            $invoice->toArrayPublic()
        );

        return $invoice;
    }

    public function update(Entity $invoice, array $input)
    {
        $this->checkIfInDrafStatus($invoice);

        $invoice->edit($input);

        (new Generator($this->merchant, $invoice))->ensureCustomerAssociation($input);

        $this->repo->saveOrFail($invoice);

        return $invoice;
    }

    public function delete(Entity $invoice)
    {
        $this->checkIfInDrafStatus($invoice);

        $this->repo->invoice->deleteOrFail($invoice);

        return true;
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
            'razorpay_payment_id' => Payment\Entity::getSignedId($paymentId)
        ];
    }

    public function getFormattedInvoiceData(Merchant\Entity $merchant, $invoiceId)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId = $invoice->getOrderId();

        $customer = $invoice->customer;

        $data['invoice'] = [
            'order_id'  => Order\Entity::getSignedId($orderId),
            'url'       => $invoice->getShortUrl()
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
}
