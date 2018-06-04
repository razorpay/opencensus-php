<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

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
     * Updates the status to INACTIVE, status_reason to EXPIRED of all the payment links
     * which are active and past expire_by.
     *
     * @return array
     */
    public function updateExpired(): array
    {
        $timeStarted = microtime(true);

        $paymentLinks = $this->repo->payment_link->getActiveAndPastExpiredByPaymentLinks();

        $summary = [
            'total_payment_links_count' => $paymentLinks->count(),
            'failed_payment_link_ids'   => [],
        ];

        foreach ($paymentLinks as $paymentLink)
        {
            try
            {
                $this->updateExpiredPaymentLink($paymentLink);
            }
            catch (\Exception $e)
            {
                $summary['failed_payment_link_ids'][] = $paymentLink->getId();

                $this->trace->traceException($e, null, null, ['id' => $paymentLink->getId()]);
            }
        }

        $timeTaken = (microtime(true) - $timeStarted) / 1000;

        $summary['time_taken'] = $timeTaken . ' secs';

        $this->trace->debug(TraceCode::PAYMENT_LINK_EXPIRE_CRON_SUMMARY, $summary);

        $slackMessage = 'Payment links past expire_by, marked expired via cron.';

        $this->slack->queue($slackMessage, $summary, ['channel' => $this->slackTechLogsChannel]);

        return $summary;
    }

    /**
     * Updates the status to INACTIVE, status_reason to EXPIRED of
     * an individual expired payment link by locking it.
     * @param Entity $paymentLink
     */
    protected function updateExpiredPaymentLink(Entity $paymentLink)
    {
        $this->repo->transaction(
            function () use ($paymentLink)
            {
                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                $paymentLink->setStatus(Status::INACTIVE);

                $paymentLink->setStatusReason(StatusReason::EXPIRED);

                $this->repo->saveOrFail($paymentLink);

                $this->trace->info(TraceCode::PAYMENT_LINK_UPDATE_STATUS, [
                    'id'            => $paymentLink->getId(),
                    'status'        => $paymentLink->getStatus(),
                    'status_reason' => $paymentLink->getStatusReason(),
                ]);
            });
    }
}
