<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use App;
use RZP\Base\JitValidator;
use RZP\Constants\Environment;
use RZP\Mail\System\Trace;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Models\Batch;
use RZP\Models\Options;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Models\Settings;
use RZP\Models\Customer;
use RZP\Models\FileStore;
use RZP\Services\Reminders;
use RZP\Base\RuntimeManager;
use RZP\Mail\Invoice\Issued;
use RZP\Constants\Entity as E;
use RZP\Models\Invoice\Reminder;
use RZP\Models\Plan\Subscription;
use RZP\Models\EntityOrigin\Constants;
use RZP\Exception\BadRequestException;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use Illuminate\Support\Facades\Storage;
use RZP\Models\Item\Type as LineItemType;
use RZP\Models\Feature\Constants as Features;
use RZP\Jobs\Invoice\BatchJob as InvoiceBatchJob;
use RZP\Models\SubscriptionRegistration\Metric as M;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Jobs\Invoice\BatchIssue as InvoiceBatchIssueJob;
use RZP\Jobs\Invoice\BatchNotify as InvoiceBatchNotifyJob;
use RZP\Jobs\Invoice\BatchCancel as InvoiceBatchCancelJob;

class Core extends Base\Core
{
    const QUEUE_JOB_DELAY              = 5; // In seconds
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;
    const CANCEL_JOB_DELAY_WITH_STOP   = 300;
    //
    // When someone requests pdf version of invoice we use this factor
    // to determine if we should create new latest pdf in sync or use already
    // created one. Whenever invoice gets updated we update pdf version over queue.
    //
    const MAX_EXPECTED_QUEUE_DELAY     = 60; // In seconds (= 1 min)

    const BATCH_BULK_QUEUE_OFFSET_LIMIT = 2500;

    const INVOICE_IDEMPOTENCY_REDIS_KEY = 'invoice_idempotency_key_';

    protected $lineItemCore;
    protected $pdfGenerator;
    protected $eventService;
    protected $options;
    /**
     * @var Reminders
     */
    protected $reminders;

    const RECEIPT_MUTEX_TIMEOUT  = 5; // 5 seconds timeout

    const SAMPLE_PDF_LINK = 'http://www.africau.edu/images/default/sample.pdf';

    public function __construct()
    {
        parent::__construct();

        $this->lineItemCore         = new LineItem\Core;
        $this->pdfGenerator         = null;
        $this->eventService         = $this->app['events'];
        $this->options              = new Options\Core();
        $this->reminders            = $this->app['reminders'];
    }

    public function setPdfGenerator(Entity $invoice)
    {
        $this->pdfGenerator = new PdfGenerator($invoice);
    }

