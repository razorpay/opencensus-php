<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    protected $lineItemCore;

    //
    // Class property to accumulate summary of bulk invoice expiration
    // and send as api response.
    //
    protected $expireInvoicesSummary;

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

        $invoice = (new Generator($merchant))->generate($input);

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

        //
        // Once basic fill by edit call on entity is done, Based on invoice status,
        // it calls either updateDraftInvoice|updateIssuedInvoice.
        //
        // This was done to maintain flow clean. Because if not now, there are chances
        // we want to handle different things in different case.
        //
        // This is neat base code for that.
        //

        $operation = 'edit' . studly_case($status);

        try
        {
            $invoice->edit($input, $operation);

            $updateFunction = 'update' . studly_case($status) . 'Invoice';

            $this->$updateFunction($merchant, $invoice, $input);
        }
        catch (\Exception $e)
        {
            ExceptionHandler::handleMySqlUniqueError($e, $invoice, $input);
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
            });

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

    public function addLineItems(
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
                $this->lineItemCore->createMany($input, $merchant, $invoice);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

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
            ]);

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
            });

        return $invoice;
    }

    public function removeLineItem(Entity $invoice, LineItem\Entity $lineItem)
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
            });

        return $invoice;
    }

    public function removeManyLineItems(Entity $invoice, Base\PublicCollection $lineItems)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_REMOVE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_ids'  => $lineItems->pluck('id')->toArray(),
            ]);

        $this->repo->transaction(
            function() use ($lineItems, $invoice)
            {
                $this->lineItemCore->deleteMany($lineItems);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

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

    public function expireInvoice(Entity $invoice)
    {
        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdate($invoice->getId());

                $this->expireInvoiceAfterChecks($invoice);
            });

        $this->trace->info(
            TraceCode::EXPIRE_INVOICE,
            [
                'invoice_id' => $invoice->getId(),
            ]);

        return $invoice;
    }

    /**
     * Called from cron.
     * Expires all invoices which are issued and past expired_by.
     *
     * @return array
     */
    public function expireInvoices()
    {
        $this->expireInvoicesSummary = [
            'count'      => 0,
            'ids'        => [],
            'failed_ids' => [],
        ];

        $this->repo->transaction(
            function ()
            {
                $invoices = $this->repo->invoice->getIssuedAndPastExpiredByInvocies();

                $this->expireInvoicesSummary['count'] = $invoices->count();
                $this->expireInvoicesSummary['ids']   = $invoices->getIds();

                foreach ($invoices as $invoice)
                {
                    try
                    {
                        $this->expireInvoiceAfterChecks($invoice);
                    }
                    catch (\Exception $e)
                    {
                        $this->trace->traceException($e);

                        $this->expireInvoicesSummary['failed_ids'][] = $invoice->getId();
                    }
                }
            });

        $this->trace->info(TraceCode::EXPIRE_INVOICES_CRON, $this->expireInvoicesSummary);

        return $this->expireInvoicesSummary;
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
            });
    }

    protected function updateIssuedInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->saveOrFail($invoice);
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
        $totalAmount = $this->lineItemCore->getTotalAmountOfLineItems($invoice);

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

    protected function expireInvoiceAfterChecks(Entity $invoice)
    {
        $this->raiseErrorIfInvoiceCannotBeExpired($invoice);

        $invoice->setStatus(Status::EXPIRED);

        $this->repo->saveOrFail($invoice);
    }

    /**
     * Raises BadRequestException if invoice cannot be expired.
     * If invoice has any created/authorized/captured/refunded payments associated
     * then we don't expire those invoices.
     *
     * This method gets called after aquiring FOR UPDATE lock on invoice, and so
     * avoids bad reads from other flows (eg. payment creation/ auto capture of
     * late authorized payments via cron, there we check for invoice status.)
     *
     * @param Entity $invoice
     *
     * @return null
     *
     * @throws Exception\BadRequestException
     */
    protected function raiseErrorIfInvoiceCannotBeExpired(Entity $invoice)
    {
        $payments = $invoice->payments()
                            ->whereIn(
                                    Payment\Entity::STATUS,
                                    [
                                        Payment\Status::CREATED,
                                        Payment\Status::AUTHORIZED,
                                        Payment\Status::CAPTURED,
                                        Payment\Status::REFUNDED,
                                    ])
                            ->get();

        if ($payments->count() !== 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_EXPIRE_FAILED,
                null,
                [
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }
}
