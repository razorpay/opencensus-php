<?php

namespace RZP\Models\PaymentLink;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Jobs\PaymentLinkRefund as PaymentLinkRefundJob;

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
     * This method is executed to update the payment link entity
     * after payment has been captured. This payment is verified
     * to be associated with a payment link.
     * This is executed inside a transaction, after acquiring a
     * mutex lock on the payment entity.
     *
     * Check link state:
     *  - Payable       : Update payment link entity stats
     *  - Not payable   : Refund to customer via queue
     *
     * @param Payment\Entity $payment
     */
    public function updatePaymentLinkAfterCapture(Payment\Entity $payment)
    {
        $paymentLink = $payment->paymentLink;

        if ($this->isPayable($paymentLink) === true)
        {
            $this->updateFromCapturedPayment($payment, $paymentLink);
        }
        else
        {
            $this->refundPaymentForLink($payment, $paymentLink);
        }
    }

    /**
     * This method should be called from a transaction
     *
     * @param Payment\Entity $payment
     * @param Entity $paymentLink
     */
    protected function updateFromCapturedPayment(Payment\Entity $payment, Entity $paymentLink)
    {
        $paymentLink->incrementTimesPaid();

        $paymentLink->incrementTotalAmountPaidBy($payment->getAmount());

        if (($paymentLink->getTimesPayable() !== null) and
            ($paymentLink->getTimesPayable() === $paymentLink->getTimesPaid()))
        {
            $paymentLink->setStatus(Status::INACTIVE);
            $paymentLink->setStatusReason(StatusReason::COMPLETED);

            $this->trace->debug(
                TraceCode::PAYMENT_LINK_STATUS_CHANGE,
                [
                    'payment_id'        => $payment->getId(),
                    'payment_link_id'   => $paymentLink->getId(),
                    'to_status'         => Status::INACTIVE,
                    'to_status_reason'  => StatusReason::COMPLETED,
                ]);
        }

        $this->repo->payment_link->saveOrFail($paymentLink);

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_PAID,
            [
                'payment_id'        => $payment->getId(),
                'payment_link_id'   => $paymentLink->getId(),
            ]);
    }

    public function validatePaymentAfterCaptureAttempt(Payment\Entity $payment)
    {
        if ($payment->isCaptured() === false)
        {
            $this->refundPaymentForLink($payment);
        }
    }

    public function isPayable(Entity $paymentLink): bool
    {
        return (($paymentLink->isActive() === true) and
                ($paymentLink->isExpired() === false));
    }

    public function validateIsPaymentInitiatable(Entity $paymentLink)
    {
        if (($this->isPayable($paymentLink) === false) or
            ($this->hasPaymentSlots($paymentLink) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE);
        }
    }

    protected function hasPaymentSlots(Entity $paymentLink): bool
    {
        $paymentLimit = $paymentLink->getTimesPayable();

        if ($paymentLimit === null)
        {
            return true;
        }

        $succeedingPaymentsCount = $this->repo->payment_link->getSucceedingPaymentsCount($paymentLink);

        $slotsAvailable = $paymentLimit - $paymentLink->getTimesPaid() - $succeedingPaymentsCount;

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
||||||| merged common ancestors
=======

    /**
     * Dispatches new job onto queue for asynchronous processing of it
     *
     * @param Payment\Entity $payment
     */
    protected function refundPaymentForLink(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_REFUND_REQUESTED,
            [
                'id'    => $payment->getPublicId(),
                'status'=> $payment->getStatus(),
                'pl_id' => $payment->paymentLink->getPublicId(),
            ]);

        PaymentLinkRefundJob::dispatch($this->mode, $payment->getId(), []);
    }
>>>>>>> [PaymentLink] payment link core logic
}
