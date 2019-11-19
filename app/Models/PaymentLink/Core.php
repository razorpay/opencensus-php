<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\User;
use RZP\Models\Order;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\LineItem;
use Razorpay\Trace\Logger;
use RZP\Services\UfhService;
use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\Template\UdfSchema;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\Template\Hosted as HostedTemplate;

class Core extends Base\Core
{
    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    /**
     * Payment link's hosted base url.
     * @var string
     */
    protected $plHostedBaseUrl;

    const PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP = 'PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP';

    public function __construct()
    {
        parent::__construct();

        $this->elfin           = $this->app['elfin'];
        $this->plHostedBaseUrl = $this->app['config']->get('app.payment_link_hosted_base_url');
    }

    /**
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  User\Entity     $user
     *
     * @return Entity
     * @throws BadRequestException
     * @throws BaseException
     */
    public function create(array $input, Merchant\Entity $merchant, User\Entity $user = null): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        $paymentLink = (new Entity)->generateId();

        // Association of merchant must happens before build() call as the same is needed in validations
        $paymentLink->merchant()->associate($merchant);

        $paymentLink->user()->associate($user);

        $settings = $input[Entity::SETTINGS] ?? [];

        if (isset($input[Entity::PAYMENT_PAGE_ITEMS]))
        {
            $settings[Entity::VERSION] = Version::V2;

            if (($this->isPaymentPageV3Enabled() === false) and
                (count($input[Entity::PAYMENT_PAGE_ITEMS]) === 1))
            {
                $input = array_merge($input, $this->getPaymentPageUpdateInputFromItemInput($input[Entity::PAYMENT_PAGE_ITEMS][0]));
            }
        }
        else
        {
            $settings[Entity::VERSION] = Version::V1;
        }

        $paymentLink->build($input);

        $this->createAndSetShortUrl($paymentLink, $input[Entity::SLUG] ?? null);

        if ($this->isPaymentPageV3Enabled() === false)
        {
            $this->addAdditionalDataToSettings($settings, $paymentLink);
        }

        $this->repo->transaction(function() use ($paymentLink, $settings, $input)
        {
            $this->upsertSettings($paymentLink, $settings);

            $this->repo->saveOrFail($paymentLink);

            if ($settings[Entity::VERSION] === Version::V2)
            {
                $this->createPaymentPageItems($input, $paymentLink);
            }
            else
            {
                $this->createPaymentPageItemInternally($paymentLink);
            }
        });

        $this->repo->loadRelations($paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

        $this->trackPaymentPageCreatedEvent($paymentLink, $input);

        return $paymentLink;
    }

    /**
     * @param  Entity $paymentLink
     * @param  array  $input
     *
     * @return Entity
     * @throws BadRequestException
     * @throws BaseException
     */
    public function update(Entity $paymentLink, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_UPDATE_REQUEST,
            [
                Entity::ID    => $paymentLink->getId(),
                Entity::INPUT => $input,
            ]);

