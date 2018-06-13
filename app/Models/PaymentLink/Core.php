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
     * Base payment link url from which payment link is generated.
     * @var string
     */
    protected $paymentLinkBaseUrl;

    public function __construct()
    {
        parent::__construct();

        $this->mutex                = $this->app['api.mutex'];
        $this->elfin                = $this->app['elfin'];
        $this->paymentLinkBaseUrl   = $this->app['config']->get('app.payment_link_base_url');
    }

    /**
     * Creates a payment link
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity $paymentLink
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        $paymentLink = (new Entity)->build($input);

        $paymentLink->merchant()->associate($merchant);

        $paymentLink->generateId();

        $this->setPaymentLinkShortUrl($paymentLink);

        $this->repo->saveOrFail($paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * Updates a payment link
     *
     * @param Entity $paymentLink
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $paymentLink, array $input): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATE_REQUEST, [
            'id'    => $paymentLink->getId(),
            'input' => $input,
        ]);

        // TODO : TO change to lockforupdate
        return $this->mutex->acquireAndRelease(
            $paymentLink->getId(),
            function() use ($paymentLink, $input)
            {
                $paymentLink->reload();

                // TODO: cases related to expire_by and times_payable to be handled
                $paymentLink->edit($input);

                $this->repo->saveOrFail($paymentLink);

                $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());

                return $paymentLink;
            });
    }

    /**
     * This method sets the short_url of a paymentLink
     * @param Entity $paymentLink
     */
    protected function setPaymentLinkShortUrl(Entity $paymentLink)
    {
        $longUrl = $paymentLink->getLongUrl($this->paymentLinkBaseUrl, $this->mode);

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $paymentLink->setShortUrl($shortenedUrl);
    }
}
