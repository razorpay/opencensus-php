<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    /**
     * Elfin: Url shortener service
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
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        $paymentLink = (new Entity)->build($input);

        $paymentLink->merchant()->associate($merchant);

        $paymentLink->generateId();

        $this->setShortUrl($paymentLink);

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

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());

        return $paymentLink;
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
     *
     * @return array
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
     * Validates if new payment initiation should be allowed or not
     * @param  Entity $paymentLink
     * @throws BadRequestException
     */
    public function validateIsPaymentInitiable(Entity $paymentLink)
    {
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
     * @param Payment\Entity $payment
     */
    public function postPaymentCaptureAttemptProcessing(Payment\Entity $payment)
    {
        assertTrue($payment->hasPaymentLink());

        $paymentLink = $payment->paymentLink;

        $this->trace->info(
            TraceCode::PAYMENT_LINK_POST_PAYMENT_CAPTURE_ATTEMPT,
            [
                'payment_id' => $payment->getId(),
                'payment_status' => $payment->getStatus(),
                'payment_link' => $paymentLink->toArrayPublic(),
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
     * This method sets the short_url of a paymentLink
     * @param Entity $paymentLink
     */
    protected function setShortUrl(Entity $paymentLink)
    {
        $url = $paymentLink->getHostedViewUrl($this->plHostedBaseUrl);
        $shortUrl = $this->elfin->shorten($url);

        $paymentLink->setShortUrl($shortUrl);
    }

    /**
     * Called from CRON.
     * Updates status to INACTIVE, status_reason to EXPIRED of all payment links which are active and past expire_by.
     *
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
     * Updates the status to INACTIVE, status_reason to EXPIRED of an individual expired payment link by locking it.
     * @param Entity $paymentLink
     */
    protected function expirePaymentLink(Entity $paymentLink)
    {
        $this->repo->transaction(
            function () use ($paymentLink)
            {
                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                if ($paymentLink->isActive() === true)
                {
                    return;
                }

                // TODO: Use Core's method to do status change. That method is being added in another PR.
                $paymentLink->setStatus(Status::INACTIVE);
                $paymentLink->setStatusReason(StatusReason::EXPIRED);

                $this->repo->saveOrFail($paymentLink);
            });
    }

    /**
     * Initiates refund on a payment. This happens in cases as described in postPaymentCaptureAttemptProcessing() method
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
            if ($payment->isAuthorized() === true)
            {
                $refund = $processor->refundAuthorizedPayment($payment);
            }
            else if ($payment->isCaptured() === true)
            {
                $refund = $processor->refundCapturedPayment($payment);
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
