<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\Template\UdfSchema;
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
     */
    public function create(array $input, Merchant\Entity $merchant, User\Entity $user = null): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        $paymentLink = (new Entity)->build($input);

        $paymentLink->merchant()->associate($merchant);
        $paymentLink->user()->associate($user);

        $paymentLink->generateId();

        $this->createAndSetShortUrl($paymentLink, $input[Entity::SLUG] ?? null);

        $this->repo->saveOrFail($paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * @param  Entity $paymentLink
     * @param  array  $input
     *
     * @return Entity
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

            $paymentLink->edit($input);

            $this->changeStatusAfterUpdateIfApplicable($paymentLink);

            $this->repo->saveOrFail($paymentLink);
        });

        $this->updateShortUrlIfApplicable($paymentLink, $input);

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * Attempts recreating short URL for payment link in case of new slug in patch input
     * @param Entity $paymentLink
     * @param array  $input
     */
    public function updateShortUrlIfApplicable(Entity $paymentLink, array $input)
    {
        if (($slug = $input[Entity::SLUG] ?? null) !== null)
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
        // 1. Validates amount, if applicable
        $paymentLink->getValidator()->validatePaymentAmount($payment);

        // 2. Validates Payment notes (UDF values), if applicable
        $udfJsonschemaId = $paymentLink->getUdfJsonschemaId();

        if ($udfJsonschemaId !== null)
        {
            $udfSchema = new Template\UdfSchema($udfJsonschemaId);
            $schema    = $udfSchema->getSchemaDecoded();

            if ($schema !== null)
            {
                $paymentNotes = $payment->getNotes()->toArray();

                $udfSchema->validate($paymentNotes);
            }
        }

        // 3. Validates payment link is active and has payment slots available
        if (($paymentLink->isPayable() === false) or
            ($this->hasPaymentSlots($paymentLink) === false))
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

    protected function updatePaymentLinkAfterPaymentCapture(Entity $paymentLink, Payment\Entity $payment)
    {
        //
        // Caller of this function must be wrapped in a database txn because we are updating entity's attributes &
        // status which are shared in multiple payment process & entity operation in parallel.
        //
        $this->repo->assertTransactionActive();

        $paymentLink->incrementTimesPaid();
        $paymentLink->incrementTotalAmountPaidBy($payment->getAmount());

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
    }

    /**
     * This is called after edit/update operation. Post building the entity with request input we check if payment
     * link's status needs changing.
     *
     * Payment link's status:
     * - will be marked complete if times_payable post update is equal to times_paid
     *
     * Currently there is no other cases. Expire by edits will not affect this because that must already by at least
     * 15 mins in future (validated via Validator method during build).
     *
     * @param Entity $paymentLink
     */
    protected function changeStatusAfterUpdateIfApplicable(Entity $paymentLink)
    {
        $this->repo->assertTransactionActive();

        if ($paymentLink->getTimesPayable() === $paymentLink->getTimesPaid())
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

    /**
     * Given payment link is payable(i.e. active and not expired etc), checks if a new payment can be accepted by
     * counting existing succeeding payments (i.e. payments in created/authorized statuses).
     *
     * @param  Entity  $paymentLink
     * @return boolean
     */
    protected function hasPaymentSlots(Entity $paymentLink): bool
    {
        $timesPaid    = $paymentLink->getTimesPaid();
        $timesPayable = $paymentLink->getTimesPayable();

        // Just return if there is no limit on number of payments
        if ($timesPayable === null)
        {
            return true;
        }

        $succeedingPaymentsCount = $this->repo->payment_link->getSucceedingPaymentsCount($paymentLink);

        $slotsAvailable = $timesPayable - $timesPaid - $succeedingPaymentsCount;

        return ($slotsAvailable > 0);
    }

    /**
     * @param Entity      $paymentLink
     * @param string|null $slug
     */
    protected function createAndSetShortUrl(Entity $paymentLink, string $slug = null)
    {
        list($url, $params, $fail) = $this->getShortenUrlRequestParams($paymentLink, $slug);

        try
        {
            $shortUrl = $this->elfin->shorten($url, $params, $fail);

            $paymentLink->setShortUrl($shortUrl);
        }
        catch (\RZP\Exception\BaseException $e)
        {
            // TODO: Gimli should return 4xx & Elfin service should propagate that error to callee
            if (str_contains($e->getDataAsString(), 'Duplicate') === true)
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
        // URL: https://pages.razorpay.in/pl_10000000000000/view OR https://pages.razorpay.in/AlphaNumMin4Max20Slug
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
     * Returns an array of the payload to be consumed by the
     * Payment link view template
     *
     * @param Entity $paymentLink
     *
     * @return array
     */
    public function getHostedViewPayload(Entity $paymentLink): array
    {
        // Fetch serialized view data for the view to consume
        $payload['data'] = (new ViewSerializer($paymentLink))->serializeForHosted();

        // Append UDF Schema as a JSON string, if defined
        $payload['udf_schema'] = $this->getUdfSchemaIfDefined($paymentLink);

        return $payload;
    }

    /**
     * Returns the name of the Payment link view template to be used
     *
     * @param Entity $paymentLink
     *
     * @return string
     */
    public function getHostedViewTemplate(Entity $paymentLink): string
    {
        $templateId = $paymentLink->getHostedTemplateId();

        // Default view name
        $defaultView = 'payment_link.hosted';

        //
        // If hosted_template_id is not sent for the Payment link,
        // use the default view
        //
        if ($templateId === null)
        {
            return $defaultView;
        }

        $templateAccessor = new HostedTemplate($templateId);

        // If a custom hosted page template exists, use that
        if ($templateAccessor->exists() === true)
        {
            $hostedPageHint = 'hostedpage.';
            return $hostedPageHint . $templateAccessor->getViewName();
        }

        // else fallback to the default hosted view
        return $defaultView;
    }

    /**
     * @param Entity $paymentLink
     *
     * @return null|string
     */
    protected function getUdfSchemaIfDefined(Entity $paymentLink)
    {
        $jsonSchemaId = $paymentLink->getUdfJsonschemaId();

        if ($jsonSchemaId === null)
        {
            return null;
        }

        $schemaAccessor = new UdfSchema($jsonSchemaId);

        return $schemaAccessor->getSchema();
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
                $refund = null;
                $this->trace->critical(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::CRITICAL, TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
        }

        $tracePayload = array_merge($tracePayload, [E::REFUND => optional($refund)->toArrayPublic()]);
        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_HANDLED, $tracePayload);
    }
}
