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
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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

        $operation = Validator::CREATE_ISSUED;

        if ((isset($input[Entity::DRAFT])) and
            ($input[Entity::DRAFT]) === '1')
        {
            $operation = Validator::CREATE_DRAFT;
        }

        $invoice = (new Generator($merchant))->generate($input, $operation);

        $this->trace->info(
            TraceCode::INVOICE_CREATED,
            $invoice->toArrayPublic()
        );

        return $invoice;
    }

    public function update(Entity $invoice, array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::INVOICE_UPDATE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

        $status = $invoice->getStatus();

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $operation = 'edit' . studly_case($status);

        try
        {
            $invoice->edit($input, $operation);

            $updateFunction = 'update' . studly_case($status) . 'Invoice';

            $this->$updateFunction($merchant, $invoice, $input);
        }
        catch (\Exception $e)
        {
            // Check if is Mysql duplicate on unique index error
            if (($e instanceof \Illuminate\Database\QueryException) and
                ($e->errorInfo[1] === 1062))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_DUPLICATE_INVOICE_RECEIPT,
                    null,
                    [
                        'invoice_id'    => $invoice->getId(),
                        'input'         => $input,
                    ]);
            }

            throw $e;
        }

        return $invoice;
    }

    public function issue(Entity $invoice, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_ISSUE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        $this->repo->transaction(
            function() use ($invoice, $merchant)
            {
                (new Generator($merchant, $invoice))->issueInvoice();

                $this->repo->saveOrFail($invoice);
            }
        );

        (new Notifier($invoice))->sendNotificationToCustomer();

        return $invoice;
    }

    public function delete(Entity $invoice)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_DELETE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        return $this->repo->invoice->deleteOrFail($invoice);
    }

    public function addLineItem(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_ADD_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

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
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_UPDATE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_id'   => $lineItem->getId(),
                'input'          => $input,
            ]
        );

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
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_REMOVE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_id'   => $lineItem->getId(),
            ]
        );

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
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'medium'         => $medium,
            ]);

        $invoice->getValidator()->validateSendNotificationRequest($medium);

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

        if ($invoice->hasBeenPaid() === false)
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

    protected function updateDraftInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->transaction(
            function() use ($merchant, $invoice, $input)
            {
                $this->generateAttributesOnUpdate($invoice, $input);

                (new Generator($merchant, $invoice))->updateDraftInvoice($input);

                $this->repo->saveOrFail($invoice);
            }
        );
    }

    protected function updateIssuedInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        ;
    }

    /**
     * Whenever invoice gets updated via add/update/delete of it's line items,
     * The invoice amount is calculated and set again.
     *
     * We don't need to set order amount here because order is created only in
     * issued state and recomputing invoice amount happens in draft state.
     *
     * @param Entity $invoice
     */
    protected function recomputeInvoiceAmount(Entity $invoice)
    {
        $totalAmount = $this->lineItemCore->getInvoiceAmountForLineItems($invoice->lineItems()->get());

        $invoice->setAmount($totalAmount);
    }

    /**
     * Invoice/Entity has few generators which are dependent on extra request
     * input keys. Those need to be run again in case of put request.
     *
     * @param Entity $invoice
     * @param array  $input
     */
    protected function generateAttributesOnUpdate(Entity $invoice, array $input)
    {
        if (isset($input[Entity::EMAIL_NOTIFY]))
        {
            $invoice->generateEmailStatus($input);
        }

        if (isset($input[Entity::SMS_NOTIFY]))
        {
            $invoice->generateSmsStatus($input);
        }
    }
}
