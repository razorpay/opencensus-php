<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Models\FileStore;
use RZP\Jobs\DispatchRouter;
use RZP\Models\Plan\Subscription;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Jobs\Invoice\BatchIssue as InvoiceBatchIssueJob;

class Core extends Base\Core
{
    const QUEUE_JOB_DELAY              = 5; // In seconds
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;

    //
    // When someone requests pdf version of invoice we use this factor
    // to determine if we should create new latest pdf in sync or use already
    // created one. Whenever invoice gets updated we update pdf version over queue.
    //
    const MAX_EXPECTED_QUEUE_DELAY     = 360; // In seconds (= 6 minutes)

    protected $lineItemCore;
    protected $pdfGenerator;
    protected $slack;
    protected $slackTechLogsChannel;
    protected $eventService;

    public function __construct()
    {
        parent::__construct();

        $this->lineItemCore         = new LineItem\Core;
        $this->pdfGenerator         = null;
        $this->slack                = $this->app['slack'];
        $this->slackTechLogsChannel = Config::get('slack.channels.tech_logs');
        $this->eventService         = $this->app['events'];
    }

    public function setPdfGenerator(Entity $invoice)
    {
        $this->pdfGenerator = new PdfGenerator($invoice);
    }

    /**
     * Creates invoice
     *
     * @param array               $input
     * @param Merchant\Entity     $merchant
     * @param Subscription\Entity $subscription - If created via subscription, this
     *                                            is passed for associations.
     * @param Batch\Entity        $batch        - If created via batch flow, this
     *                                            is passed for association.
     *
     * @return Entity
     */
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Subscription\Entity $subscription = null,
        Batch\Entity $batch = null): Entity
    {
        $this->trace->info(TraceCode::INVOICE_CREATE_REQUEST, $input);

        $this->modifyInputToHandleRenamedAttributes($input);

        $invoice = (new Generator($merchant))
                        ->setSubscription($subscription)
                        ->setBatch($batch)
                        ->generate($input);

        $this->trace->info(TraceCode::INVOICE_CREATED, $invoice->toArrayPublic());

        $this->repo->loadRelations($invoice);

        if ($invoice->isIssued())
        {
            $job = new InvoiceJob($this->mode, InvoiceJob::ISSUED, $invoice->getId());

            //
            // In cases we push job over queue with delay factor of 2 seconds.
            // This is done because some methods of this Core gets called internally
            // by other products (eg. subscriptions) and in their core they finish few
            // other stuffs as well before commiting DB transactions.
            //
            if ($subscription !== null)
            {
                $job->delay(self::QUEUE_JOB_DELAY);
            }

            (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);
        }

        return $invoice;
    }

    public function update(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::INVOICE_UPDATE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

        $this->modifyInputToHandleRenamedAttributes($input);

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

        $this->repo->loadRelations($invoice);

        if ($invoice->isIssued())
        {
            $job = new InvoiceJob(
                        $this->mode,
                        InvoiceJob::UPDATED,
                        $invoice->getId());

            (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);
        }

        return $invoice;
    }

    public function issue(Entity $invoice, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::INVOICE_ISSUE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function() use ($invoice, $merchant)
            {
                (new Generator($merchant, $invoice))->issueInvoice();

                $this->repo->saveOrFail($invoice);
            });

        $job = new InvoiceJob(
                    $this->mode,
                    InvoiceJob::ISSUED,
                    $invoice->getId());

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);

        return $invoice;
    }

    public function delete(Entity $invoice)
    {
        $this->trace->info(
            TraceCode::INVOICE_DELETE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        return $this->repo->invoice->deleteOrFail($invoice);
    }

    public function addLineItems(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::INVOICE_ADD_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function() use ($invoice, $input, $merchant)
            {
                $this->lineItemCore->createMany($input, $merchant, $invoice);

                $this->calculateAndSetAmountsOfInvoice($invoice);

                $this->repo->saveOrFail($invoice);
            });

        return $this->repo->loadRelations($invoice);
    }

    public function updateLineItem(
        Entity $invoice,
        LineItem\Entity $lineItem,
        array $input,
        Merchant\Entity $merchant): Entity
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

                $this->calculateAndSetAmountsOfInvoice($invoice);

                $this->repo->saveOrFail($invoice);
            });

        return $this->repo->loadRelations($invoice);
    }

    public function removeLineItem(
        Entity $invoice,
        LineItem\Entity $lineItem): Entity
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
                $this->lineItemCore->delete($lineItem, $invoice);

                $this->calculateAndSetAmountsOfInvoice($invoice);

                $this->repo->saveOrFail($invoice);
            });

        return $this->repo->loadRelations($invoice);
    }

    public function removeManyLineItems(
        Entity $invoice,
        Base\PublicCollection $lineItems): Entity
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

                $this->calculateAndSetAmountsOfInvoice($invoice);

                $this->repo->saveOrFail($invoice);
            });

        return $this->repo->loadRelations($invoice);
    }

    public function sendNotification(Entity $invoice, string $medium): array
    {
        $this->trace->info(
            TraceCode::INVOICE_SEND_NOTIFICATION,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'medium'         => $medium,
            ]);

        $invoice->getValidator()->validateSendNotificationRequest($medium);

        $func = studly_case($medium) . 'InvoiceIssuedToCustomer';

        $pdfPath = null;

        if ($medium === NotifyMedium::EMAIL)
        {
            $pdfPath = $this->getFreshInvoicePdf($invoice);
        }

        $response = (new Notifier($invoice, $pdfPath))->$func();

        $this->repo->saveOrFail($invoice);

        return ['success' => $response];
    }

    public function cancelInvoice(Entity $invoice): Entity
    {
        $this->trace->info(
            TraceCode::CANCEL_INVOICE,
            [
                'id' => $invoice->getId(),
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                $this->validateIfInvoiceCanBeCancelled($invoice);

                $invoice->setStatus(Status::CANCELLED);

                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }

    /**
     * Called from CRON.
     * Expires all invoices which are issued and past expire_by.
     *
     * @return array
     */
    public function expireInvoices(): array
    {
        $time = time();

        $invoices = $this->repo->invoice->getIssuedAndPastExpiredByInvoices();

        $summary = [
            'total_invoices_count' => $invoices->count(),
            'failed_invoice_ids'   => [],
        ];

        foreach ($invoices as $invoice)
        {
            try
            {
                $this->expireInvoice($invoice);
            }
            catch (\Exception $e)
            {
                $summary['failed_invoice_ids'][] = $invoice->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::INVOICE_EXPIRE_VIA_CRON_FAILED,
                    ['id' => $invoice->getId()]
                );
            }
        }

        $time = time() - $time;

        $summary['time_taken'] = $time . ' secs';

        $this->trace->debug(TraceCode::INVOICES_EXPIRE_CRON_SUMMARY, $summary);

        $slackMessage = 'Invoices past expire_by, marked expired via cron.';

        $this->slack->queue($slackMessage, $summary, ['channel' => $this->slackTechLogsChannel]);

        return $summary;
    }

    /**
     * Expires individual invoice by locking it for update.
     *
     * @param Entity $invoice
     *
     */
    protected function expireInvoice(Entity $invoice)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                $this->validateIfInvoiceCanBeExpired($invoice);

                $invoice->setStatus(Status::EXPIRED);

                $this->repo->saveOrFail($invoice);
            });

        $job = new InvoiceJob(
                        $this->mode,
                        InvoiceJob::EXPIRED,
                        $invoice->getId());

        // Sends expiration mails to customer asynchronously
        (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);

        $this->eventService->fire('api.invoice.expired', [$invoice]);
    }

    public function fetchStatus(Entity $invoice): array
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

    /**
     * Returns formatted invoice data for checkout usage.
     * Includes:
     * - Invoice basic attributes
     * - Order amount fields
     * - Customer details
     *
     * @param string          $invoiceId
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getFormattedInvoiceData(
        string $invoiceId,
        Merchant\Entity $merchant): array
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId       = $invoice->getOrderId();
        $publicOrderId = Order\Entity::getSignedId($orderId);

        $customer = $invoice->customer;

        // Currently EPOS application usage following attributes.
        //
        // Later amount specific attributes e.g. amount, amount_paid and amount_due
        // etc would be send as part of 'order' key in checkout preferences. When
        // EPOS starts supporting partial payment they will start consuming
        // 'order' key and amount fields from here can be removed.
        //
        // Here, we would only append invoice specific stuff needed additionally.
        // It's easier this way. As with partial payment on order checkout would
        // find it easy to manage amounts for all cases with one data point.

        $data['invoice'] = [
            Entity::ORDER_ID => $publicOrderId,
            Entity::URL      => $invoice->getShortUrl(),
            Entity::AMOUNT   => $invoice->getAmount(),
        ];

        // Add order data

        $data['order'] = (new Order\Core)->getFormattedDataForCheckout(
                                                $publicOrderId,
                                                $merchant);

        // Add customer data if available

        if ($customer !== null)
        {
            $data[Entity::CUSTOMER] = $customer->toArrayPublic();
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
            $invoice->associateAndSetCustomerDetails($paymentCustomer);
        }
        else
        {
            $invoice->setCustomerEmail($payment->getEmail());
            $invoice->setCustomerContact($payment->getContact());
        }

        $this->repo->saveOrFail($invoice);
    }

    /**
     * Gets fresh invoice pdf.
     * Considers MAX_EXPECTED_QUEUE_DELAY as the max time our queue can take to
     * process job and update the invoice, and if pdf needs to be viewed (sync
     * call, non frequent) directly we use this method to ensure we see the updated
     * version.
     *
     * @param Entity $invoice
     *
     * @return string|null
     */
    public function getFreshInvoicePdf(Entity $invoice)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        $now = Carbon::now()->getTimestamp();

        if ($now - $invoice->getUpdatedAt() <= self::MAX_EXPECTED_QUEUE_DELAY)
        {
            $this->trace->debug(TraceCode::INVOICE_PDF_GEN_SYNC, ['id' => $invoice->getId()]);

            return $this->createInvoicePdf($invoice);
        }
        else
        {
            return $this->getInvoicePdfIfExistsOrCreate($invoice);
        }
    }

    public function getInvoicePdfIfExistsOrCreate(Entity $invoice)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        $pdfPath = $this->getInvoicePdf($invoice);

        if ($pdfPath !== null)
        {
            return $pdfPath;
        }

        return $this->createInvoicePdf($invoice);
    }

    public function getInvoicePdf(Entity $invoice)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        $pdf = $invoice->pdf();

        if ($pdf === null)
        {
            return null;
        }

        return (new FileStore\Accessor)
                    ->id($pdf->getId())
                    ->merchantId($invoice->getMerchantId())
                    ->getFile();
    }

    public function createInvoicePdf(Entity $invoice)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        //
        // Single PdfGenerator instance created as part of this class's member,
        // used multiple times in following line with retry.
        //

        $this->setPdfGenerator($invoice);

        return $this->generatePdfWithRetry($invoice->getId());
    }

    /**
     * Issues all invoices of given $batch, if list of invoice ids are sent
     * that is used (ensuring those ids are of given batch).
     *
     * The method returns success and the actual issue happens asynchronously
     * in a queue job.
     *
     * @param Batch\Entity $batch
     * @param array        $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function issueInvoicesOfBatch(Batch\Entity $batch, array $input): array
    {
        //
        // There is an action of 'Issue all payment links' of a processed(created
        // in draft state) payment link batch. But currently this action is not
        // saved anywhere and so can be called multiple times on given processed batch.
        // There is validation in the flow to not issue already issued invoice, but
        // following check will throw error in advance if there is any non draft status
        // invoices against the given batch.
        //

        $batchId = $batch->getId();

        $nonDraftInvCount = $this->repo->invoice
                                       ->getNonDraftInvoiceCountByBatchId($batchId);

        if ($nonDraftInvCount > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_LINK_BATCH_ISSUED_ALREADY,
                Entity::BATCH_ID,
                [
                    Entity::BATCH_ID => $batchId,
                ]);
        }

        $job = new InvoiceBatchIssueJob($this->mode, $batch->getId(), $input);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);

        return ['success' => true];
    }

    /**
     * Calculates and sets derived amounts of invoice.
     *
     * @param Entity $invoice
     */
    public function calculateAndSetAmountsOfInvoice(Entity $invoice)
    {
        // Other types won't have taxation, their tax amount will be 0
        // and net amount will be equal to amount.

        if (($invoice->isTypeInvoice() === false) and ($invoice->getAmount() !== null))
        {
            $invoice->setTaxAmount(0);
            $invoice->setGrossAmount($invoice->getAmount());

            return;
        }

        $lineItems = $invoice->lineItems()->get();

        // If there are no line items associated with invoice, make all amounts
        // field 'null' (i.e. unset).

        if ($lineItems->count() === 0)
        {
            $invoice->setAmountsToNull();

            return;
        }

        // Invoice's:
        // Gross amount = ∑(line_items.gross_amount)
        // Tax amount = ∑(line_items.tax_amount)
        // Amount = ∑(line_items.net_amount)

        $grossAmount = $taxAmount = $amount = 0;

        foreach ($lineItems as $lineItem)
        {
            $grossAmount += $lineItem->getGrossAmount();
            $taxAmount   += $lineItem->getTaxAmount();
            $amount      += $lineItem->getNetAmount();
        }

        $invoice->setGrossAmount($grossAmount);
        $invoice->setTaxAmount($taxAmount);
        $invoice->setAmount($amount);

        $invoice->getValidator()->validateMaxAllowedAmount($grossAmount);
    }

    // -------------------- Protected methods --------------------

    protected function updateDraftInvoice(
        Merchant\Entity $merchant,
        Entity $invoice,
        array $input)
    {
        $this->repo->transaction(
            function() use ($merchant, $invoice, $input)
            {
                $this->generateAttributesOnUpdate($invoice, $input);

                (new Generator($merchant, $invoice))->updateDraftInvoice($input);

                $this->repo->saveOrFail($invoice);
            });
    }

    protected function updateIssuedInvoice(
        Merchant\Entity $merchant,
        Entity $invoice,
        array $input)
    {
        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->updateOrderOfIssuedInvoice($invoice);

                $this->repo->saveOrFail($invoice);
            });
    }

    /**
     * Issue invoice has an order created. There are few attributes
     * which gets copied to order when issuing an invoice. Eg. invoice
     * has partial_payment attribute.
     *
     * In most of the cases we don't allow edits on issued invoice attributes
     * but when we do and it affects orders (highly unlikely case) we need
     * to update corresponding order details as well.
     *
     * @param Entity $invoice
     */
    protected function updateOrderOfIssuedInvoice(Entity $invoice)
    {
        if ($invoice->isDirty(Entity::PARTIAL_PAYMENT) === true)
        {
            $order = $invoice->order;

            $order->togglePartialPayment();

            $this->repo->saveOrFail($order);
        }
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

        if (isset($input[Entity::DRAFT]))
        {
            $invoice->generateStatus($input);
        }
    }

    protected function validateIfInvoiceCanBeCancelled(Entity $invoice)
    {
        $count = $this->repo->invoice->getSucceedingPaymentsCount($invoice);

        if ($count !== 0)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getTypeLabel() . ' cannot be cancelled as payment for it has happened');
        }
    }

    protected function validateIfInvoiceCanBeExpired(Entity $invoice)
    {
        $count = $this->repo->invoice->getSucceedingPaymentsCount($invoice);

        if ($count !== 0)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getTypeLabel() . ' cannot be expired as payment for it has happened');
        }
    }

    protected function generatePdfWithRetry(string $id, int $attempt = 0)
    {
        $attempt++;

        if ($attempt > self::MAX_ALLOWED_PDF_GEN_ATTEMPTS)
        {
            return null;
        }

        try
        {
            return $this->pdfGenerator->generate();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_PDF_GEN_FAILED,
                [
                    'id'       => $id,
                    'attempts' => $attempt,
                ]);

            // Don't attempt regenerating file if there was some 4XX error

            if ($e instanceof BadRequestValidationFailureException)
            {
                return null;
            }

            $this->generatePdfWithRetry($id, $attempt);
        }
    }

    /**
     * Modifies input param to handle renamed attributes in response.
     *
     * @param array $input
     */
    protected function modifyInputToHandleRenamedAttributes(array & $input)
    {
        if (array_key_exists(Entity::INVOICE_NUMBER, $input) === true)
        {
            $input[Entity::RECEIPT] = $input[Entity::INVOICE_NUMBER];

            unset($input[Entity::INVOICE_NUMBER]);
        }
    }
}
