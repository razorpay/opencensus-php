<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Jobs\PaymentLink\RefundPayment as RefundPaymentJob;

class Core extends Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;

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

        $this->mutex           = $this->app['api.mutex'];
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

        // TODO : TO change to lockForUpdate(). Has been done in subsequent PR already.
        $paymentLink = $this->mutex->acquireAndRelease(
                            $paymentLink->getId(),
                            function() use ($paymentLink, $input)
                            {
                                $paymentLink->reload();

                                // TODO: Cases related to expire_by and times_payable to be handled. Has been done in subsequent pr already.
                                $paymentLink->edit($input);

                                $this->repo->saveOrFail($paymentLink);

                                return $paymentLink;
                            });

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());

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
     * This method is executed to update the payment link entity after payment has been captured. This payment is
     * verified to be associated with a payment link. This is executed inside a transaction, after acquiring a lock on
     * the payment entity.
     *
     * Actions:
     *  - Payable?     Update payment link entity's attributes(including stats)
     *  - Not payable? Initiate refund for payment
     *
     * @param Payment\Entity $payment
     */
    public function updatePaymentLinkAfterPaymentCaptureIfApplicable(Payment\Entity $payment)
    {
        // TODO: Check if we should wrap this block in try..catch

        $this->repo->assertTransactionActive();

        $paymentLink = $payment->paymentLink;

        $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

        if ($paymentLink->isPayable() === true)
        {
            $this->updatePaymentLinkAfterPaymentCapture($payment, $paymentLink);
        }
        else
        {
            $this->initiateRefundForPayment($payment);
        }
    }

    protected function updatePaymentLinkAfterPaymentCapture(Payment\Entity $payment, Entity $paymentLink)
    {
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

    public function initiateRefundForPaymentIfNotCaptured(Payment\Entity $payment)
    {
        if ($payment->isCaptured() === false)
        {
            $this->initiateRefundForPayment($payment);
        }
    }

    /**
     * Validates if new payment initiation should be allowed or not
     *
     * @param Entity $paymentLink
     *
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
     * Changes payment link's status. Every status change must happen via this method which keeps a uniform log of
     * status changes and probably could do further things i.e. validation etc.
     *
     * @param Entity      $paymentLink
     * @param string      $status
     * @param string|null $statusReason
     */
    protected function changeStatus(Entity $paymentLink, string $status, string $statusReason = null)
    {
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
     * Dispatches new job onto queue for asynchronous processing of it.
     *
     * This is an edge case, and is expected to be used seldomly after
     * initial release.  Further releases should purge the requirement
     * for refund using soft reservation. As for now, we do a simple
     * refund without retry.
     *
     * @param Payment\Entity $payment
     */
    protected function initiateRefundForPayment(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_PUSH,
            [
                E::PAYMENT      => $payment->toArrayPublic(),
                E::PAYMENT_LINK => $payment->paymentLink->toArrayPublic(),
            ]);

        try
        {
            RefundPaymentJob::dispatch($this->mode, $payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }
}
