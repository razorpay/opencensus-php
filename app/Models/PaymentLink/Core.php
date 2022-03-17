<?php

namespace RZP\Models\PaymentLink;

use Cache;
use Carbon\Carbon;
use phpseclib\Crypt\AES;
use RZP\Encryption\AESEncryption;
use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\User;
use RZP\Models\Order;
use RZP\Trace\Tracer;
use RZP\Models\Invoice;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Jobs\NotifyRas;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\LineItem;
use RZP\Models\FileStore;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Jobs\AppsRiskCheck;
use RZP\Services\UfhService;
use RZP\Constants\Entity as E;
use RZP\Models\Invoice\Entity as IE;
use RZP\Exception\BaseException;
use RZP\Models\Currency\Currency;
use RZP\Jobs\PaymentPageProcessor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\MerchantRiskClient;
use RZP\Listeners\ApiEventSubscriber;
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

    protected $paymentHandleHostedBaseUrl;

    protected $merchantRiskService;

    const PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP = 'PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP';

    const RAZORX_ASYNC_UPDATE_EXPERIMENT = 'pp_async_update_experiment';

    const RAZORX_ASYNC_PAYMENT_PAGE_CREATE_DEDUPE = 'RAZORX_ASYNC_PAYMENT_PAGE_CREATE_DEDUPE';

    public function __construct()
    {
        parent::__construct();

        $this->elfin           = $this->app['elfin'];
        $this->plHostedBaseUrl = $this->app['config']->get('app.payment_link_hosted_base_url');
        $this->paymentHandleHostedBaseUrl = $this->app['config']->get('app.payment_handle_hosted_base_url');
        $this->merchantRiskService = $this->app['merchantRiskClient'];
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

        $paymentLink = Tracer::inSpan(['name' => 'payment_page.create.generate_id'], function() {
            return (new Entity)->generateId();
        });

        // Association of merchant must happens before build() call as the same is needed in validations
        Tracer::inSpan(['name' => 'payment_page.create.associate_merchant'], function() use ($paymentLink, $merchant) {
            $paymentLink->merchant()->associate($merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.create.associate_user'], function() use ($paymentLink, $user) {
            $paymentLink->user()->associate($user);
        });

        $settings = $input[Entity::SETTINGS] ?? [];

        $settings[Entity::VERSION] = Version::V2;

        Tracer::inSpan(['name' => 'payment_page.create.build'], function() use ($paymentLink, $input) {
            $paymentLink->build($input);
        });

        if((array_key_exists(Entity::SLUG, $input) === true) and
            ($paymentLink->getViewType() !== ViewType::PAYMENT_HANDLE))
        {
            (new Validator)->validateSlug('validateSlug', $input[Entity::SLUG]);
        }

        Tracer::inSpan(['name' => 'payment_page.create.short_url'], function() use ($paymentLink, $input) {
            $this->createAndSetShortUrl($paymentLink, $input[Entity::SLUG] ?? null);
        });

        $this->repo->transaction(function() use ($paymentLink, $settings, $input)
        {
            Tracer::inSpan(['name' => 'payment_page.create.upsert_settings'], function() use ($paymentLink, $settings) {
                $this->upsertSettings($paymentLink, $settings);
            });

            Tracer::inSpan(['name' => 'payment_page.create.create_page'], function() use ($paymentLink) {
                $this->repo->saveOrFail($paymentLink);
            });

            Tracer::inSpan(['name' => 'payment_page.create.create_items'], function() use ($input, $paymentLink) {
                $this->createPaymentPageItems($input, $paymentLink);
            });
        });

        Tracer::inSpan(['name' => 'payment_page.create.load_relations'], function() use ($paymentLink) {
            $this->repo->loadRelations($paymentLink);
        });
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

        Tracer::inSpan(['name' => 'payment_page.create.events'], function() use ($input, $paymentLink) {
            $this->trackPaymentPageCreatedEvent($paymentLink, $input);
        });

        $this->trace->count(Metric::PAYMENT_PAGE_CREATED_TOTAL, $paymentLink->getMetricDimensions());

        Tracer::inSpan(['name' => 'payment_page.create.dedupe_actions'], function() use ($paymentLink, $merchant) {
            $this->dispatchDedupeCall($paymentLink, $merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.create.dispatch.app_risk_check'], function() use ($paymentLink) {
            $this->dispatchAppRiskCheck($paymentLink);
        });

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        return $paymentLink;
    }

    public function createPaymentHandle(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_CREATE_PAYMENT_PAGE,
            [
                Entity::SLUG        => $input[Entity::SLUG],
                Entity::MERCHANT_ID => $this->merchant->getPublicId()
            ]);

        $paymentPage = $this->createPaymentPageForPaymentHandle($input,  $merchant);

        $this->upsertDefaultPaymentHandleForMerchant($input[Entity::SLUG], $paymentPage->getPublicId());

        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PAYMENT_PAGE_CREATED,
            [
                Entity::SLUG      => $input[Entity::SLUG],
                Entity::MERCHANT_ID => $this->merchant->getPublicId(),
                "payment_page" =>  $paymentPage
            ]);

        return $paymentPage;
    }

    public function updatePaymentHandle(array $input): array
    {
        $merchantSettings = Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        $handleOld = array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE);

        if(empty($handleOld) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle does not exists for this merchant.');
        }

        $handlePageId = array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID);

        $this->createGimliEntryForHandle($input[Entity::SLUG], $this->merchant->getPublicId(), $handlePageId);


        $this->upsertDefaultPaymentHandleForMerchant($input[Entity::SLUG], $handlePageId);

        $url = $this->paymentHandleHostedBaseUrl . '/' . $input[Entity::SLUG];

        $response = [];

        $response[Entity::URL] = $url;

        $response[Entity::TITLE] = $this->merchant->getBillingLabel();

        $response[Entity::SLUG] = $input[Entity::SLUG];

        if(empty($handlePageId) === true)
        {
            return $response;
        }

        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant(
            $handlePageId,
            $this->merchant);

        $paymentLink->setShortUrl($url);

        $this->repo->saveOrFail($paymentLink);

        $response[Entity::ID] = $paymentLink->getPublicId();

        return $response;
    }

    public function getPaymentHandleByMerchant(Merchant\Entity $merchant): array
    {
        $merchantSettings = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        if (empty($merchantSettings) === true || empty($merchantSettings[ENTITY::DEFAULT_PAYMENT_HANDLE]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle does not exists for this merchant. Please create a new one');
        }

        $response[Entity::TITLE] = $merchant->getBillingLabel();

        $response[Entity::SLUG] =  $merchantSettings[ENTITY::DEFAULT_PAYMENT_HANDLE][Entity::DEFAULT_PAYMENT_HANDLE];

        $response[Entity::URL] = $this->paymentHandleHostedBaseUrl . '/' . $response[Entity::SLUG];

        $handlePageId = array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID);

        if(empty($handlePageId) === true)
        {
            return $response;
        }

        $response[ENTITY::ID] = $handlePageId;

        return $response;
    }

    public function createSubscription(Entity $paymentLink, array $input, Merchant\Entity $merchant)
    {
        $ppItemId = $input[Entity::PAYMENT_PAGE_ITEM_ID];

        $ppItemId = Entity::stripDefaultSign($ppItemId);

        $ppItem = $this->repo->payment_page_item->findOrFailPublic($ppItemId);

        $planId = $ppItem->getPlanId();

        if (empty($planId) === true)
        {
            throw new BadRequestValidationFailureException(
                'plan is not present to create a subscription'
            );
        }

        $subscriptionDetails = $ppItem->getProductConfig(PaymentPageItem\Entity::SUBSCRIPTION_DETAILS);

        $subscriptionInput = $this->buildInputForSubscription($planId, $subscriptionDetails, $input);

        $headers['X-Razorpay-Source'] = 'subscription_button';

        $headers['X-Razorpay-SourceId'] = $paymentLink->getId();

        $responseJson = $this->app['module']->subscription->createSubscription($subscriptionInput, $merchant, $headers);

        $this->repo->transaction(function() use ($ppItem)
        {
            $this->repo->payment_page_item->lockForUpdateAndReload($ppItem);

            $ppItem->incrementQuantitySold(1);

            $this->repo->payment_page_item->saveOrFail($ppItem);

        });

        $this->trace->count(Metric::PAYMENT_PAGE_SUBSCRIPTION_CREATED, $paymentLink->getMetricDimensions());

        return ['subscription_id' => $responseJson['id']];
    }

    protected function buildInputForSubscription(string $planId, $subscriptionDetails, array $input)
    {
        $totalCount = $subscriptionDetails['total_count'] ?? 120;

        $quantity = $subscriptionDetails['quantity'] ?? 1;

        $customerNotify = $subscriptionDetails['customer_notify'] ?? 1;

        $inputForSubscription = [
            'plan_id'        => 'plan_'.$planId,
           'total_count'     => $totalCount,
           'quantity'        => $quantity,
           'customer_notify' => $customerNotify,
           'notes'           => $input[Entity::NOTES] ?? null,
       ];

        return $inputForSubscription;
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

        Tracer::inSpan(['name' => 'payment_page.update'], function() use($paymentLink, $input)
        {
            $this->repo->transaction(function () use ($paymentLink, $input) {

                Tracer::inSpan(['name' => 'payment_page.update.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                $settings = $input[Entity::SETTINGS] ?? [];

                if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === true)
                {
                    Tracer::inSpan(['name' => 'payment_page.update.item_as_put'], function() use($input, $paymentLink)
                    {
                        (new PaymentPageItem\Core)->updatePaymentPageItemsAsPut(
                            $input[Entity::PAYMENT_PAGE_ITEMS],
                            $this->merchant,
                            $paymentLink
                        );
                    });

                    $settings[Entity::VERSION] = Version::V2;
                }

                $paymentLink->edit($input);

                $this->changeStatusAfterUpdateIfApplicable($paymentLink);

                Tracer::inSpan(['name' => 'payment_page.update.upsert_settings'], function() use($paymentLink, $settings)
                {
                    $this->upsertSettings($paymentLink, $settings);
                });

                Tracer::inSpan(['name' => 'payment_page.update.save_or_fail'], function() use($paymentLink)
                {
                    $this->repo->saveOrFail($paymentLink);
                });
            });

            $this->updateShortUrlIfApplicable($paymentLink, $input);

            Tracer::inSpan(['name' => 'payment_page.update.load_relations'], function() use($paymentLink)
            {
                $this->repo->loadRelations($paymentLink);
            });

            Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
                $this->dispatchHostedCache($paymentLink);
            });

            $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());
        });

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
            Tracer::inSpan(['name' => 'payment_page.create_and_set_short_url'], function() use($paymentLink, $slug)
            {
                $this->createAndSetShortUrl($paymentLink, $slug);
            });

            Tracer::inSpan(['name' => 'payment_page.update_url.save_or_fail'], function() use($paymentLink)
            {
                $this->repo->saveOrFail($paymentLink);
            });
        }
    }

    public function deactivate(Entity $paymentLink): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_DEACTIVATE_REQUEST,
            [
                Entity::ID => $paymentLink->getPublicId(),
            ]);

        Tracer::inSpan(['name' => 'payment_page.deactivate.transaction'], function() use($paymentLink)
        {
            $this->repo->transaction(function () use ($paymentLink) {

                Tracer::inSpan(['name' => 'payment_page.deactivate.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateDeactivateOperation();
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.change_status'], function() use($paymentLink)
                {
                    $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::DEACTIVATED);
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.save_or_fail'], function() use($paymentLink) {
                    return $this->repo->saveOrFail($paymentLink);
                });
            });
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

        Tracer::inSpan(['name' => 'payment_page.activate.transaction'], function() use($paymentLink, $input)
        {
            $this->repo->transaction(function () use ($paymentLink, $input) {

                Tracer::inSpan(['name' => 'payment_page.activate.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                Tracer::inSpan(['name' => 'payment_page.activate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateActivateOperation();
                });

                $paymentLink->edit($input);

                Tracer::inSpan(['name' => 'payment_page.activate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateShouldActivationBeAllowed();
                });

                Tracer::inSpan(['name' => 'payment_page.activate.change_status'], function() use($paymentLink)
                {
                    $this->changeStatus($paymentLink, Status::ACTIVE, null);
                });

                Tracer::inSpan(['name' => 'payment_page.activate.save_or_fail'], function() use($paymentLink)
                {
                    $this->repo->saveOrFail($paymentLink);
                });
            });

            Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
                $this->dispatchHostedCache($paymentLink);
            });
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

        Tracer::inSpan(['name' => 'payment_page.send_notification.validate'], function() use($input, $paymentLink)
        {
            $paymentLink->getValidator()->validateSendNotification($input);
        });

        Tracer::inSpan(['name' => 'payment_page.send_notification.notify_by_email_and_sms'], function() use($paymentLink, $input)
        {
            (new Notifier)->notifyByEmailAndSms($paymentLink, $input);
        });
    }

    /**
     * Validates if new payment initiation should be allowed or not.
     * Note : Only quantity validations are done here because it is being called early in the flow of
     * create payment
     *
     * @param array $input
     *
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */

    public function validatePaymentPagePaymentFromInput(array $input)
    {
        if (array_key_exists(Payment\Entity::PAYMENT_LINK_ID, $input) === false)
        {
            return;
        }

        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            throw new BadRequestValidationFailureException(
                'order_id is required to create payment for payment page'
            );
        }

        $order = $this->repo->order->findByPublicIdAndMerchant(
                $input[Payment\Entity::ORDER_ID],
                $this->merchant);

        $paymentLinkId = $input[Payment\Entity::PAYMENT_LINK_ID];

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_PAYMENT_VALIDATION,
            [
                'input'     => $paymentLinkId,
            ]);

        $paymentLink   = $this->repo->payment_link->findByPublicIdAndMerchant($paymentLinkId, $this->merchant);

        // 3. Validates payment link is active and has payment slots available
        if (($paymentLink->isPayable() === false) or
            ($this->hasPaymentSlots($paymentLink, $order) === false))
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
     * Validates if new payment initiation should be allowed or not.
     * Note: This is intentionally not in Validator class, because there is much logic(probably more very soon) and it
     * accesses repository as well.
     * quantity validations has been moved to separate function because that is being called early in the flow of
     * create payment
     *
     * @param Entity         $paymentLink
     * @param Payment\Entity $payment
     *
     * @throws BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateIsPaymentInitiatable(Entity $paymentLink, Payment\Entity $payment)
    {
        $this->trace->count(Metric::PAYMENT_PAGE_PAYMENT_ATTEMPTS_TOTAL, $paymentLink->getMetricDimensions());

        $paymentLink->getValidator()->validatePaymentCurrency($payment);

        // 2. Validates Payment notes (UDF values), if applicable
        $udfSchema = new UdfSchema($paymentLink);

        if ($udfSchema->exists() === true)
        {
            $paymentNotes = $payment->getNotes()->toArray();

            $udfSchema->validate($paymentNotes);
        }

        if ($payment->hasOrder() === false) {
            throw new BadRequestValidationFailureException(
                'order_id is required to create payment for payment page'
            );
        }


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

    public function postPaymentCaptureUpdatePaymentPage(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $variant = $this->app->razorx->getTreatment(
            $merchant->getId(),
            self::RAZORX_ASYNC_UPDATE_EXPERIMENT,
            $this->mode
        );

        if ($variant === 'on')
        {
            PaymentPageProcessor::dispatch($this->mode, [
                'payment_id'    => $payment->getId(),
                'start_time'    => millitime(),
                'event'         => PaymentPageProcessor::PAYMENT_CAPTURE_EVENT,
            ]);

            return;
        }

        $this->postPaymentCaptureAttemptProcessing($payment);
    }

    public function postPaymentRefundUpdatePaymentPageDispatcher(Payment\Refund\Entity $refund)
    {
        $context = $this->getRefundContext($refund);
        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_INIT, $context);

        PaymentPageProcessor::dispatch($this->mode, [
            'event'     => PaymentPageProcessor::REFUND_PROCESSED_EVENT,
            'refund_id' => $refund->getId(),
            'merchant'  => $refund->merchant,
            'start_time'=> millitime(),
        ]);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_DISPATCHED, $context);
    }

    public function postPaymentRefundUpdatePaymentPage(Payment\Refund\Entity $refund)
    {
        assertTrue($refund->payment->hasPaymentLink());

        $context = $this->getRefundContext($refund);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_START, $context);

        $this->repo->transaction(function() use ($refund) {
            $lineItems = $refund->payment->order->lineItems()->get();
            $unitsSold  = 0;

            // Partial refund is not supported, will be handling the use case in future
            if ($refund->payment->isFullyRefunded() === true)
            {
                $unitsSold = $lineItems->sum(function ($item) {
                    return $item->getQuantity();
                });
            }

            $this->updateDonationGoalTrackerKeys($refund->payment->paymentLink,  [
                Entity::SOLD_UNITS          => $unitsSold,
                Entity::COLLECTED_AMOUNT    => $refund->getAmount(),
                Entity::SUPPORTER_COUNT     => $unitsSold === 0 ? 0 : 1,
            ], true);
        });

        $this->updateHostedCache($refund->payment->paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_COMPLETED, $context);
    }

    /**
     * This method is called post a payment capture is attempted (failed or success) in Processor/Authorize. Refer below
     * cases on what this method handdles.
     *
     * @param Payment\Entity $payment
     */
    public function postPaymentCaptureAttemptProcessing(Payment\Entity $payment, $async = false)
    {
        assertTrue($payment->hasPaymentLink());

        $paymentLink = $payment->paymentLink;

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_CAPTURE_PROCESS,
            [
                'payment_id'     => $payment->getId(),
                'payment_status' => $payment->getStatus(),
                'payment_link'   => $paymentLink->toArrayPublic(),
                'async'          => $async,
            ]);

        //
        // Case 1: If payment was not captured (i.e. stuck in authorized state), refund it immediately and return as
        // there is nothing else to be done here.
        //
        $shouldRefundPayment = ($payment->isCaptured() === false);

        if ($shouldRefundPayment === true)
        {
            $this->refundPayment($paymentLink, $payment);
            return;
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
                // If payment is autocaptured and payment page is not payable, then we blindly refund the payment
                if ($payment->getAutoCaptured() === true)
                {
                    $shouldRefundPayment = true;
                }
                else
                {
                    // In case payment is not auto captured,(late auth, upi payment edge cases etc)
                    // the merchant has captured manually and expects the payment
                    // to be captured. In such edge cases, even though the page is expired, we update the quantities
                    $this->updatePaymentLinkAfterPaymentCapture($paymentLink, $payment);
                }
            }
        });

        // Follow up to Case 3 (Refer above ^ comment)
        if ($shouldRefundPayment === true)
        {
            $this->refundPayment($paymentLink, $payment);
            return;
        }

        try
        {
            $this->createInvoiceIfEnabled($paymentLink, $payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        $this->doPostPaymentRiskActions($paymentLink, $payment);

        $this->eventPaymentPagePaid($paymentLink, $payment);

        $this->updateHostedCache($paymentLink);
    }

    public function createOrder(Entity $paymentLink, array $input)
    {
        Tracer::inSpan(['name' => 'payment_page.order.create.validate_payment_link'], function() use($paymentLink)
        {
            $paymentLink->getValidator()->validatePaymentLinkToCreateOrder();
        });

        Tracer::inSpan(['name' => 'payment_page.order.create.validate'], function() use($paymentLink, $input)
        {
            $paymentLink->getValidator()->validateInput('create_order', $input);
        });

        $input = Tracer::inSpan(['name' => 'payment_page.order.create.modify_and_validate_input'], function() use($paymentLink, $input)
        {
            return $this->modifyAndValidateInputToCreateLineItems($input, $paymentLink);
        });

        $totalAmount = $this->getTotalAmountForOrder($input[Entity::LINE_ITEMS]);

        $order = Tracer::inSpan(['name' => 'payment_page.order.create.create_order'], function() use($totalAmount, $paymentLink, $input)
        {
            return (new Order\Core)->create(
                [
                    Order\Entity::AMOUNT => $totalAmount,
                    Order\Entity::CURRENCY => $paymentLink->getCurrency(),
                    Order\Entity::PAYMENT_CAPTURE => true,
                    Order\Entity::NOTES => $input[Order\Entity::NOTES] ?? [],
                    Order\Entity::PRODUCT_TYPE => $paymentLink->getProductType(),
                    Order\Entity::PRODUCT_ID => $paymentLink->getId(),
                ],
                $paymentLink->merchant
            );
        });

        Tracer::inSpan(['name' => 'payment_page.order.create.create_line_item'], function() use($order, $input)
        {
            (new LineItem\Core)->createMany($input[Entity::LINE_ITEMS], $this->merchant, $order);
        });

        $lineItems = $order->lineItems()->get();

        return [
            Entity::ORDER      => $order,
            Entity::LINE_ITEMS => $lineItems
        ];
    }

    public function setMerchantDetails(array $settings)
    {
        Tracer::inSpan(['name' => 'payment_page.merchant_details.set.validate'], function() use($settings) {
            (new Validator())->validateSetMerchantDetails($settings);
        });

        $merchant = $this->merchant;

        Tracer::inSpan(['name' => 'payment_page.merchant_details.set.upsert_and_save'], function() use($merchant, $settings)
        {
            Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
                ->upsert($settings)
                ->save();
        });

        return Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();
    }

    public function fetchMerchantDetails()
    {
        $merchant = $this->merchant;

        return Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();
    }

    public function setReceiptDetails(Entity $paymentLink, array $input)
    {
        $validator = new Validator($paymentLink);

        Tracer::inSpan(['name' => 'payment_page.recipts.create.validate'], function() use ($validator, $input) {
            $validator->validateSetInvoiceDetails($input);
        });

        Tracer::inSpan(['name' => 'payment_page.recipts.create.upsert.settings'], function() use ($input, $paymentLink) {
            $this->upsertSettings($paymentLink, $input);
        });

        $receiptSettings = Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)
            ->all();

        $response = array_intersect_key($receiptSettings->toArray(), array_flip(Entity::INVOICE_DETAILS_KEYS));

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        return $response;
    }

    public function getInvoiceDetails(string $paymentId)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.invoice.find_entity'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        if(empty($order) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt is not generated for this payment');
        }

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt is not generated for this payment');
        }

        $invoiceId = $invoice->getPublicId();

        $receipt = $invoice->getReceipt();

        $response = [
            'invoice_id' => $invoiceId,
            'receipt'    => $receipt,
        ];

        $invoiceCore = new Invoice\Core();

        $pdf = Tracer::inSpan(['name' => 'payment_page.invoice.get_fresh_invoice'], function() use($invoiceCore, $invoice)
        {
            return $invoiceCore->getFreshInvoicePdf($invoice);
        });

        if ($pdf === null)
        {
            return $response;
        }

        $pdfUrl = Tracer::inSpan(['name' => 'payment_page.invoice.get_signed_url'], function() use($invoice)
        {
            return (new Invoice\FileUploadUfh())->getSignedUrl($invoice);
        });

        $response['receipt_download_url'] = $pdfUrl;

        return $response;
    }

    public function sendReceipt(string $paymentId, array $input)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.receipt.send.find_payment'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
            null,
            null,
            'Receipt is not generated for this payment');
        }

        Tracer::inSpan(['name' => 'payment_page.receipt.send.validate_input'], function() use($input)
        {
            (new Validator)->validateInput('save_receipt_if_present', $input);
        });

        if(isset($input[Invoice\Entity::RECEIPT]) === true)
        {
            $receipt = $input[Invoice\Entity::RECEIPT];

            Tracer::inSpan(['name' => 'payment_page.receipt.send.set_attribute'], function() use($invoice, $receipt)
            {
                $invoice->setAttribute(Invoice\Entity::RECEIPT, $receipt);
            });

            Tracer::inSpan(['name' => 'payment_page.receipt.send.save'], function() use($invoice)
            {
                $this->repo->invoice->save($invoice);
            });
        }

        $invoiceCore = new Invoice\Core();

        $invoice->setRelation('entity', $invoice->entity);

        return Tracer::inSpan(['name' => 'payment_page.receipt.send.send_notification'], function() use($invoiceCore, $invoice)
        {
            return $invoiceCore->sendNotification($invoice, Invoice\NotifyMedium::EMAIL, true);
        });

    }

    public function saveReceiptForPaymentAndGeneratePdf(string $paymentId, array $input)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.receipt.save.find_payment'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt is not generated for this payment');
        }

        Tracer::inSpan(['name' => 'payment_page.receipt.save.validate'], function() use($input)
        {
            (new Validator)->validateInput('save_receipt', $input);
        });

        if(isset($input[Invoice\Entity::RECEIPT]) === true)
        {
            $receipt = $input[Invoice\Entity::RECEIPT];

            Tracer::inSpan(['name' => 'payment_page.receipt.save.set_attribute'], function() use($invoice, $receipt)
            {
                $invoice->setAttribute(Invoice\Entity::RECEIPT, $receipt);
            });

            Tracer::inSpan(['name' => 'payment_page.receipt.save.save'], function() use($invoice)
            {
                $this->repo->invoice->save($invoice);
            });
        }

        $invoiceCore = new Invoice\Core();

        $invoice->setRelation('entity', $invoice->entity);

        return Tracer::inSpan(['name' => 'payment_page.receipt.save.create_invoice_pdf'], function() use($invoice, $invoiceCore)
        {
            return $invoiceCore->createInvoicePdf($invoice);
        });
    }

    public function getAttributesForPaymentHandlePreview(array $input, Merchant\Entity $merchant, string $slug): array
    {
        $paymentPage = (new Entity)->generateId();

        $paymentPage->merchant()->associate($merchant);

        $settings = $input[Entity::SETTINGS] ?? [];

        $paymentPage->build($input);

        $payload = (new ViewSerializer($paymentPage))->serializeForHosted();

        $payload['is_preview'] = true;

        $payload[Entity::SETTINGS][Entity::UDF_SCHEMA] = "[{\"name\":\"comment\",\"title\":\"Comment\",\"required\":true,\"type\":\"string\",\"options\":{},\"settings\":{\"position\":1}}]";

        $payload['payment_link'][Entity::HANDLE_URL] = $this->paymentHandleHostedBaseUrl . '/' . $slug;

        $payload['payment_link'][Entity::PAYMENT_PAGE_ITEMS] = [
            Item\Entity::ITEM => [
                Item\Entity::ID        => "item_0000000000",
                Item\Entity::NAME      => "amount",
                Item\Entity::CURRENCY  => "INR",
                Item\Entity::TYPE      => "payment_page",
            ],
            PaymentPageItem\Entity::MIN_AMOUNT  => 100,
            PaymentPageItem\Entity::SETTINGS    => [
                PaymentPageItem\Entity::POSITION      => "0"
            ],
        ];
        return $payload;
    }

    public function getPaymentHandleCustomAmountEncryptionHeaders(): array
    {
        return $params = [
            AESEncryption::MODE => AES::MODE_CBC,
            AESEncryption::IV => '',
            AESEncryption::SECRET => $this->app['config']['app']['payment_handle']['secret'],
        ];
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $entity
     *
     * @return int
     */
    public function updateAndGetCapturedPaymentCount(Entity $entity): int
    {
        $capturedPaymentCount = 0;

        if ($this->repo->isTransactionActive() === false)
        {
            $this->repo->transaction(function() use ($entity, & $capturedPaymentCount) {

                $this->repo->payment_link->lockForUpdateAndReload($entity);

                $capturedPaymentCount = $this->updateAndGetCapturedPaymentCount($entity);
            });

            return $capturedPaymentCount;
        }

        $capturedPaymentCount = $this->repo->payment->getCapturedPaymentsForPaymentPage($entity);

        $this->updateCapturedPaymentCount($capturedPaymentCount, $entity);

        return $capturedPaymentCount;
    }

    /**
     * @param int                            $count
     * @param \RZP\Models\PaymentLink\Entity $entity
     * @param array                          $existingComputedSettings
     *
     * @return void
     */
    private function updateCapturedPaymentCount(int $count, Entity $entity, array $existingComputedSettings = [])
    {
        $this->repo->assertTransactionActive();

        if (empty($existingComputedSettings) === true)
        {
            $existingComputedSettings = $entity->getComputedSettings()->toArray();
        }

        $existingComputedSettings[Entity::CAPTURED_PAYMENTS_COUNT] = $count;

        $entity->getComputedSettingsAccessor()->upsert($existingComputedSettings)->save();
    }

    protected function addAdditionalDataToSettings(array & $settings, Entity $paymentLink)
    {
        $settings[Entity::CHECKOUT_OPTIONS] = [
            'email' => 'email',
            'phone' => 'phone',
        ];

        $settings[Entity::PAYMENT_BUTTON_LABEL] = 'Pay';
    }

    protected function createPaymentPageItems(array $input, Entity $paymentLink)
    {
        (new PaymentPageItem\Core)->createMany(
            $input[Entity::PAYMENT_PAGE_ITEMS],
            $this->merchant,
            $paymentLink
        );
    }

    protected function updatePaymentLinkAfterPaymentCapture(Entity $paymentLink, Payment\Entity $payment)
    {
        // Multiple payment process attempts to update attributes of link entity.
        $this->repo->assertTransactionActive();

        $paymentLink->incrementTotalAmountPaidBy($payment->getAdjustedAmountWrtCustFeeBearer());

        $order = $payment->order;

        $lineItems = $order->lineItems()->get();

        // for donation goal tracker
        $unitsSold          = 0;
        $collectedAmount    = 0;

        foreach ($lineItems as $lineItem)
        {
            $paymentPageItem = $lineItem->ref;

            $paymentPageItem->incrementQuantitySold($lineItem->getQuantity());

            $unitsSold  += $lineItem->getQuantity();

            $paymentPageItem->incrementTotalAmountPaidBy($lineItem->getQuantity() * $lineItem->getAmount());

            $collectedAmount    += $lineItem->getQuantity() * $lineItem->getAmount();

            $paymentPageItem->saveOrFail();
        }

        if (($paymentLink->isTimesPayableExhausted() === true) and
            ($paymentLink->getViewType() !== Entity::VIEW_TYPE_STORE))
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

        // update donation goal tracker dynamic keys if applicable
        $this->updateDonationGoalTrackerKeys($paymentLink, [
            Entity::SOLD_UNITS          => $unitsSold,
            Entity::COLLECTED_AMOUNT    => $collectedAmount,
            Entity::SUPPORTER_COUNT     => 1
        ]);

        // update captured payment count for the entity
        $this->updateCapturedPaymentCountOnthePage($paymentLink);

        $this->trace->count(Metric::PAYMENT_PAGE_PAID_TOTAL, $paymentLink->getMetricDimensions());
    }

    protected function updateDonationGoalTrackerKeys(Entity $paymentLink, array $items, bool $decrement = false): void
    {
        $this->repo->assertTransactionActive();

        $settings   = $paymentLink->getSettings()->toArray();

        if (empty(array_get($settings, Entity::GOAL_TRACKER.'.'.Entity::META_DATA, [])))
        {
            return;
        }

        $computedSettings   = $paymentLink->getComputedSettings()->toArray();

        $context = [
            "items"     => $items,
            "entity"    => [
                Entity::ID  => $paymentLink->getId(),
            ],
            Entity::GOAL_TRACKER            => $settings[Entity::GOAL_TRACKER][Entity::META_DATA],
            Entity::COMPUTED_GOAL_TRACKER   => $computedSettings,
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_UPDATES_START, $context);

        $multiplier = $decrement ? -1 : 1;
        $code       = $decrement
            ? TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_DECREMENT
            : TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_INCREMENT;

        $this->trace->info($code, $context);

        $metadaKey          = Entity::GOAL_TRACKER.'.'.Entity::META_DATA;
        $amountKey          = $metadaKey.'.'.Entity::COLLECTED_AMOUNT;
        $soldUnitKey        = $metadaKey.'.'.Entity::SOLD_UNITS;
        $supporterCountKey  = $metadaKey.'.'.Entity::SUPPORTER_COUNT;

        $amount         = ((int) array_get($computedSettings, $amountKey, "0")) + ($multiplier * $items[Entity::COLLECTED_AMOUNT]);
        $soldUnit       = ((int) array_get($computedSettings, $soldUnitKey, "0")) + ($multiplier * $items[Entity::SOLD_UNITS]);
        $supporterCount = ((int) array_get($computedSettings, $supporterCountKey, "0")) + ($multiplier * $items[Entity::SUPPORTER_COUNT]);

        array_set($computedSettings, $amountKey, $amount < 0 ? 0 : $amount);
        array_set($computedSettings, $soldUnitKey, $soldUnit < 0 ? 0 : $soldUnit);
        array_set($computedSettings, $supporterCountKey, $supporterCount < 0 ? 0 : $supporterCount);

        $paymentLink->getComputedSettingsAccessor()->upsert($computedSettings)->save();

        $this->trace->info(TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_UPDATES_COMPLETED, $context);
    }

    protected function createInvoiceIfEnabled(Entity $paymentLink, Payment\Entity $payment)
    {
        if($paymentLink->isReceiptEnabled() === false)
        {
            return;
        }

        $merchant = $paymentLink->merchant;

        $invoiceCreateInput = $this->getInvoiceCreateInput($paymentLink, $payment);

        $invoiceCore = (new Invoice\Core());

        $invoice = $invoiceCore->create(
            $invoiceCreateInput,
            $merchant,
            null,
            null,
            $paymentLink,
            null,
            $payment->order);

        $invoice->setStatus(Invoice\Status::PAID);

        $this->repo->save($invoice);

        $customSerialNumberEnabled = $paymentLink->isCustomSerialNumberEnabled();

        $shouldSendEmail = $customSerialNumberEnabled ? false : true;

        if ($shouldSendEmail === true)
        {
            $invoice->setRelation('entity', $invoice->entity);

            return $invoiceCore->sendNotification($invoice, Invoice\NotifyMedium::EMAIL, true);
        }

        $this->trace->count(Metric::PAYMENT_PAGE_RECEIPT_GENERATED, $paymentLink->getMetricDimensions());
    }

    public function addCustomAmountForPaymentHandleIfRequired(array & $payload, string $host, array $input)
    {
        if(isset($input[Entity::AMOUNT]) === true && $host === config('app.payment_handle_domain'))
        {
            $decryptedAmount = $this->decryptCustomAmountForPaymentHandle($input[Entity::AMOUNT]);

            if($decryptedAmount === '')
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                        Entity::VIEW_TYPE => ViewType::PAYMENT_HANDLE
                    ],
                    'Amount not valid.');
            }

            $payload['data'][Entity::PAYMENT_HANDLE_AMOUNT] = $decryptedAmount;
        }
    }

    public function encryptAmountForPaymentHandle(array $input): array
    {
        $params = $this->getPaymentHandleCustomAmountEncryptionHeaders();

        $encryptedAmount = (new AESEncryption($params))->encrypt($input[Entity::AMOUNT]);

        $encryptedAmount = base64_encode($encryptedAmount);

        $encryptedAmount = urlencode($encryptedAmount);

        return [Entity::ENCRYPTED_AMOUNT => $encryptedAmount];
    }

    public function decryptCustomAmountForPaymentHandle(string $encryptedAmount)
    {
        $params = $this->getPaymentHandleCustomAmountEncryptionHeaders();

        $decryptedAmount = urldecode($encryptedAmount);

        $decryptedAmount = base64_decode($decryptedAmount);

        $decryptedAmount = (new AESEncryption($params))->decrypt($decryptedAmount);

        return $decryptedAmount;
    }

    protected function getInvoiceCreateInput(Entity $paymentLink, Payment\Entity $payment): array
    {
        $type = Invoice\Type::INVOICE;

        $order = $payment->order;

        $customer = [
            Customer\Entity::CONTACT   => $payment->getContact(),
            Customer\Entity::EMAIL     => $payment->getEmail()
        ];

        $comment = Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)
            ->get(Entity::PAYMENT_SUCCESS_MESSAGE);

        $lineItems = $this->getLineItemsInput($order);

        $terms = $paymentLink->getAttribute(Entity::TERMS);

        $customSerialNumberEnabled = $paymentLink->isCustomSerialNumberEnabled();

        $receipt = $customSerialNumberEnabled ? null : $payment->getPublicId();

        $input = [
            IE::TYPE                => $type,
            IE::EMAIL_NOTIFY        => 0,
            IE::SMS_NOTIFY          => 0,
            IE::CUSTOMER            => $customer,
            IE::LINE_ITEMS          => $lineItems,
            IE::COMMENT             => is_string($comment) ? $comment : null,
            IE::TERMS               => $terms,
            IE::RECEIPT             => $receipt,
            IE::REMINDER_ENABLE     => false,
            IE::CURRENCY            => $paymentLink->getCurrency() ?? 'INR',
        ];

        $input = array_filter(
            $input,
            function ($value) {
                return $value !== null;
            }
        );

        return $input;
    }

    protected function getLineItemsInput(Order\Entity $order)
    {
        $invoiceLineItems = [];

        $lineItems = $order->lineItems()->get()->all();

        foreach ($lineItems as $lineItem)
        {
            $invoiceLineItem = [
                LineItem\Entity::NAME           => $lineItem->getName(),
                LineItem\Entity::DESCRIPTION    => $lineItem->getDescription(),
                LineItem\Entity::AMOUNT         => $lineItem->getAmount(),
                LineItem\Entity::CURRENCY       => $lineItem->getCurrency(),
                LineItem\Entity::QUANTITY       => $lineItem->getQuantity()
            ];
            array_push($invoiceLineItems, $invoiceLineItem);
        }

        return $invoiceLineItems;
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
     * @param  Entity       $paymentLink
     * @param  Order\Entity $order
     *
     * @return boolean
     */
    protected function hasPaymentSlots(Entity $paymentLink, Order\Entity $order): bool
    {
        $lineItems = $order->lineItems()->get();

        $shouldCheckForPayments = $this->shouldCheckForPayments($lineItems);

        if ($shouldCheckForPayments === false)
        {
            return true;
        }

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_VALIDATE_EXISTING_PAYMENTS,
            [
                'id'     => $paymentLink->getId(),
            ]);

        $succeedingPayments = $this->repo->payment->getValidatePaymentsForPaymentPages($paymentLink);

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

    protected function shouldCheckForPayments(Base\Collection $lineItems): bool
    {
        foreach ($lineItems as $lineItem)
        {
            $paymentPageItem = $lineItem->ref;

            if (is_null($paymentPageItem->getStock()) === false)
            {
                return true;
            }
        }

        return false;
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

        switch ($paymentLink->getViewType())
        {
            case ViewType::PAYMENT_HANDLE:

                $hostedBaseUrl = $this->paymentHandleHostedBaseUrl;

                break;

            default:

                $hostedBaseUrl = $this->plHostedBaseUrl;

                break;
        }

        $merchant = $paymentLink->merchant;

        $org = $merchant->org;

        switch ($org->getCustomCode())
        {
            case 'axis':

                $hostedBaseUrl = $this->app['config']->get('app.payment_page_axis_hosted_base_url');

                break;
        }

        $url = $paymentLink->getHostedViewUrl($hostedBaseUrl, $slug);

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

        $paymentLinks = Tracer::inSpan(['name' => 'payment_page.expire.get_active_and_past_expire_by_payment_link'], function()
        {
            return $this->repo->payment_link->getActiveAndPastExpireByPaymentLinks();
        });

        $summary = [
            'total_count' => $paymentLinks->count(),
            'failed_ids'  => [],
        ];

        foreach ($paymentLinks as $paymentLink)
        {
            try
            {
                Tracer::inSpan(['name' => 'payment_page.expire.payment_link.core'], function() use($paymentLink)
                {
                    $this->expirePaymentLink($paymentLink);
                });
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
        $payload['data'] = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SERIALIZE], function() use ($paymentLink) {
            return $this->getSerializedFromCache($paymentLink);
        });

        // Append UDF Schema as a JSON string, if defined
        $payload[Entity::UDF_SCHEMA] = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SCHEMA], function() use ($paymentLink) {
            return (new UdfSchema($paymentLink))->getSchema();
        });

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
            switch ($paymentLink->getViewType())
            {
                case ViewType::PAYMENT_HANDLE:

                        $view = 'payment_handle.hosted_with_udf';

                        break;

                default:

                    $view = 'payment_link.hosted_with_udf';

                    break;
            }
        }

        return $view;
    }

    /**
     * Returns settings required to load button on client side
     * Has to be cached in redis after mvp
     * @param  Entity $paymentLink
     *
     * @return array
     */
    public function getHostedButtonPreferences(Entity $paymentLink)
    {
        $settings =  Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)->all();

        $merchant = $paymentLink->merchant;

        $merchantBrandColor = get_rgb_value($merchant->getBrandColorOrDefault());

        $preferences = array_intersect_key($settings->toArray(), array_flip(Entity::BUTTON_PREFERENCES_KEYS));

        $preferences['merchant_brand_color'] = $merchantBrandColor;

        $preferences += $this->serializeOrgPropertiesForPreferences($merchant);

        return [
            'is_test_mode'   => $this->isTestMode(),
            'preferences'    => $preferences,
        ];
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

            $ufhService = $this->app['ufh.service'];

            $file = $ufhService->uploadFileAndGetUrl(
                $image,
                $uploadFilename,
                Constants::PAYMENT_LINK_DESCRIPTION,
                $merchant,
                ['Content-Disposition' => 'inline']);

            $urls[] = $cdn . '/' . $file[Constants::RELATIVE_LOCATION];
        }

        return $urls;
    }

    public function updatePaymentPageItem(PaymentPageItem\Entity $paymentPageItem, array $input)
    {
        $paymentPageItem = Tracer::inSpan(['name' => Constants::HT_PPI_TRANSACTION], function() use ($paymentPageItem, $input)
        {
            return $this->repo->transaction(function () use ($paymentPageItem, $input) {
                $paymentLink = $paymentPageItem->paymentLink;

                Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_LOCK], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                $this->repo->payment_page_item->reload($paymentPageItem);

                $paymentPageItem = Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_CORE], function() use($paymentPageItem, $input)
                {
                    return (new PaymentPageItem\Core)->update($paymentPageItem, $input);
                });

                Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_STATUS], function() use($paymentLink)
                {
                    $this->changeStatusAfterUpdateIfApplicable($paymentLink);
                });

                Tracer::inSpan(['name' =>  Constants::HT_PPI_UPDATE_SAVE], function() use($paymentLink) {
                    $paymentLink->saveOrFail();
                });

                return $paymentPageItem;
            });
        });

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentPageItem) {
            $this->dispatchHostedCache($paymentPageItem->paymentLink);
        });

        return $paymentPageItem;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    protected function updateCapturedPaymentCountOnthePage(Entity $paymentLink)
    {
        $computedSettings   = $paymentLink->getComputedSettings()->toArray();

        $context = [
            "entity"    => [
                Entity::ID  => $paymentLink->getId(),
            ],
            Entity::COMPUTED_SETTINGS   => $computedSettings,
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATING_TRANSACTION_COUNT, $context);

        $totalTransactionCountSoFar = array_get($computedSettings, Entity::CAPTURED_PAYMENTS_COUNT);

        if ($totalTransactionCountSoFar === null)
        {
            /**
             * captured payment count has never been computed for this entity
             * we will update the count by making a query and update it,
             * so that from next time we will simply increment the count
             *
             * purpose of calling $this->repo->payment->getCapturedPaymentsForPaymentPage is
             * we will not have the captured_payment_count for old payment pages.
             */
            $this->updateAndGetCapturedPaymentCount($paymentLink);

            return;
        }

        $this->updateCapturedPaymentCount(1 + (int) $totalTransactionCountSoFar, $paymentLink, $computedSettings);
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

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        $this->traceForExpire($paymentLink);
    }

    /**
     * Tracing for payment links expire action
     * Traces count as well as histogram
     *
     * @param Entity         $paymentLink
     */
    protected function traceForExpire(Entity $paymentLink)
    {
        $now = Carbon::now(Timezone::IST)->timestamp;

        $diffInTime = $now - $paymentLink->getExpireBy();

        $this->trace->histogram(Metric::PAYMENT_PAGE_EXPIRED_SEC, $diffInTime, $paymentLink->getMetricDimensions());

        $this->trace->count(Metric::PAYMENT_PAGE_EXPIRED_TOTAL, $paymentLink->getMetricDimensions());
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
                // based on experiment, refund request will be routed to Scrooge
                $refund = $processor->refundAuthorizedPayment($payment->reload());
            }
            else if ($payment->isCaptured() === true)
            {
                // based on experiment, refund request will be routed to Scrooge
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
            $dimensions = ['payment_status' => $payment->getStatus()] + $paymentLink->getMetricDimensions();

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

        $modifiedInput[Order\Entity::NOTES] = $input[Order\Entity::NOTES] ?? [];

        return $modifiedInput;
    }

    protected function serializeOrgPropertiesForPreferences(Merchant\Entity $merchant)
    {
        $org = $merchant->org;

        $branding = [
            'show_rzp_logo' => true,
            'branding_logo' => '',
        ];

        if($merchant->shouldShowCustomOrgBranding() === true)
        {
            $branding['show_rzp_logo'] = false;

            $branding['branding_logo'] = $org->getPaymentAppLogo() ?: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.png';

        }

        return [
            'branding'  => $branding
        ];
    }

    protected function doPostPaymentRiskActions(Entity $paymentLink, Payment\Entity $payment)
    {
        try
        {
            if ($this->mode === Mode::TEST)
            {
                return;
            }

            $riskAovInput = $this->getAovRiskCallInput($paymentLink, $payment);

            NotifyRas::dispatch($this->mode, $riskAovInput);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, null, ['payment_page_id' => $paymentLink->getId()]);
        }
    }

    public function doDedupeAndRiskActions(Entity $paymentLink)
    {
        try
        {
            if ($this->mode !== Mode::LIVE)
            {
                return;
            }

            $riskCheckInput = $this->getRiskCheckInput($paymentLink);

            $riskCheckOutput = Tracer::inSpan(
                ['name' => 'payment_page.create.dedupe_actions.validate.risk_factor.request'],
                function() use ($riskCheckInput) {
                return $this->merchantRiskService->validateRiskFactorForMerchantRequest($riskCheckInput);
            });
            $alertInput = Tracer::inSpan(
                ['name' => 'payment_page.create.dedupe_actions.validate.risk_factor.response'],
                function () use ($riskCheckOutput, $paymentLink) {
                return $this->validateRiskFactorResponseAndGetAlertInput($riskCheckOutput, $paymentLink);
            });

            if (empty($alertInput) === true)
            {
                return;
            }

            $this->trace->count(Metric::PAYMENT_PAGE_RISK_ALERT_COUNT, $paymentLink->getMetricDimensions());

            NotifyRas::dispatch($this->mode, $alertInput);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, null, ['payment_page_id' => $paymentLink->getId()]);
        }
    }

    protected function getRiskCheckInput(Entity $paymentLink)
    {
        $riskInput = [
            'entity_type'   => 'payment_pages',
            'client_type'   => 'payment_pages',
            'entity_id'     => $paymentLink->getId(),
            'merchant_id'   => $paymentLink->getMerchantId(),
            'fields'        => [
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'authorities_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'brand_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'authorities_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'brand_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'terms',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'authorities_list',
                    'config_key' => 'terms',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'brand_list',
                    'config_key' => 'terms',
                ]
            ]
        ];

        return $riskInput;
    }

    protected function validateRiskFactorResponseAndGetAlertInput(array $response, Entity $paymentLink)
    {
        $riskFactorFields = (array_key_exists('fields', $response) === true) ? $response['fields'] : [];

        $dataFields = [];

        foreach ($riskFactorFields as $riskFactorField)
        {
            if ($riskFactorField['score'] > 650)
            {
                $matchedField = $riskFactorField['config_key'];

                switch ($matchedField)
                {
                    case Entity::DESCRIPTION:

                        $dataFields[Entity::DESCRIPTION] = $paymentLink->getMetaDescription();

                        break;

                    case Entity::TITLE:

                        $dataFields[Entity::TITLE] = $paymentLink->getTitle();

                        break;

                    case Entity::TERMS:

                        $dataFields[Entity::TERMS] = $paymentLink->getTerms();

                        break;
                }
            }
        }

        if (empty($dataFields) === true)
        {
            return [];
        }

        $isManaged = false;

        if (isset($response['is_managed']) === true)
        {
            $isManaged = $response['is_managed'];
        }

        return $this->getAlertServiceInput($paymentLink, $dataFields, $isManaged);
    }

    protected function getAlertServiceInput(Entity $paymentLink, array $dataFields, bool $isManaged)
    {
        $dataFields['merchant_type'] = $isManaged === true ? 'managed' : 'unmanaged';

        return [
            'merchant_id'     => $paymentLink->merchant->getMerchantId(),
            'entity_type'     => 'payment_page',
            'entity_id'       => $paymentLink->getId(),
            'category'        => 'high_risk_keywords',
            'source'          => 'pp_service',
            'data'            => $dataFields,
            'event_timestamp' => $paymentLink->getCreatedAt(),
            'event_type'      => 'create',
        ];
    }

    protected function getAovRiskCallInput(Entity $paymentLink, Payment\Entity $payment)
    {
        return [
            'merchant_id'     => $paymentLink->merchant->getMerchantId(),
            'entity_type'     => 'payment_page',
            'entity_id'       => $paymentLink->getId(),
            'category'        => 'transaction',
            'source'          => 'pp_service',
            'data'            => [
                'payment_created_at' => (string) $payment->getCreatedAt(),
                'base_amount'        => (string) $payment->getAmount(),
            ],
            'event_timestamp' => (string) $payment->getCapturedAt(),
            'event_type'      => 'captured',
        ];
    }

    public function getGrievanceEntityDetails(string $id)
    {
        $id = Entity::stripDefaultSign($id);

        $paymentPage = $this->repo->payment_link->findOrFailPublic($id);

        $merchant = $paymentPage->merchant;

        return [
            'entity'         => 'payment_page',
            'entity_id'      => $paymentPage->getPublicId(),
            'merchant_id'    => $paymentPage->merchant->getId(),
            'merchant_label' => $merchant->getBillingLabel(),
            'merchant_logo'  => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'subject'        => $paymentPage->getTitle(),
        ];
    }

    protected function eventPaymentPagePaid(Entity $paymentPage, Payment\Entity $payment)
    {
        $this->firePartnerWebhooksIfNeeded($paymentPage, $payment);
    }

    protected function firePartnerWebhooksIfNeeded(Entity $paymentPage, Payment\Entity $payment)
    {
        // We are sending webhooks only for payment pages now.
        if ($paymentPage->getViewType() !== ViewType::PAGE)
        {
            return;
        }

        $partnerWebhookSettings = $paymentPage->getEnabledPartnerWebhooks();

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        foreach ($partnerWebhookSettings as $partnerName => $partnerWebhookEnabled)
        {
            $partnerWebhookEvent = Entity::PARTNER_WEBHOOKS[$partnerName] ?? null;

            //In case the partner name is saved wrong in the settings sent via dashboard
            if (($partnerWebhookEvent === null) || ($partnerWebhookEnabled !== "1"))
            {
                continue;
            }

            $this->trace->info(TraceCode::PAYMENT_PAGE_FIRE_WEBHOOK, [$partnerWebhookEvent]);

            $this->app['events']->fire('api.'.$partnerWebhookEvent, $eventPayload);
        }
    }

    /**
     * Constructs webhook payload for zapier integration
     *
     * @param Payment\Entity $payment
     * @return array
     */
    public function constructPayloadForPartnerWebhook(Payment\Entity $payment): array
    {
        $payload = [];

        $payload[E::PAYMENT] = $payment->toArrayPublic();

        $paymentPage = $payment->paymentLink;

        if ($paymentPage === null)
        {
            return $payload;
        }

        $payload[E::PAYMENT_PAGE] = $paymentPage->toArrayPublic();

        $order = $payment->order;

        $payload[E::ORDER] = $order->toArrayPublic();

        $lineItems = $order->lineItems;

        if ($lineItems !== null)
        {
            $payload[E::ORDER]['items'] = $lineItems->toArrayPublic()['items'];
        }

        $this->trace->info(TraceCode::PAYMENT_PAGE_FIRE_WEBHOOK, $payload);

        return $payload;
    }

    protected function getRefundContext(Payment\Refund\Entity $refund): array
    {
        return [
            'refund_id'         => $refund->getId(),
            'refund_status'     => $refund->getStatus(),
            "refund"            => $refund->toArrayPublic(),
            'payment_id'        => $refund->payment->getId(),
            'payment_status'    => $refund->payment->getStatus(),
        ];
    }

    private function dispatchAppRiskCheck(Entity $paymentLink)
    {
        $mode = $this->app['basicauth']->getMode() ?? Mode::LIVE;

        if ($mode !== Mode::LIVE)
        {
            return;
        }

        $request = [
            'entity_id'         => $paymentLink->getId(),
            'checks'            => ['profanity_check'],
            'payment_page_id'   => $paymentLink->getPublicId(),
            'merchant_id'       => $paymentLink->getMerchantId(),
        ];
        try {
            $this->trace->info(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_INIT,
                $request
            );
            AppsRiskCheck::dispatch($this->mode, $request);
        } catch (\Exception $e) {
            $this->trace->critical(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    public function upsertDefaultPaymentHandleForMerchant(string $slug, string $handlePageId = null): void
    {
        $input[Entity::DEFAULT_PAYMENT_HANDLE] = $slug;

        if($handlePageId !== null)
        {
            $input[Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID] = $handlePageId;
        }

        Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
            ->upsert([
                Entity::DEFAULT_PAYMENT_HANDLE => $input
            ])
            ->save();
    }

    public function suggestionPaymentHandle($count)
    {
        $merchant = $this->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $merchantBillingLabel = preg_replace('/[^a-zA-Z0-9-]+/', '', $merchantBillingLabel);

        // removes spaces
        $merchantBillingLabel = '@' . strtolower(str_replace(' ', '', $merchantBillingLabel));

        if(strlen($merchantBillingLabel) > Entity::MAX_SLUG_LENGTH)
        {
            $merchantBillingLabel = substr($merchantBillingLabel, 0, Entity::MAX_SLUG_LENGTH);
        }

        $suggestions = [];

        if($this->slugExists($merchantBillingLabel) === false)
        {
            array_push($suggestions, $merchantBillingLabel);

            $count--;
        }

        $this->generatePaymentHandle($merchantBillingLabel, $count, $suggestions);

        return $suggestions;
    }

    protected function generatePaymentHandle(string $handle, $count, & $suggestions)
    {
        while($count > 0) {

            $randInteger = rand(1, 10000);

            $suggestedHandle = $handle . $randInteger;

            if(strlen($suggestedHandle) > Entity::MAX_SLUG_LENGTH)
            {
                $suggestedHandle = substr($handle, 0, Entity::MAX_SLUG_LENGTH - strlen((string)$randInteger))
                    . $randInteger;
            }

            if ($this->slugExists($suggestedHandle) === false) {

                array_push($suggestions, $suggestedHandle);

                $count--;
            }
        }
        return $suggestions;
    }

    public function slugExists(string $slug)
    {
        $gimli  = $this->app['elfin']->driver('gimli');

        return ($gimli->expand($slug) !== null);
    }

    public function getPlIdFromSlug(string $slug)
    {
            $gimli        = $this->app['elfin']->driver('gimli');

            $slugMetadata = $gimli->expandAndGetMetadata($slug);

            // Renders 404 if no metadata available(error/exception at Gimli side)
            if ($slugMetadata === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
            }

            $this->app['basicauth']->setModeAndDbConnection($slugMetadata['mode']);

            return $slugMetadata['id'];
    }

    private function dispatchDedupeCall(Entity $paymentLink, Merchant\Entity $merchant)
    {
        if ($this->mode !== Mode::LIVE)
        {
            return;
        }

        $request = [
            'event'             => PaymentPageProcessor::PAYMENT_PAGE_CREATE_DEDUPE,
            'payment_page_id'   => $paymentLink->getId(),
            'start_time'        => millitime(),
        ];

        try {
            $this->trace->info(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSH_INIT,
                $request
            );

            PaymentPageProcessor::dispatch($this->mode, $request);

            $this->trace->info(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSHED,
                $request
            );
        } catch (\Exception $e) {
            $this->trace->critical(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    public function precreatePaymentHandle(Merchant\Entity $merchant): array
    {
        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PRECREATE_STARTED,
            [
                Entity::MERCHANT_ID => $merchant->getId()
            ]);
        // get unique handle
        $handle = $this->suggestionPaymentHandle(1);

        $handle = $handle[0];

        $url = $this->paymentHandleHostedBaseUrl . '/' . $handle;

        $this->createGimliEntryForHandle($handle, $merchant->getPublicId());

        // upsert handle in merchant setting
        $this->upsertDefaultPaymentHandleForMerchant($handle);

        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PRECREATE_COMPLETED,
            [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::TITLE          => $merchant->getBillingLabel(),
                Entity::URL            => $url,
                Entity::SLUG           => $handle
            ]);

        return [
            Entity::TITLE          => $merchant->getBillingLabel(),
            Entity::URL            => $url,
            Entity::SLUG           => $handle
        ];
    }

    protected function createGimliEntryForHandle(string $handle, string $merchantId,string $handlePageId = null, int $retryTotal = 3)
    {
        // Fail: If failed to shorten the URL, do not continue with creation and fail
        $fail = true;

        // No fall back: Only use Gimli(our shortener service) and do not fall back to Bitly etc if that fails
        $this->elfin->setNoFallback();

        $params = [
            'ptype'          => 'link',
            'alias'          => $handle,
            'fail_if_exists' => true,
            'metadata'       => [
                'mode'          => $this->mode,
                'entity'        => E::PAYMENT_LINK,
                'view_type'     => ViewType::PAYMENT_HANDLE,
                'merchant_id'   => $merchantId,
            ],
        ];

        // Gimli Metadata will not have payment page id if the handle is in precreated state
        if($handlePageId !== null)
        {
            $params['metadata'][Entity::ID] = $handlePageId;
        }

        $url = $this->paymentHandleHostedBaseUrl . '/' . $handle;

        $sleepTime = 0;

        $retry = $retryTotal;

        while($retry > 0)
        {
            try
            {
                $shortUrl = $this->elfin->shorten($url, $params, $fail);

                if($shortUrl !== "")
                {
                    $this->trace->count(Metric::PAYMENT_HANDLE_SHORTENING_SUCCESSFUL_COUNT, [
                        'slug'    => $handle,
                        'retries' => $retryTotal - $retry
                    ]);

                    $this->trace->info(TraceCode::PAYMENT_HANDLE_GIMLI_MAPPING_CREATION_SUCCESSFUL, [
                        'slug'    => $handle,
                        'retries' => $retryTotal - $retry
                    ]);

                    return;
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATE_GIMLI_MAPPING_RETRY,[
                    "slug"   => $handle,
                    "retry"  => $retryTotal - $retry,
                    "error"  => $e->getMessage()
                ]);
            }

            $retry = $retry - 1;

            sleep($sleepTime);

            $sleepTime = $sleepTime + 1;
        }

        $this->trace->count(Metric::PAYMENT_HANDLE_SHORTENING_UNSUCCESSFUL_COUNT);

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ViewType::PAYMENT_HANDLE,
            [
                Entity::SLUG => $handle,
            ]
        );
    }

    protected function createPaymentPageForPaymentHandle(array $input, Merchant\Entity $merchant)
    {
        $paymentPage = (new Entity)->generateId();

        $paymentPage->merchant()->associate($merchant);

        //TODO: not sure if we need settings
        $settings = $input[Entity::SETTINGS] ?? [];

        $settings[Entity::VERSION] = Version::V2;

        $paymentPage->build($input);

        $paymentPage->setShortUrl($this->paymentHandleHostedBaseUrl . '/' . $input[Entity::SLUG]);

        // instead of creating a new short url, we will update the existing gimli mapping
        // which was created in pre-create step in case of payment handle
        $this->updateGimliMappingForHandle($input[Entity::SLUG], $paymentPage->getPublicId());

        $this->repo->transaction(function() use ($paymentPage, $settings, $input)
        {
            $this->upsertSettings($paymentPage, $settings);

            $this->repo->saveOrFail($paymentPage);

            $this->createPaymentPageItems($input, $paymentPage);
        });

        $this->trackPaymentPageCreatedEvent($paymentPage, $input);

        return $paymentPage;
    }

    protected function updateGimliMappingForHandle(string $handle, string $paymentPageId)
    {
        $gimli        = $this->app['elfin']->driver('gimli');

        $newMetadata = [
            'mode'          => $this->mode,
            'entity'        => E::PAYMENT_LINK,
            'view_type'     => ViewType::PAYMENT_HANDLE,
            'merchant_id'   => $this->merchant->getId(),
            Entity::ID      => $paymentPageId,
        ];

        $input = json_encode(['metadata' => $newMetadata]);

        try
        {
            $gimli->update($handle, $input);
        }
        catch (\Throwable $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                Entity::SLUG,
                [
                    Entity::SLUG         => $handle,
                    Entity::ERROR        => $e->getMessage(),
                    Entity::MERCHANT_ID  => $this->merchant->getId()
                ]);
        }
    }

    /**
     * @return string
     */
    public function getHandleFromTestMode(): string
    {
        // As when we hit the precreate api, we might be in test mode, therefore we are checking
        // test mode if we have precreated handle.
        // Precreate handle entry in merchant settings will be made in live mode when we are
        // upserting the merchant setting in Payment Handle creation flow

        $prevMode = $this->mode;

        $this->app['basicauth']->setModeAndDbConnection('test');

        $merchantSetting = Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        $handle = array_get($merchantSetting, ENTITY::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE);

        $this->app['basicauth']->setModeAndDbConnection($prevMode);

        return $handle === null ? '' : $handle;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    public function updateHostedCache(Entity $paymentLink)
    {
        Entity::clearHostedCacheForPageId($paymentLink->getPublicId());

        $this->getSerializedFromCache($paymentLink);
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return array
     */
    private function getSerializedFromCache(Entity $paymentLink): array
    {
        $serializer = new ViewSerializer($paymentLink);

        if (! $this->shouldCacheHostedResponse($paymentLink))
        {
            return $serializer->serializeForHosted();
        }

        $cacheKey = Entity::getHostedCacheKey($paymentLink->getPublicId());

        $cached = $this
            ->cache
            ->remember($cacheKey, Entity::getHostedCacheTTL(), function () use ($serializer) {
                return $serializer->serializeForHosted();
            });

        return $serializer->updateKeyLessHeader($cached);
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    private function dispatchHostedCache(Entity $paymentLink)
    {
        if (! $this->shouldCacheHostedResponse($paymentLink))
        {
            return;
        }

        $request = [
            'event'             => PaymentPageProcessor::PAYMENT_PAGE_HOSTED_CACHE,
            'payment_page_id'   => $paymentLink->getId(),
            'start_time'        => millitime(),
        ];

        try {
            $this->trace->info(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSH_INIT,
                $request
            );

            PaymentPageProcessor::dispatch($this->mode, $request);

            $this->trace->info(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSHED,
                $request
            );
        } catch (\Exception $e) {
            $this->trace->error(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return bool
     */
    private function shouldCacheHostedResponse(Entity $paymentLink): bool
    {
        return $paymentLink->getViewType() === ViewType::PAGE;
    }
}