        $this->repo->transaction(function() use ($paymentLink, $input)
        {
            $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

            $settings = $input[Entity::SETTINGS] ?? [];

            if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === true)
            {
                (new PaymentPageItem\Core)->updatePaymentPageItemsAsPut(
                    $input[Entity::PAYMENT_PAGE_ITEMS],
                    $this->merchant,
                    $paymentLink
                );

                $settings[Entity::VERSION] = Version::V2;
            }
            else if ($paymentLink->getVersion() !== Version::V2)
            {
                $paymentPageItemUpdateInput = $this->getPaymentPageItemUpdateInput($input, $paymentLink);

                if (empty($paymentPageItemUpdateInput) === false)
                {
                    $paymentPageItems = $paymentLink->paymentPageItems()->get();

                    if ($paymentPageItems->isEmpty() === false)
                    {
                        $paymentPageItem = $paymentPageItems->get(0);

                        (new PaymentPageItem\Core)->update($paymentPageItem, $paymentPageItemUpdateInput);
                    }
                }

                $this->addPositionToCustomFields($settings, $paymentLink);
            }

            $paymentPageItems = $paymentLink->paymentPageItems()->get();

            if (($this->isPaymentPageV3Enabled() === false) and
                (count($paymentPageItems) === 1) and
                (empty($input[Entity::PAYMENT_PAGE_ITEMS]) === false))
            {
                $input = array_merge(
                    $input,
                    $this->getPaymentPageUpdateInputFromItemInput($input[Entity::PAYMENT_PAGE_ITEMS][0])
                );
            }

            $paymentLink->edit($input);

            $this->changeStatusAfterUpdateIfApplicable($paymentLink);

            $this->upsertSettings($paymentLink, $settings);

            $this->repo->saveOrFail($paymentLink);
        });

        $this->updateShortUrlIfApplicable($paymentLink, $input);

        $this->repo->loadRelations($paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * Attempts recreating short URL for payment link in case of new slug in patch input
     *
     * @param Entity $paymentLink
     * @param array  $input
     *
     * @throws BadRequestException
     * @throws BaseException
     */
    public function updateShortUrlIfApplicable(Entity $paymentLink, array $input)
    {
        if ((($slug = $input[Entity::SLUG] ?? null) !== null) and
            // In patch requests frontned can send same slug as input and gimli
            // request will fail with duplicate slug/alias, so just ignore.
            ($slug !== $paymentLink->getSlugFromShortUrl()) and
            ($this->isTestMode() === false))
        {
            $this->createAndSetShortUrl($paymentLink, $slug);

            $this->repo->saveOrFail($paymentLink);
        }
    }

    public function deactivate(Entity $paymentLink): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_DEACTIVATE_REQUEST,
            [
                Entity::ID => $paymentLink->getPublicId(),
            ]);

        $this->repo->transaction(function() use ($paymentLink)
        {
            $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

            $paymentLink->getValidator()->validateDeactivateOperation();

            $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::DEACTIVATED);

            $this->repo->saveOrFail($paymentLink);
        });

        $this->trace->info(TraceCode::PAYMENT_LINK_DEACTIVATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    public function activate(Entity $paymentLink, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_ACTIVATE_REQUEST,
            [
                Entity::ID    => $paymentLink->getPublicId(),
                Entity::INPUT => $input,
            ]);

        $this->repo->transaction(function() use ($paymentLink, $input)
        {
            $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

            $paymentLink->getValidator()->validateActivateOperation();

            $paymentLink->edit($input);

            $paymentLink->getValidator()->validateShouldActivationBeAllowed();

            $this->changeStatus($paymentLink, Status::ACTIVE, null);

            $this->repo->saveOrFail($paymentLink);
        });

        $this->trace->info(TraceCode::PAYMENT_LINK_ACTIVATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * Sends email/sms notifications to a customer with a payment link
     *
     * @param  Entity $paymentLink
     * @param  array  $input
     */
    public function sendNotification(Entity $paymentLink, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_SEND_NOTIFICATION,
            [
                Entity::ID    => $paymentLink->getId(),
                Entity::INPUT => $input,
            ]);

        $paymentLink->getValidator()->validateSendNotification($input);

        (new Notifier)->notifyByEmailAndSms($paymentLink, $input);
    }

    /**
     * Validates if new payment initiation should be allowed or not.
     * Note: This is intentionally not in Validator class, because there is much logic(probably more very soon) and it
     * accesses repository as well.
     *
     * @param Entity         $paymentLink
     * @param Payment\Entity $payment
     *
     * @throws BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateIsPaymentInitiatable(Entity $paymentLink, Payment\Entity $payment)
    {
        $this->trace->count(Metric::PAYMENT_PAGE_PAYMENT_ATTEMPTS_TOTAL);

        // 1. Validates amount, if applicable
        $paymentLink->getValidator()->validatePaymentAmount($payment);

        $paymentLink->getValidator()->validatePaymentCurrency($payment);

        // 2. Validates Payment notes (UDF values), if applicable
        $udfSchema = new UdfSchema($paymentLink);

        if ($udfSchema->exists() === true)
        {
            $paymentNotes = $payment->getNotes()->toArray();

            $udfSchema->validate($paymentNotes);
        }

        if ($paymentLink->getVersion() === Version::V2) {
            if ($payment->hasOrder() === false) {
                throw new BadRequestValidationFailureException(
                    'order_id is required to create payment for payment page v2'
                );
            }
        }

        if ($payment->hasOrder() === true)
        {
            $order = $payment->order;

            $lineItems = $order->lineItems()->get();

            if ($lineItems->count() === 0)
            {
                throw new BadRequestValidationFailureException(
                    'order does not belongs to the given payment page'
                );
            }

            foreach ($lineItems as $lineItem)
            {
                if ($lineItem->getRefType() !== E::PAYMENT_PAGE_ITEM)
                {
                    throw new BadRequestValidationFailureException(
                        'order does not belongs to the given payment page'
                    );
                }

                $paymentPageItem = $lineItem->ref;

                if ($paymentLink->getId() !== $paymentPageItem->paymentLink->getId())
                {
                    throw new BadRequestValidationFailureException(
                        'order does not belongs to the given payment page'
                    );
                }
            }
        }

        // 3. Validates payment link is active and has payment slots available
        if (($paymentLink->isPayable() === false) or
            ($this->hasPaymentSlots($paymentLink, $payment) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE,
                null,
                [
                    E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
                ]);
        }
    }

    /**
     * This method is called post a payment capture is attempted (failed or success) in Processor/Authorize. Refer below
     * cases on what this method handles.
     *
     * @param Payment\Entity $payment
     */
    public function postPaymentCaptureAttemptProcessing(Payment\Entity $payment)
    {
        assertTrue($payment->hasPaymentLink());

        $paymentLink = $payment->paymentLink;

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_CAPTURE_PROCESS,
            [
                'payment_id'     => $payment->getId(),
                'payment_status' => $payment->getStatus(),
                'payment_link'   => $paymentLink->toArrayPublic(),
            ]);

        //
        // Case 1: If payment was not captured (i.e. stuck in authorized state), refund it immediately and return as
        // there is nothing else to be done here.
        //
        $shouldRefundPayment = ($payment->isCaptured() === false);
        if ($shouldRefundPayment === true)
        {
            return $this->refundPayment($paymentLink, $payment);
        }

        $this->repo->transaction(function() use ($paymentLink, $payment, & $shouldRefundPayment)
        {
            $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

            //
            // Case 2: If payment is captured and there the link is still payable, accept the payment and update entity
            // Note: Updating entity happens in transaction with lock on pl entity, so other process doesn't read & work
            // on bad value.
            //
            if ($paymentLink->isPayable() === true)
            {
                $this->updatePaymentLinkAfterPaymentCapture($paymentLink, $payment);
            }
            //
            // Case 3: If payment is captured but now the link is not payable, refund it immediately
            // Note: We are just setting up a flag here and not initiating the refund here actually, because this block
            // is wrapped in a db transaction. We do actual refund outside this block.
            //
            else
            {
                $shouldRefundPayment = true;
            }
        });

        // Follow up to Case 3 (Refer above ^ comment)
        if ($shouldRefundPayment === true)
        {
            $this->refundPayment($paymentLink, $payment);
        }
    }

    public function createOrder(Entity $paymentLink, array $input)
    {
        $paymentLink->getValidator()->validatePaymentLinkToCreateOrder();

        $paymentLink->getValidator()->validateInput('create_order', $input);

        $input = $this->modifyAndValidateInputToCreateLineItems($input, $paymentLink);

        $totalAmount = $this->getTotalAmountForOrder($input[Entity::LINE_ITEMS]);

        $order = (new Order\Core)->create(
            [
                Order\Entity::AMOUNT   => $totalAmount,
                Order\Entity::CURRENCY => $paymentLink->getCurrency(),
            ],
            $paymentLink->merchant
        );

        (new LineItem\Core)->createMany($input[Entity::LINE_ITEMS], $this->merchant, $order);

        $lineItems = $order->lineItems()->get();

        return [
            Entity::ORDER      => $order,
            Entity::LINE_ITEMS => $lineItems
        ];
    }

    protected function createPaymentPageItemInternally(Entity $paymentLink)
    {
        $paymentPageItemInput = $this->getPaymentPageItemCreateInput($paymentLink);

        (new PaymentPageItem\Core)->create(
            $paymentPageItemInput,
            $this->merchant,
            $paymentLink
        );
    }

    protected function addPositionToCustomFields(array & $settings, Entity $paymentLink)
    {
        if ((isset($settings[Entity::UDF_SCHEMA]) === false) or
            ($paymentLink->getVersion() === Version::V2) or
            ($this->isPaymentPageV3Enabled() === true))
        {
            return;
        }

        $udfSchema = json_decode($settings[Entity::UDF_SCHEMA] ?? '{}');

        $modifiedUdfSchema = [];

        $index = 0;

        foreach ($udfSchema as $item)
        {
            $item->settings = ['position' => $index + 3];

            $modifiedUdfSchema[$index] = $item;

            $index += 1;
        }

        $settings[Entity::UDF_SCHEMA] = json_encode($modifiedUdfSchema);
    }

    public function migratePaymentPageItems(array $input)
    {
        $paymentPages = [];

        if (isset($input[Entity::IDS]) === true)
        {
            foreach ($input[Entity::IDS] as $id)
            {
                $paymentPages[] = $this->repo->payment_link->findByPublicId($id);
            }
        }
        else
        {
            $limit = $input['limit'] ?? 1000;

            $paymentPages = $this->repo->payment_link->getAllPaymentPagesForMigration($limit);
        }

        $this->trace->info(
            TraceCode::PAYMENT_PAGES_MIGRATION_REQUEST_RECEIVED
        );


        $migratedPaymentPages = [];

        $migrationFailedPaymentPages = [];

        foreach ($paymentPages as $paymentPage)
        {
            try
            {
                $this->migratePaymentPage($paymentPage);

                $migratedPaymentPages[] = $paymentPage->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $migrationFailedPaymentPages[] = $paymentPage->getId();
            }
        }

        $summary = [
            'total'                          => count($paymentPages),
            'migrated_payment_pages'         => $migratedPaymentPages,
            'migration_failed_payment_pages' => $migrationFailedPaymentPages,
        ];

        $tracePayload         = $summary;
        $tracePayload['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::PAYMENT_PAGES_MIGRATED,
            $tracePayload
        );

        return $summary;
    }

    public function migratePaymentPageItemsForMinPurchase(array $input)
    {
        $redis = $this->app['redis'];

        $limit = $input['limit'] ?? 1000;

        $lastSyncTimestamp = $redis->get(self::PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP);

        if ($lastSyncTimestamp === null)
        {
            $lastSyncTimestamp = 0;
        }

        $paymentPages = $this->repo->payment_link->getAllPaymentPagesForMigrationOfMinPurchase($lastSyncTimestamp, $limit);

        $this->trace->info(
            TraceCode::PAYMENT_PAGES_MIGRATION_REQUEST_RECEIVED
        );

        $migratedPaymentPages = [];

        $migrationFailedPaymentPages = [];

        foreach ($paymentPages as $paymentPage)
        {
            try
            {
                $this->migratePaymentPageForMinPurchase($paymentPage);

                $migratedPaymentPages[] = $paymentPage->getId();

                $redis->set(self::PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP, $paymentPage->getCreatedAt());
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $migrationFailedPaymentPages[] = $paymentPage->getId();
            }
        }

        $summary = [
            'total'                          => count($paymentPages),
            'migrated_payment_pages'         => $migratedPaymentPages,
            'migration_failed_payment_pages' => $migrationFailedPaymentPages,
        ];

        $tracePayload         = $summary;
        $tracePayload['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::PAYMENT_PAGES_MIGRATED,
            $tracePayload
        );

        return $summary;
    }

    protected function addAdditionalDataToSettings(array & $settings, Entity $paymentLink)
    {
        $this->addPositionToCustomFields($settings, $paymentLink);

        $settings[Entity::CHECKOUT_OPTIONS] = [
            'email' => 'email',
            'phone' => 'phone',
        ];

        $settings[Entity::PAYMENT_BUTTON_LABEL] = 'Pay';
    }

    protected function migratePaymentPage(Entity $paymentPage)
    {
        (new PaymentPageItem\Core)->migratePaymentPageItem($paymentPage);

        $settings = $paymentPage->getSettings()->toArray();

        $this->addAdditionalDataToSettings($settings);

        $this->upsertSettings($paymentPage, $settings);

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_MIGRATED,
            [Entity::ID => $paymentPage->getId()]
        );
    }

    protected function createPaymentPageItems(array $input, Entity $paymentLink)
    {
        (new PaymentPageItem\Core)->createMany(
            $input[Entity::PAYMENT_PAGE_ITEMS],
            $this->merchant,
            $paymentLink
        );
    }

    protected function migratePaymentPageForMinPurchase(Entity $paymentPage)
    {
        (new PaymentPageItem\Core)->migratePaymentPageItemForMinPurchase($paymentPage);

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_MIGRATED,
            [Entity::ID => $paymentPage->getId()]
        );
    }

    protected function updatePaymentLinkAfterPaymentCapture(Entity $paymentLink, Payment\Entity $payment)
    {
        // Multiple payment process attempts to update attributes of link entity.
        $this->repo->assertTransactionActive();

        $paymentLink->incrementTotalAmountPaidBy($payment->getAdjustedAmountWrtCustFeeBearer());

        $paymentPageItems = $paymentLink->paymentPageItems()->get();

        if ($payment->hasOrder() !== true)
        {
            // Note's units value is validated during payment creation against payment & link's amount, defaults to 1.
            $units = (int) ($payment->getNotes()[Entity::UNITS] ?? 1);

            $paymentLink->incrementTimesPaidBy($units);

            if ($paymentPageItems->isEmpty() === false)
            {
                $paymentPageItem = $paymentPageItems->get(0);

                $paymentPageItem->incrementQuantitySold($units);
                $paymentPageItem->incrementTotalAmountPaidBy($payment->getAdjustedAmountWrtCustFeeBearer());

                $this->repo->saveOrFail($paymentPageItem);
            }
        }
        else
        {
            $order = $payment->order;

            $lineItems = $order->lineItems()->get();

            foreach ($lineItems as $lineItem)
            {
                $paymentPageItem = $lineItem->ref;

                $paymentPageItem->incrementQuantitySold($lineItem->getQuantity());
                $paymentPageItem->incrementTotalAmountPaidBy($lineItem->getQuantity() * $lineItem->getAmount());

                $paymentPageItem->saveOrFail();
            }

            if ((count($paymentPageItems) === 1) and ($this->isPaymentPageV3Enabled() === false))
            {
                foreach ($lineItems as $lineItem)
                {
                    $paymentLink->incrementTimesPaidBy($lineItem->getQuantity());
                }
            }
        }

        if ($paymentLink->isTimesPayableExhausted() === true)
        {
            $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::COMPLETED);
        }

        $this->repo->saveOrFail($paymentLink);

        $this->trace->info(
            TraceCode::PAYMENT_LINK_UPDATED_POST_PAYMENT_CAPTURE,
            [
                Entity::PAYMENT_ID => $payment->getId(),
                E::PAYMENT_LINK    => $paymentLink->toArrayPublic(),
            ]);

        $this->trace->count(Metric::PAYMENT_PAGE_PAID_TOTAL);
    }

    /**
     * This is called after edit/update operation. Post building the entity with request input we check if payment
     * link's status needs changing.
     *
     * Payment link's status:
     * - will be marked complete if all the stock of the pp items are exhausted
     *
     * Currently there is no other cases. Expire by edits will not affect this because that must already by at least
     * 15 minutes in future (validated via Validator method during build).
     *
     * @param Entity $paymentLink
     */
    protected function changeStatusAfterUpdateIfApplicable(Entity $paymentLink)
    {
        $this->repo->assertTransactionActive();

        if ($paymentLink->isTimesPayableExhausted() === true)
        {
            $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::COMPLETED);
        }
    }

    /**
     * Changes payment link's status. Every status change must happen via this method which keeps a uniform log of
     * status changes and probably could do further things i.e. validation etc.
     *
     * @param Entity      $paymentLink
     * @param string      $status
     * @param string|null $statusReason
     */
    protected function changeStatus(Entity $paymentLink, string $status, string $statusReason = null)
    {
        //
        // Caller of this function must be wrapped in a database txn because we are updating entity's attributes &
        // status which are shared in multiple payment process & entity operation in parallel.
        //
        $this->repo->assertTransactionActive();

        $oldStatus       = $paymentLink->getStatus();
        $oldStatusReason = $paymentLink->getStatusReason();

        $paymentLink->setStatus($status);
        $paymentLink->setStatusReason($statusReason);

        $this->trace->debug(
            TraceCode::PAYMENT_LINK_STATUS_CHANGE,
            [
                Entity::ID                 => $paymentLink->getId(),
                Entity::FROM_STATUS        => $oldStatus,
                Entity::FROM_STATUS_REASON => $oldStatusReason,
                Entity::TO_STATUS          => $status,
                Entity::TO_STATUS_REASON   => $statusReason,
            ]);
    }

    protected function trackPaymentPageCreatedEvent(Entity $paymentLink, array $input)
    {
        if (empty($input[Entity::TEMPLATE_TYPE]) === true)
        {
            return;
        }

        $customProperties[Entity::TEMPLATE_TYPE] = $input[Entity::TEMPLATE_TYPE];

        $this->app['diag']->trackPaymentPageEvent(EventCode::PAYMENT_PAGE_CREATED, $paymentLink, null, $customProperties);
    }

    /**
     * Given payment link is payable(i.e. active and not expired etc), checks if a new payment can be accepted by
     * counting existing succeeding payments (i.e. payments in created/authorized statuses).
     *
     * @param  Entity         $paymentLink
     * @param  Payment\Entity $payment
     *
     * @return boolean
     */
    protected function hasPaymentSlots(Entity $paymentLink, Payment\Entity $payment): bool
    {
        if ($payment->hasOrder() === false)
        {
            // Note's units value is validated during payment creation against payment & link's amount, defaults to 1.
            $paymentUnits = (int) ($payment->getNotes()[Entity::UNITS] ?? 1);
            $timesPaid    = $paymentLink->getTimesPaid();
            $timesPayable = $paymentLink->getTimesPayable();

            // Just return if there is no limit on number of payments
            if ($timesPayable === null)
            {
                return true;
            }

            $succeedingPaymentUnits = $this->repo->payment_link->getSucceedingPaymentUnits($paymentLink);

            $slotsAvailable = $timesPayable - $timesPaid - $succeedingPaymentUnits;

            return ($slotsAvailable >= $paymentUnits);
        }
        else
        {
            $order = $payment->order;

            $lineItems = $order->lineItems()->get();

            $succeedingPayments = $this->repo->payment_link->getSucceedingPayments($paymentLink);

            $paymentPageItemQuantity = $this->getActivePaymentQuantityCount($succeedingPayments);

            foreach ($lineItems as $lineItem)
            {
                $paymentPageItem = $lineItem->ref;

                $neededQuantity = $this->getNeededQuantity($lineItem, $paymentPageItemQuantity);

                if ($paymentPageItem->isSlotLeft($lineItem->getQuantity() + $neededQuantity) === false)
                {
                    return false;
                }
            }

            return true;
        }
    }

    protected function getActivePaymentQuantityCount($payments): array
    {
        $paymentPageItemQuantity = [];

        foreach ($payments as $payment)
        {
            if ($payment->hasOrder() === true)
            {
                $order = $payment->order;

                $lineItems = $order->lineItems()->get();

                foreach ($lineItems as $lineItem)
                {
                    $paymentPageItem = $lineItem->ref;

                    if (isset($paymentPageItemQuantity[$paymentPageItem->getId()]) !== true)
                    {
                        $paymentPageItemQuantity[$paymentPageItem->getId()] = 0;
                    }

                    $paymentPageItemQuantity[$paymentPageItem->getId()] += $lineItem->getQuantity();
                }
            }
        }

        return $paymentPageItemQuantity;
    }

    protected function getNeededQuantity(LineItem\Entity $lineItem, array $paymentPageItemQuantity): int
    {
        $paymentPageItem = $lineItem->ref;

         return (int) ($paymentPageItemQuantity[$paymentPageItem->getId()] ?? 0);
    }

    /**
     * @param Entity      $paymentLink
     * @param string|null $slug
     *
     * @throws BadRequestException
     * @throws BaseException
     */
    protected function createAndSetShortUrl(Entity $paymentLink, string $slug = null)
    {
        //
        // Temporary: We ignore custom slug in test mode. Practical case is
        // merchant consumes his slug in test mode while exploring and we want
        // to avoid it. Better approach being discussed but for now this us safeguard.
        // Same check exists at updateShortUrlIfApplicable() as well.
        //
        if (($this->isTestMode() === true) and
            ($slug !== null))
        {
            $slug = null;
        }

        list($url, $params, $fail) = $this->getShortenUrlRequestParams($paymentLink, $slug);

        try
        {
            $shortUrl = $this->elfin->shorten($url, $params, $fail);

            $paymentLink->setShortUrl($shortUrl);
        }
        catch (BaseException $e)
        {
            // TODO: Gimli should return 4xx & Elfin service should propagate that error to callee
            if (preg_match('/Duplicate|Blacklisted/', $e->getDataAsString()) === 1)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_LINK_SLUG_GENERATE_FAILED,
                    Entity::SLUG,
                    [
                        Entity::SLUG => $slug,
                    ]);
            }

            throw $e;
        }
    }

    protected function getShortenUrlRequestParams(Entity $paymentLink, string $slug = null): array
    {
        // Following are default set of parameters, when there is no slug passed in input
        // URL: https://pages.razorpay.in/pl_10000000000000/view OR https://pages.razorpay.in/AlphaNumMin4Max30Slug
        $url = $paymentLink->getHostedViewUrl($this->plHostedBaseUrl, $slug);
        // Fail: In case not able to shorten URL, will keep above value itself as short URL and continue with creation
        $fail = false;
        // Ptype: Input request for Gimli
        $params = ['ptype' => 'link'];

        // If slug is passed in input, we override above parameters in following way
        if ($slug !== null)
        {
            // Fail: If failed to shorten the URL, do not continue with creation and fail
            $fail = true;
            // No fall back: Only use Gimli(our shortener service) and do not fall back to Bitly etc if that fails
            $this->elfin->setNoFallback();
            // Additional parameters/metadata which gets used later in rendering view endpoint
            $params += [
                'alias'          => $slug,
                'fail_if_exists' => true,
                'metadata'       => [
                    'mode'   => $this->mode,
                    'entity' => $paymentLink->getEntity(),
                    'id'     => $paymentLink->getPublicId(),
                ],
            ];
        }

        return [$url, $params, $fail];
    }

    /**
     * Called from CRON.
     * Updates status to INACTIVE, status_reason to EXPIRED of all payment links which are active and past expire_by.
     * @return array
     */
    public function expirePaymentLinks(): array
    {
        $timeStarted = microtime(true);

        $paymentLinks = $this->repo->payment_link->getActiveAndPastExpireByPaymentLinks();

        $summary = [
            'total_count' => $paymentLinks->count(),
            'failed_ids'  => [],
        ];

        foreach ($paymentLinks as $paymentLink)
        {
            try
            {
                $this->expirePaymentLink($paymentLink);
            }
            catch (\Throwable $e)
            {
                $summary['failed_ids'][] = $paymentLink->getId();

                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::PAYMENT_LINK_EXPIRE_ERROR,
                    [
                        Entity::ID => $paymentLink->getId(),
                    ]);
            }
        }

        $summary['time_taken'] = (microtime(true) - $timeStarted) / 1000;

        $this->trace->debug(TraceCode::PAYMENT_LINK_EXPIRE_CRON_SUMMARY, $summary);

        return $summary;
    }

    /**
     * Returns an array of the payload to be consumed by the view template.
     *
     * @param  Entity $paymentLink
     *
     * @return array
     */
    public function getHostedViewPayload(Entity $paymentLink): array
    {
        // Fetch serialized view data for the view to consume
        $payload['data'] = (new ViewSerializer($paymentLink))->serializeForHosted();

        // Append UDF Schema as a JSON string, if defined
        $payload[Entity::UDF_SCHEMA] = (new UdfSchema($paymentLink))->getSchema();

        return $payload;
    }

    /**
     * Returns the name of the Payment link view template to be used.
     *
     * @param  Entity $paymentLink
     *
     * @return string
     */
    public function getHostedViewTemplate(Entity $paymentLink): string
    {
        $templateId = $paymentLink->getHostedTemplateId();

        if ($templateId !== null)
        {
            $templateAccessor = new HostedTemplate($templateId);
            $view = 'hostedpage.' . $templateAccessor->getViewName();
        }
        else
        {
            $view = 'payment_link.hosted_with_udf';
        }

        return $view;
    }

    /**
     * It uploads the images in S3 bucket and returns the image cdn urls.
     *
     * @param array           $input Includes images to be uploaded in s3 bucket.
     * @param Merchant\Entity $merchant
     *
     * @return array Image cdn urls
     * @throws \RZP\Exception\ServerErrorException
     */
    public function upload(array $input, Merchant\Entity $merchant): array
    {
        $urls = [];
        // Todo: Uncomment below line and remove line below that once devops issue(refer pr desc) fixed.
        // $cdn  = $this->config->get('url.cdn.' . $this->env);
        $cdn  = sprintf(
            'https://s3.ap-south-1.amazonaws.com/rzp-%s-merchant-assets',
            $this->env === 'production' ? 'prod' : 'nonprod');

        foreach ($input['images'] as $image)
        {
            $filenameWithoutExt = str_before($image->getClientOriginalName(), '.' . $image->getClientOriginalExtension());

            $uploadFilename = 'payment-link/description/' . $filenameWithoutExt . '_' . UniqueIdEntity::generateUniqueId();

            $file = (new UfhService($this->app))->uploadFileAndGetUrl(
                $image,
                $uploadFilename,
                Constants::PAYMENT_LINK_DESCRIPTION,
                $merchant,
                ['Content-Disposition' => 'inline']);

            $urls[] = $cdn . '/' . $file[Constants::RELATIVE_LOCATION];
        }

        return $urls;
    }

    protected function updateFromPaymentPageItemInput(Entity $paymentPage, array $input)
    {
        $paymentPage->edit($input);

        $settings = $input[Entity::SETTINGS] ?? [];

        if (empty($settings) === false)
        {
            $this->upsertSettings($paymentPage, $settings);
        }

        $this->repo->saveOrFail($paymentPage);
    }

    public function updatePaymentPageItem(PaymentPageItem\Entity $paymentPageItem, array $input)
    {
        $paymentPageItem = $this->repo->transaction(
            function() use ($paymentPageItem, $input)
            {
                $paymentLink = $paymentPageItem->paymentLink;

                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                $this->repo->payment_page_item->reload($paymentPageItem);

                $paymentPageItem = (new PaymentPageItem\Core)->update($paymentPageItem, $input);

                $paymentPageItems = $paymentPageItem->paymentLink->paymentPageItems()->get();

                if (($this->isPaymentPageV3Enabled() === false) and
                    (count($paymentPageItems) === 1))
                {
                    $paymentPageUpdateInput = $this->getPaymentPageUpdateInputFromItemInput($input);

                    if (empty($paymentPageUpdateInput) === false) {
                        $this->updateFromPaymentPageItemInput($paymentLink, $paymentPageUpdateInput);
                    }
                }

                $this->changeStatusAfterUpdateIfApplicable($paymentLink);

                $paymentLink->saveOrFail();

                return $paymentPageItem;
            }
        );

        return $paymentPageItem;
    }

    public function isPaymentPageV3Enabled()
    {
        $treatment = $this->app->razorx->getTreatment($this->merchant->getId(), Constants::PAYMENT_PAGE_V3, $this->mode);

        return $treatment === 'on';
    }

    protected function getPaymentPageUpdateInputFromItemInput(array $input)
    {
        $paymentPageUpdateInput = [];

        if (empty($input[PaymentPageItem\Entity::ITEM]) === false)
        {
            $item = $input[PaymentPageItem\Entity::ITEM];

            if (array_key_exists(Item\Entity::AMOUNT, $item) === true)
            {
                $paymentPageUpdateInput[Entity::AMOUNT] = $item[Item\Entity::AMOUNT];
            }
        }

        if (array_key_exists(PaymentPageItem\Entity::STOCK, $input) === true)
        {
            $paymentPageUpdateInput[Entity::TIMES_PAYABLE] = $input[PaymentPageItem\Entity::STOCK];
        }

        if (array_key_exists(PaymentPageItem\Entity::MIN_PURCHASE, $input) === true)
        {
            if ($input[PaymentPageItem\Entity::MIN_PURCHASE] !== null)
            {
                $paymentPageUpdateInput[Entity::SETTINGS][Entity::ALLOW_MULTIPLE_UNITS] = '1';
            }
            else
            {
                $paymentPageUpdateInput[Entity::SETTINGS][Entity::ALLOW_MULTIPLE_UNITS] = '0';
            }
        }

        return $paymentPageUpdateInput;
    }

    /**
     * Updates the status to INACTIVE, status_reason to EXPIRED of an individual expired payment link by locking it.
     *
     * @param Entity $paymentLink
     */
    protected function expirePaymentLink(Entity $paymentLink)
    {
        $this->repo->transaction(
            function () use ($paymentLink)
            {
                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                // Continues with expiration only if current status is active and expire_by's value is past now
                if (($paymentLink->isActive() === true) and
                    ($paymentLink->isPastExpireBy() === true))
                {
                    $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::EXPIRED);

                    $this->repo->saveOrFail($paymentLink);
                }
            });

        $this->trace->count(Metric::PAYMENT_PAGE_EXPIRED_TOTAL);
    }

    /**
     * Initiates refund on a payment. This happens in cases as described in
     * postPaymentCaptureAttemptProcessing() method
     *
     * @param Entity         $paymentLink
     * @param Payment\Entity $payment
     */
    protected function refundPayment(Entity $paymentLink, Payment\Entity $payment)
    {
        $processor = new Payment\Processor\Processor($payment->merchant);

        $tracePayload = [
            E::PAYMENT      => $payment->toArrayPublic(),
            E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_REQUEST, $tracePayload);

        $refund = null;

        try
        {
            //
            // We use existing payment entity's status attribute to decide which method to call for refund.
            // Additionally while calling the refund{X}Payment() method we pass reloaded payment entity because reload
            // doesn't happen in the called method. This is an additional level of check for concurrent issues. The
            // payment's refund will fail if the status has changed in between. We can't do reload before that because
            // then condition check will happen on new status.
            //
            if ($payment->isAuthorized() === true)
            {
                $refund = $processor->refundAuthorizedPayment($payment->reload());
            }
            else if ($payment->isCaptured() === true)
            {
                $refund = $processor->refundCapturedPayment($payment->reload());
            }
            else
            {
                $this->trace->critical(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::CRITICAL,
                TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
        }

        // If refund was made, increments counter of at what payment status the refund was made
        if ($refund !== null)
        {
            $dimensions = ['payment_status' => $payment->getStatus()];

            $this->trace->count(Metric::PAYMENT_PAGE_PAYMENT_REFUNDS_TOTAL, $dimensions);
        }

        $tracePayload = array_merge($tracePayload, [E::REFUND => optional($refund)->toArrayPublic()]);
        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_HANDLED, $tracePayload);
    }

    /**
     * Every payment link could have set of setting associated. Ref: Model\Settings.
     * @param  Entity $paymentLink
     * @param  array  $settings
     */
    protected function upsertSettings(Entity $paymentLink, array $settings)
    {
        if (empty($settings) === false)
        {
            $paymentLink->getSettingsAccessor()->upsert($settings)->save();
        }
    }

    protected function getPaymentPageItemCreateInput(Entity $paymentLink)
    {
        $itemInput[Item\Entity::NAME]     = 'amount';

        $itemInput[Item\Entity::AMOUNT]   = $paymentLink->getAmount();

        $itemInput[Item\Entity::CURRENCY] = $paymentLink->getCurrency();

        $paymentPageItemInput[PaymentPageItem\Entity::ITEM]  = $itemInput;

        $paymentPageItemInput[PaymentPageItem\Entity::STOCK] = $paymentLink->getTimesPayable();

        $paymentPageItemInput[PaymentPageItem\Entity::SETTINGS][PaymentPageItem\Entity::POSITION] = 0;

        $allowMultipleUnits = $paymentLink->getSettings()->toArray()[Entity::ALLOW_MULTIPLE_UNITS] ?? null;

        if ($allowMultipleUnits === '1')
        {
            $paymentPageItemInput[PaymentPageItem\Entity::MIN_PURCHASE] = 1;
        }

        if ($paymentLink->getAmount() === null)
        {
            $minAmount = Currency::getMinAmount($paymentLink->getCurrency());

            $paymentPageItemInput[PaymentPageItem\Entity::MIN_AMOUNT] = $minAmount;
        }

        return $paymentPageItemInput;
    }

    protected function getPaymentPageItemUpdateInput(array $input, Entity $paymentLink)
    {
        $paymentPageItemInput = [];

        if (array_key_exists(Entity::AMOUNT, $input) === true)
        {
            $itemInput[Item\Entity::AMOUNT] = $input[Entity::AMOUNT];

            $paymentPageItemInput[PaymentPageItem\Entity::ITEM] = $itemInput;

            if ($input[Entity::AMOUNT] === null)
            {
                $minAmount = Currency::getMinAmount($paymentLink->getCurrency());

                $paymentPageItemInput[PaymentPageItem\Entity::MIN_AMOUNT] = $minAmount;
            }
        }

        if (array_key_exists(Entity::TIMES_PAYABLE, $input) === true)
        {
            $paymentPageItemInput[PaymentPageItem\Entity::STOCK] = $input[Entity::TIMES_PAYABLE];
        }

        return $paymentPageItemInput;
    }

    protected function getTotalAmountForOrder(array $input)
    {
        $totalAmount = 0;

        foreach ($input as $lineItem)
        {
            $totalAmount += $lineItem[LineItem\Entity::AMOUNT] * ($lineItem[LineItem\Entity::QUANTITY] ?? 1);
        }

        return $totalAmount;
    }

    protected function modifyAndValidateInputToCreateLineItems(array $input, Entity $paymentLink)
    {
        $modifiedInput = [];

        $PPIRepo = new PaymentPageItem\Repository;

        $PPIValidator = new PaymentPageItem\Validator();

        foreach ($input[Entity::LINE_ITEMS] as $lineItem)
        {
            $paymentPageItemId = $lineItem[Entity::PAYMENT_PAGE_ITEM_ID];

            unset($lineItem[Entity::PAYMENT_PAGE_ITEM_ID]);

            $paymentPageItemId = PaymentPageItem\Entity::verifyIdAndStripSign($paymentPageItemId);

            $paymentPageItem = $PPIRepo->findByIdAndPaymentLinkEntityOrFail(
                $paymentPageItemId,
                $paymentLink
            );

            $itemId = $paymentPageItem->getItemId();

            $lineItem[LineItem\Entity::ITEM_ID] = Item\Entity::getSignedId($itemId);

            $lineItem[LineItem\Entity::REF] = $paymentPageItem;

            $modifiedInput[Entity::LINE_ITEMS][] = $lineItem;

            $PPIValidator->validateAmountQuantityAndStockOfPPI($paymentPageItem, $lineItem);
        }

        return $modifiedInput;
    }
}
