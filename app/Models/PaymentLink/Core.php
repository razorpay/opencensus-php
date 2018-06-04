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
     * @param Entity $paymentLink
     * @param array  $input
     *
     * @return array
     */
    public function sendNotification(Entity $paymentLink, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_SEND_NOTIFICATION,
            [
                'payment_link_id' => $paymentLink->getId(),
                'input'           => $input,
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
}