    /**
     * Creates invoice
     *
     * @param array                    $input
     * @param Merchant\Entity          $merchant
     * @param Subscription\Entity|null $subscription   - If created via subscription,
     *                                                 this is passed for associations
     * @param Batch\Entity|null        $batch
     * @param Base\Entity|null         $externalEntity
     * @param string|null              $batchId        - This is passed by batchService
     *                                                 and kept for backward compatible
     *                                                 and also by batch upload in API
     *                                                 till we migrate completely
     *
     * @return Entity
     */
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Subscription\Entity $subscription = null,
        Batch\Entity $batch = null,
        Base\Entity $externalEntity = null,
        string $batchId = null,
        Order\Entity $order = null): Entity
    {
        $inputTrace = $input;

        $this->unsetPIIData($inputTrace);

        $this->trace->info(TraceCode::INVOICE_CREATE_REQUEST, $inputTrace);

        //
        // check if idempotent Id exists in the payload entity
        // if yes then fetch the record from the Db.
        // if record exist, then return. If not then continue with the flow.
        //
        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->invoice->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY]);

            if ($result === null)
            {
                $merchantId  = $this->merchant->getId();

                $key = self::INVOICE_IDEMPOTENCY_REDIS_KEY . $merchantId . '_' . $input[Entity::IDEMPOTENCY_KEY];

                $invoiceId = $this->app['cache']->get($key);

                if ($invoiceId !== null)
                {
                    $result = $this->repo->invoice->findByIdAndMerchant($invoiceId, $merchantId);
                }
            }

            if ($result !== null)
            {
                return $result;
            }
        }

        $this->modifyInputToHandleRenamedAttributes($input);

        $shouldFailOnDuplicateInternalRef = boolval($input['fail_existing'] ?? true);
        unset($input['fail_existing']);

        //
        // This happens when the invoice is being created for a subscription,
        // but not internally via API. Instead, the request has come to
        // API from SubServ. In this case, we don't have a subscription
        // object, but do need to set the id in invoice entity.
        //
        if (($subscription === null) and
            (empty($input[Entity::SUBSCRIPTION_ID]) === false))
        {
            $subscription = $input[Entity::SUBSCRIPTION_ID];

            unset($input[Entity::SUBSCRIPTION_ID]);
        }

        $batchIdOrBatch = $batchId === null ? $batch : $batchId;

        $batchOffset = 0;

        if (isset($input[Entity::BATCH_OFFSET]) === true)
        {
            $batchOffset = $input[Entity::BATCH_OFFSET];

            unset($input[Entity::BATCH_OFFSET]);
        }

        $this->takeMutexOnReceiptOrFail($merchant, $input, $batchIdOrBatch);

        $invoice = (new Generator($merchant))
                        ->setSubscription($subscription)
                        ->setExternalEntity($externalEntity)
                        ->setOrder($order)
                        ->setBatch($batchIdOrBatch)
                        ->setShouldFailOnDuplicateInternalRef($shouldFailOnDuplicateInternalRef)
                        ->generate($input);

        $invoiceCreatedTrace = $invoice->toArrayPublic();

        $this->unsetPIIData($invoiceCreatedTrace);

        $this->trace->info(TraceCode::INVOICE_CREATED, $invoiceCreatedTrace);
        $this->trace->count(Metric::INVOICE_CREATED_TOTAL, $invoice->getMetricDimensions(['merchant_country_code' => (string) $merchant->getCountry()]));

        $this->repo->loadRelations($invoice);

        if ($invoice->isIssued())
        {
            if ((empty($batchIdOrBatch) === false) and ($batchOffset > self::BATCH_BULK_QUEUE_OFFSET_LIMIT))
            {
                $pendingDispatch = InvoiceBatchJob::dispatch($this->mode, InvoiceBatchJob::ISSUED, $invoice->getId());
            }
            else
            {
                $pendingDispatch = InvoiceJob::dispatch($this->mode, InvoiceJob::ISSUED, $invoice->getId());
            }

            // Internal flow (e.g. via subscription) requires delay to accommodate for time in wrapping txn commit
            if ($invoice->hasSubscription())
            {
                $pendingDispatch->delay(self::QUEUE_JOB_DELAY);
            }
        }

        if (isset($input[Options\Entity::OPTIONS]) === true)
        {
            $this->options->createOptionForPaymentLink($input, $merchant, $invoice);
        }

        (new \RZP\Models\EntityOrigin\Core())->createEntityOrigin($invoice);

        return $invoice;
    }

    public function unsetPIIData(array &$input)
    {
        unset($input[Entity::CUSTOMER]);
        unset($input[Entity::CUSTOMER_DETAILS]);
        unset($input['token']['bank_account']);
    }

    public function update(Entity $invoice, array $input, Merchant\Entity $merchant): Entity
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
        // Once basic fill by edit call on entity is done, based on invoice status,
        // it calls either updateDraftInvoice|updateIssuedInvoice.
        //
        // This was done to maintain flow clean. Because if not now, there are chances
        // we want to handle different things in different case.
        //

        $operation = 'edit' . studly_case($status);

        $invoice->edit($input, $operation);

        $updateFunction = 'update' . studly_case($status) . 'Invoice';

        // If a custom function exists to handle update for a status, call it. Else, handle save here and proceed
        if (method_exists($this, $updateFunction) === true)
        {
            $this->$updateFunction($merchant, $invoice, $input);
        }
        else
        {
            $this->repo->saveOrFail($invoice);
        }

        $this->repo->loadRelations($invoice);

        $this->handleReminderForInvoice($invoice, $input);

        if ($invoice->isIssued() === true or $invoice->isPartiallyPaid() === true)
        {
            InvoiceJob::dispatch($this->mode, InvoiceJob::UPDATED, $invoice->getId(), $input);
        }

        return $invoice;
    }

    protected function changeReminderStatus(array $input, $reminderEntity): bool
    {
        if ((empty($input[Entity::REMINDER_ENABLE]) === false) and
            (boolval($input[Entity::REMINDER_ENABLE]) === true))
        {
            return true;
        }

        if(empty($reminderEntity) === true)
        {
            return false;
        }

        $reminderStatus = $reminderEntity->getReminderStatus();

        if((array_key_exists('expire_by', $input) === true) and
            ((empty($reminderStatus) === false) and
             ($reminderStatus === Reminder\Status::IN_PROGRESS or
              $reminderStatus === Reminder\Status::FAILED)))
        {
            return true;
        }

        return false;
    }

    protected function deleteReminder(Entity $invoice, $reminderEntity, Reminder\Core $reminderCore): bool
    {
        if ((empty($reminderEntity) === true) or
            ($reminderEntity->getReminderId() === null))
        {
            return false;
        }

        if (empty($reminderEntity->getReminderId()) === false)
        {
            try {
                $response = $this->reminders->deleteReminder($reminderEntity->getReminderId());

                $reminderInput[Reminder\Entity::REMINDER_STATUS] = Reminder\Status::DISABLED;

                $reminderCore->createOrUpdate($reminderInput, $invoice, $reminderEntity);

            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    null);

                return false;
            }
        }

        if(((isset($response['status_code']) === true) and ($response['status_code'] === 200)) or
            (empty($reminderEntity->getReminderId()) === true))
        {
            $reminderEntity->setReminderStatus(Reminder\Status::DISABLED);

            $reminderEntity->setReminderId(null);

            $this->repo->saveOrFail($reminderEntity);

            return true;
        }

        return false;
    }

    private function handleReminderForInvoice(Entity $invoice, array $input)
    {
        $reminderCore = new Reminder\Core();

        $reminderEntity = $this->repo->invoice_reminder->getByInvoiceId($invoice->getId());

        if($this->changeReminderStatus($input, $reminderEntity) === true)
        {
            $reminderInput[Reminder\Entity::REMINDER_STATUS] = Reminder\Status::PENDING;

            $reminderCore->createOrUpdate($reminderInput, $invoice, $reminderEntity);
        }

        if((isset($input['reminder_enable']) === true) and
            (boolval($input['reminder_enable']) === false))
        {
            $this->deleteReminder($invoice, $reminderEntity, $reminderCore);
        }

    }

    public function updateBillingPeriod(Entity $invoice, array $input): Entity
    {
        $operation = 'editBillingPeriod';

        $invoice->edit($input, $operation);

        $this->repo->saveOrFail($invoice);

        return $invoice;
    }

    public function issue(Entity $invoice, Merchant\Entity $merchant, $batchId = null): Entity
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

        $response = 'off';

        if (empty($batchId) === false)
        {
            $response = $this->app->razorx->getTreatment(
                $merchant->getId(),
                Merchant\RazorxTreatment::CHANGE_QUEUE_BATCH_INVOICE,
                $this->mode);
        }

        // route batch jobs to batch invoice queue
        if ((empty($batchId) === false) and ($response === 'on'))
        {
            InvoiceBatchJob::dispatch($this->mode, InvoiceBatchJob::ISSUED, $invoice->getId());
        }
        else
        {
            InvoiceJob::dispatch($this->mode, InvoiceJob::ISSUED, $invoice->getId());
        }

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

    public function addOfferDetails(
        $invoiceId,
        Payment\Entity $payment)
    {
        if ($invoiceId === null)
        {
            // Case: Subscription Payment for when No Invoice is created: eg Card Change
            return;
        }

        $offer = $payment->getOffer();

        $this->trace->info(
            TraceCode::INVOICE_ADD_OFFER_DETAILS,
            [
                'invoice_id'     => $invoiceId,
                'offer'          => $offer,
            ]);

        $invoiceEntity = $this->repo->invoice->findByIdAndMerchantId($invoiceId, $payment->getMerchantId());

        if ($invoiceEntity === null)
        {
            return;
        }

        if ($offer !== null)
        {
            $this->repo->transaction(
                function () use ($invoiceEntity, $offer, $payment) {

                        $discountAmount = $offer->getDiscountAmountForPayment($payment->order->getAmount(), $payment);

                        $invoiceEntity->setOfferAmount($discountAmount);

                        $this->calculateAndSetAmountsOfInvoice($invoiceEntity);

                        $template = '$offerName ;#$ $offerDesc';

                        $vars = array(
                            '$offerName' => $offer->getName(),
                            '$offerDesc' => $offer->getDisplayText(),
                        );

                        // By this time, amount due attribute should be the offer amount
                        if ($invoiceEntity->getAmountDueAttribute() === $discountAmount)
                        {
                            $invoiceEntity->setStatus(Status::PAID);
                        }

                        $invoiceEntity->setComment(strtr($template, $vars));

                        $this->repo->saveOrFail($invoiceEntity);
                });
        }
        else if($invoiceEntity->getComment() !== null and
                $invoiceEntity->getOfferAmount() === null)
        {
            $invoiceEntity->setNotes(
                [
                    "offer_note"       => 'offer could not be applied as per offer conditions',
                    "original_comment" => $invoiceEntity->getComment()
                ]
            );

            $invoiceEntity->setComment(null);

            $this->repo->saveOrFail($invoiceEntity);
        }

        return $this->repo->loadRelations($invoiceEntity);
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

    public function sendNotification(Entity $invoice, string $medium, bool $merchantEmail = false): array
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
            $pdfPath = $this->getFreshInvoicePdfFilePath($invoice);
        }

        $response = (new Notifier($invoice, $pdfPath))->$func();

        $this->repo->saveOrFail($invoice);

        if ($merchantEmail === true)
        {
            $func = studly_case($medium) . 'InvoiceIssuedToMerchant';

            (new Notifier($invoice, $pdfPath))->$func();
        }

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
        RuntimeManager::setMaxExecTime(720);

        RuntimeManager::setMemoryLimit('1024M');

        $time = time();

        $invoiceIds = $this->repo->invoice->getIssuedAndPastExpiredByInvoices();

        $summary = [
            'total_invoices_count' => $invoiceIds->count(),
            'failed_invoice_ids'   => [],
        ];

        foreach ($invoiceIds as $invoiceId)
        {
            try
            {
                $invoice = $this->repo->invoice->findOrFail($invoiceId);

                $this->expireInvoice($invoice);
            }
            catch (\Exception $e)
            {
                $summary['failed_invoice_ids'][] = $invoice->getId();

                $this->trace->traceException($e, null, null, ['id' => $invoice->getId()]);
            }
        }

        $time = time() - $time;

        $summary['time_taken'] = $time . ' secs';

        $this->trace->debug(TraceCode::INVOICES_EXPIRE_CRON_SUMMARY, $summary);

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
        /** @var Validator $validator */
        $validator = $invoice->getValidator();

        $validator->validateOperation(__FUNCTION__);

        // retries the database transaction for 1 time when there is a deadlock error.
        $maxAttempts = 2;

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                $this->validateIfInvoiceCanBeExpired($invoice);

                $invoice->setStatus(Status::EXPIRED);

                $this->repo->saveOrFail($invoice);
            }, $maxAttempts);

        $this->trace->count(Metric::INVOICE_EXPIRED_TOTAL, $invoice->getMetricDimensions(['merchant_country_code' => (string) $invoice->merchant->getCountry()]));

        // Sends expiration mails to customer asynchronously
        $this->eventService->dispatch('api.invoice.expired', [$invoice]);
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

    public function deleteInvoices(int $pastTime, array $merchantIds = [], int $limit = 500): array
    {
        RuntimeManager::setMaxExecTime(720);

        RuntimeManager::setMemoryLimit('1024M');

        $time = time();

        $invoiceIds = $this->repo->invoice->getPastInvoicesByStatusAndMerchatId(
            $pastTime,
            Entity::DELETE_ALLOWED_STATUSES,
            $merchantIds,
            $limit);

        $summary = [
            'total_invoices_count' => $invoiceIds->count(),
            'failed_invoice_ids'   => [],
        ];

        foreach ($invoiceIds as $invoiceId)
        {
            try
            {
                $invoice = $this->repo->invoice->findOrFail($invoiceId);

                $this->deleteInvoice($invoice);
            }
            catch (\Exception $e)
            {
                $summary['failed_invoice_ids'][] = $invoice->getId();

                $this->trace->traceException($e, null, null, ['id' => $invoice->getId()]);
            }
        }

        $time = time() - $time;

        $summary['time_taken'] = $time . ' secs';

        $this->trace->debug(TraceCode::INVOICES_DELETE_CRON_SUMMARY, $summary);

        return $summary;
    }

    protected function deleteInvoice(Entity $invoice)
    {
        /** @var Validator $validator */
        $validator = $invoice->getValidator();

        $validator->validateOperation(__FUNCTION__);

        // retries the database transaction for 1 time when there is a deadlock error.
        $maxAttempts = 2;

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                $this->repo->invoice->deleteOrFail($invoice);

            }, $maxAttempts);

        $this->trace->count(Metric::INVOICE_DELETED_TOTAL, $invoice->getMetricDimensions(['merchant_country_code' => (string) $invoice->merchant->getCountry()]));
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
        /** @var Entity $invoice */
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $merchant);

        /** @var Validator $validator */
        $validator = $invoice->getValidator();
        $validator->validateInvoicePayable();

        $orderId       = $invoice->getOrderId();
        $publicOrderId = Order\Entity::getSignedId($orderId);
        $customer      = $invoice->customer;

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
        $order = $this->repo->order->findByPublicIdAndMerchant($publicOrderId, $merchant);

        $data['order'] = (new Order\Core)->getFormattedDataForCheckout(
                                                $order,
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
     * @param Entity $invoice
     *
     * @return FileStore\Entity|null
     */
    public function getFreshInvoicePdf(Entity $invoice)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        // If requested withing expected queue delay, create fresh pdf and return
        $now = Carbon::now()->getTimestamp();

        if ($now - $invoice->getUpdatedAt() <= self::MAX_EXPECTED_QUEUE_DELAY)
        {
            $this->trace->debug(TraceCode::INVOICE_PDF_GEN_SYNC, [Entity::ID => $invoice->getId()]);

            return $this->createInvoicePdf($invoice);
        }

        // Else return existing pdf. Now in case it doesn't exist still, create
        $pdf = $invoice->pdf();

        if ($pdf === null)
        {
            $pdf = $this->createInvoicePdf($invoice);
        }

        return $pdf;
    }

    /**
     * @param Entity $invoice
     *
     * @return string|null
     */
    public function getFreshInvoicePdfFilePath(Entity $invoice)
    {
        $pdf = $this->getFreshInvoicePdf($invoice);

        if ($pdf === null)
        {
            return null;
        }

        $signedUrl = (new FileUploadUfh())->getSignedUrl($invoice);

        if($this->app->environment() == Environment::TESTING)
        {
            $signedUrl = self::SAMPLE_PDF_LINK;
        }

        return $signedUrl;
    }

    /**
     * @param Entity $invoice
     *
     * @return FileStore\Entity|null
     */
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
     * @param Entity $invoice
     *
     * @return string|null
     */
    public function createInvoicePdfAndGetFilePath(Entity $invoice)
    {
        $pdf = $this->createInvoicePdf($invoice);

        return ($pdf !== null) ? $pdf->getFullFilePath() : null;
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

        InvoiceBatchIssueJob::dispatch($this->mode, $batch->getId(), $input);

        return ['success' => true];
    }

    /**
     * Sends notifications for all the invoices of a given batch, if not
     * already sent.
     *
     * @param  Batch\Entity $batch
     * @param  array        $input
     */
    public function notifyInvoicesOfBatch(Batch\Entity $batch, array $input)
    {
        //
        // Settings module captures whether notification for this batch has
        // been already sent or not.
        //
        $settingsAccessor = Settings\Accessor::for($batch, Settings\Module::BATCH);

        (new Validator)->validateNotifyInvoicesOfBatch($settingsAccessor, $batch, $input);

        $settingsAccessor->upsert($input)->save();

        InvoiceBatchNotifyJob::dispatch($this->mode, $batch->getId(), $input);
    }

    /**
     * Cancels all the invoices associated to batch if batch is
     * proccessed.
     *
     * @param  Batch\Entity $batch
     */
    public function cancelInvoicesOfBatch(array $batch)
    {
        (new Validator())->validateCancelInvoicesOfBatch($batch);

        $batchService = new Batch\Service();

        $needToStopBatch = $batchService->isStoppingRequired($batch[Batch\Entity::STATUS]);

        $delay = self::QUEUE_JOB_DELAY;

        if ($needToStopBatch === true)
        {
            $delay = self::CANCEL_JOB_DELAY_WITH_STOP;

            $batchService->stopBatchProcess($batch);
        }

        $batchId = $batch[Batch\Entity::ID];

        Batch\Entity::verifyIdAndStripSign($batchId);

        $this->trace->info(
            TraceCode::INVOICE_BATCH_CANCEL_DISPATCH_REQUEST,
            [
                'batch'        => $batch,
                'delay'        => $delay,
            ]);

        //
        // This function is also used for cancel auth links via batch on
        // admin auth. Since merchant is not available on admin auth,
        // keeping $this->merchant ?? null explicitly.
        //
        InvoiceBatchCancelJob::dispatch($this->mode,
                                        $batchId,
                                        $batch[Batch\Entity::SUCCESS_COUNT])
                             ->delay($delay);
    }

    /**
     * Calculates and sets derived amounts of invoice.
     *
     * @param Entity $invoice
     */
    public function calculateAndSetAmountsOfInvoice(Entity $invoice)
    {
        // Other types won't have taxation, their tax amount will be 0 and net amount will be equal to amount.
        if (($invoice->isTypeInvoice() === false) and ($invoice->getAmount() !== null))
        {
            $invoice->setTaxAmount(0);
            $invoice->setGrossAmount($invoice->getAmount());

            return;
        }

        $lineItems = $invoice->lineItems()->get();

        // If there are no line items associated with invoice, make all amounts field 'null' (i.e. unset).
        if ($lineItems->count() === 0)
        {
            $invoice->setAmountsToNull();

            return;
        }

        //
        // Invoice's:
        // Gross amount = ∑(line_items.gross_amount)
        // Tax amount   = ∑(line_items.tax_amount)
        // Amount       = ∑(line_items.net_amount)
        //
        // Note: We do re calculation of all taxes of line items here to get the precise(float) value and then ∑
        // followed by rounding here again to set in invoice's entity. This is because line item's taxes entities are
        // already built earlier in the flow and they have in their columns values rounded(so precision lost). This is
        // and quick workaround and we'll revisit to have the flow here fixed besides thinking long term of keeping
        // precise amount throughout (i.e. PAISE multiplied by 100 or something :) ).
        //

        $grossAmount = $taxAmount = $amount = 0;

        foreach ($lineItems as $lineItem)
        {
            // Gets line item's gross, tax and net amount in order
            $amounts = $this->lineItemCore->calculateAmountsOfLineItem($lineItem);

            $grossAmount += $amounts[0];
            $taxAmount   += $amounts[1];
            $amount      += $amounts[2];
        }

        if ($invoice->getOfferAmount() !== null and $invoice->getOfferAmount() > 0)
        {
            $amount -= $invoice->getOfferAmount();
        }

        $invoice->setGrossAmount($grossAmount);
        $invoice->setTaxAmount((int) round($taxAmount));
        $invoice->setAmount((int) round($amount));

        /**
         * Removed max amount check from here since order already does the validations properly
         */
    }

    public function fetchStatsOfBatch(Batch\Entity $batch): array
    {
        $stats = null;

        if ($this->shouldForwardToPaymentLinkService() === true)
        {
            try
            {
                $paymentLinkService = $this->app['paymentlinkservice'];

                $response = $paymentLinkService->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    $stats =  $response['response']['stats'];
                }

                $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_RESPONSE,
                    [
                        'batch'    => $batch->getId(),
                        'stats'    => $stats ,
                        'response' => $response['response']
                    ]);
                // all other cases , do nothing. will try fetching from invoice repo
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['batch' => $batch->getId()]);
                // do nothing. will try fetching from invoice repo
            }
        }

        if ($stats === null)
        {
            $stats = $this->repo->invoice->getInvoiceStatsForBatch($batch);
        }

        if (isset($stats[Entity::TOTAL_COUNT]) === true)
        {
            return $stats;
        }

        //
        // created_count is the number of links that were in `issued` state
        // at some point of their lifetime.
        // Invoice can be cancelled from both `draft` and `issued` state.
        // However, for batch payment links, all links are created in `issued` state only.
        // So any link in `cancelled` state can be safely assumed to have
        // reached from `issued` state only.
        //
        $createdCount = array_sum(array_except($stats, [Status::DRAFT]));

        return [
            Entity::TOTAL_COUNT   => $batch->getTotalCount(),
            Entity::ISSUED_COUNT  => $createdCount,
            Entity::CREATED_COUNT => $createdCount,
            Entity::PAID_COUNT    => $stats[Status::PAID] ?? 0,
            Entity::EXPIRED_COUNT => $stats[Status::EXPIRED] ?? 0,
        ];
    }

    public function switchPlVersions(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_V2_SWITCH_REQUEST, $input);

        $switchTo = $input[Entity::SWITCH_TO];

        switch ($switchTo)
        {
            case 'v2':
                $this->repo->transaction(
                    function() use ($input, $merchant)
                    {
                        $isV2Enabled = $merchant->isFeatureEnabled(Features::PAYMENTLINKS_V2);

                        if ($isV2Enabled === false)
                        {
                            (new Feature\Core)->create([
                                Feature\Entity::ENTITY_TYPE => E::MERCHANT,
                                Feature\Entity::ENTITY_ID => $merchant->getId(),
                                Feature\Entity::NAME => Feature\Constants::PAYMENTLINKS_V2,
                            ], $shouldSync = true);
                        }

                        $isCompatEnabled = $merchant->isFeatureEnabled(Features::PAYMENTLINKS_COMPATIBILITY_V2);

                        if ($isCompatEnabled === true)
                        {
                            $compatFeature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                                E::MERCHANT,
                                $merchant->getId(),
                                Feature\Constants::PAYMENTLINKS_COMPATIBILITY_V2);

                            (new Feature\Core)->delete($compatFeature, true);
                        }

                        // This tag is added to know whether merchant switched themselves.
                        // If they did the action on their own, we are adding a form fill up on their dashboard
                        // if they want to revert. Revert is still a manual process via ops. This tag is just to show the form.
                        if ($merchant->isTagAdded('self_switched_to_v2') === false)
                        {
                            $merchant->tag('self_switched_to_v2');
                        }
                    });

                break;
        }

        return [Entity::SWITCH_TO => true];
    }

    // -------------------- Protected methods --------------------

    protected function updateDraftInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->transaction(
            function() use ($merchant, $invoice, $input)
            {
                $this->generateAttributesOnUpdate($invoice, $input);

                $this->unsetFirstPaymentMinAmountFieldIfApplicable($invoice);

                (new Generator($merchant, $invoice))->updateDraftInvoice($input);

                $this->repo->saveOrFail($invoice);
            });
    }

    protected function updateIssuedInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->unsetFirstPaymentMinAmountFieldIfApplicable($invoice);

                $this->repo->saveOrFail($invoice);

                // Please keep this function at the end of transaction block, as
                // we are updating orders which lies in PG Router service now.
                //This has been done to temporarily handle the distributed transaction failures.
                $this->updateOrderOfIssuedInvoice($invoice);
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
        $order = $invoice->order;

        if ($invoice->isDirty(Entity::PARTIAL_PAYMENT) === true)
        {
            $order->togglePartialPayment();
        }

        $order->setFirstPaymentMinAmount($invoice->getFirstPaymentMinAmount());

        if ($order->isExternal() === true)
        {
            $input = [
                Order\Entity::FIRST_PAYMENT_MIN_AMOUNT => $order->getFirstPaymentMinAmount(),
                Order\Entity::PARTIAL_PAYMENT => $order->isPartialPaymentAllowed()
            ];

            $this->app['pg_router']->updateInternalOrder($input,$order->getId(),$order->getMerchantId(), true);
        }
        else
        {
            $this->repo->saveOrFail($order);
        }

       // $this->repo->saveOrFail($order);
    }

    protected function unsetFirstPaymentMinAmountFieldIfApplicable(Entity $invoice)
    {
        if (($invoice->isDirty(Entity::PARTIAL_PAYMENT) === true) and
            ($invoice->isPartialPaymentAllowed() === false))
        {
            $invoice->setFirstPaymentMinAmount(null);
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

        // Sanitize Customer data before sending it to customer create module.
        if ((empty($input[Entity::CUSTOMER]) === false) and
            (array_key_exists(Customer\Entity::EMAIL, $input[Entity::CUSTOMER]) === true) and
            (array_key_exists(Customer\Entity::CONTACT, $input[Entity::CUSTOMER]) === true) and
            ($input[Entity::CUSTOMER][Customer\Entity::EMAIL] === '') and
            ($input[Entity::CUSTOMER][Customer\Entity::CONTACT] === ''))
        {
            $input[Entity::CUSTOMER][Customer\Entity::EMAIL] = null;
            $input[Entity::CUSTOMER][Customer\Entity::CONTACT] = null;
        }
    }

    protected function takeMutexOnReceiptOrFail(Merchant\Entity $merchant, array & $input, $batch)
    {
        if ($batch !== null)
        {
            return;
        }

        if (isset($input[Entity::RECEIPT]) === false)
        {
            return;
        }

        $receipt = $input[Entity::RECEIPT];

        if (empty($receipt) === true)
        {
            return;
        }

        $skipUniquenessCheck = $merchant->isFeatureEnabled(Features::INVOICE_NO_RECEIPT_UNIQUE);

        if ($skipUniquenessCheck === true)
        {
            return;
        }

        $mutex =  App::getFacadeRoot()['api.mutex'];

        $mutexAcquired = $mutex->acquire($merchant->getId()."-".$receipt, self::RECEIPT_MUTEX_TIMEOUT);

        if ($mutexAcquired === false)
        {
            $this->trace->count(
                M::INVOICE_MUTEX_ACQUIRE_FAILED,
                [
                    'message' => ErrorCode::BAD_REQUEST_INVOICE_RECEIPT_ANOTHER_OPERATION_IN_PROGRESS,
                    'route' => App::getFacadeRoot()['api.route']->getCurrentRouteName(),
                ]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_RECEIPT_ANOTHER_OPERATION_IN_PROGRESS,
                null,
                ['resource' => $merchant->getId()."-".$receipt]
            );
        }
    }

    public function shouldForwardToPaymentLinkService(): bool
    {
        if ($this->app['basicauth']->isPaymentLinkServiceApp() === true)
        {
            return false;
        }

        $merchant = $this->merchant;

        if ($merchant !== null)
        {
            if ($merchant->isAtLeastOneFeatureEnabled(
                [Features::PAYMENTLINKS_COMPATIBILITY_V2,
                    Features::PAYMENTLINKS_V2
                ]) === true)
            {
                return true;
            }
        }

        return false;
    }

    public function getGrievanceEntityDetails(string $id)
    {
        $merchant = null;

        $response = $this->app['paymentlinkservice']->getById($id);

        return $this->getFromattedDataForGrievance($id, $response);
    }

    protected function getFromattedDataForGrievance(string $id, $response)
    {
        if ($response !== null)
        {
           return $this->getFormattedDataForPlServiceGrievance($response);
        }

        $id = Entity::stripDefaultSign($id);

        $invoice = $this->repo->invoice->findOrFailPublic($id);

        $merchant = $invoice->merchant;

        $currency = $invoice->getCurrency();

        $formattedAmount = $invoice->getFormattedAmount();

        $type = $invoice->getType();

        return [
            'entity'         => $invoice->isTypeLink() ? 'payment_link' : 'invoice',
            'entity_id'      => $invoice->getPublicId(),
            'merchant_id'    => $merchant->getId(),
            'merchant_label' => $merchant->getBillingLabel(),
            'merchant_logo'  => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'subject'        => $this->getMailSubjectForGrievance($type, $merchant, $currency, $formattedAmount),
        ];
    }

    protected function getFormattedDataForPlServiceGrievance(array $response)
    {
        $merchantId = $response['merchant_id'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $currency = $response['currency'];

        $amount = $response['amount'];

        $formattedAmount = number_format($amount / 100, 2);

        $type = Type::LINK;

        return [
            'entity'         => 'payment_link',
            'entity_id'      => $response['id'],
            'merchant_id'    => $merchant->getId(),
            'merchant_label' => strip_tags($merchant->getBillingLabel()),
            'merchant_logo'  => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'subject'        => $this->getMailSubjectForGrievance($type, $merchant, $currency, $formattedAmount),
        ];
    }

    protected function getMailSubjectForGrievance(string $type, Merchant\Entity $merchant, string $currency, string $formattedAmount)
    {
        $subjectTemplate  = Issued::SUBJECT_TEMPLATES[$type];

        if ($type === Type::INVOICE)
        {
            $args = [
                $merchant->getBillingLabel(),
            ];
        }
        else
        {
            $args = [
                $currency,
                $formattedAmount,
            ];
        }

        return sprintf($subjectTemplate, ...$args);
    }

    public function updateInvoiceAfterCapture(
        Entity $invoice,
        Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE,
            [
                'payment_id'                    => $payment->getId(),
                'invoice_id'                    => $invoice->getId(),
                'order_id'                      => $invoice->getOrderId(),
                'payment_amount'                => $payment->getAmount(),
                'invoice_amount'                => $invoice->getAmount(),
                'invoice_amount_paid_attribute' => $invoice->getAmountPaidAttribute(),
                'invoice_amount_paid'           => $invoice->getAmountPaid(),
                'invoice_status'                => $invoice->getStatus(),
            ]);

        if ($invoice->hasBeenPaid() === true)
        {
            $this->trace->info(TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE_IS_FULLY_PAID,
                               [
                                   'invoice_status' => $invoice->getStatus()
                               ]);
            return;
        }

        $invoice->updateStatusPostCapture($payment);

        $isPartialPayment = ($invoice->getAmount() !== $payment->getAmount());
        $dimensions = $invoice->getMetricDimensions(['is_partial_payment' => (int) $isPartialPayment, 'merchant_country_code' => (string) $invoice->merchant->getCountry()]);
        $this->trace->count(Metric::INVOICE_PAID_TOTAL, $dimensions);

        $this->repo->saveOrFail($invoice);

        $this->trace->info(TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE_SAVED,
                           [
                               'payment_amount'                => $payment->getAmount(),
                               'invoice_amount'                => $invoice->getAmount(),
                               'invoice_amount_paid_attribute' => $invoice->getAmountPaidAttribute(),
                               'invoice_amount_paid'           => $invoice->getAmountPaid(),
                               'invoice_status'                => $invoice->getStatus(),
                           ]
        );
    }

    public  function  getCurlecBrandingConfig(){
        $branding = [];
        $branding['show_rzp_logo'] = true;
        $branding['security_branding_logo'] = "https://cdn.razorpay.com/static/assets/i18n/malaysia/security-branding.png";
        return $branding;
    }
}
