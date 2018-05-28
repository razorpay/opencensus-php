<?php

namespace RZP\Models\PaymentLink;

use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;
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
    protected $basePaymentLinkUrl;

    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    public function __construct()
    {
        parent::__construct();

        $this->mutex              = $this->app['api.mutex'];
        $this->elfin              = $this->app['elfin'];
        $this->basePaymentLinkUrl = Config::get('app.payment_link');
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

        return $this->repo->transaction(function() use ($input, $merchant)
        {
            $paymentLink = (new Entity)->build($input);

            $paymentLink->merchant()->associate($merchant);

            $this->repo->saveOrFail($paymentLink);

            $this->setShortUrl($paymentLink);

            $this->repo->saveOrFail($paymentLink);

            $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

            return $paymentLink;
        });
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
            'id'    => $paymentLink->getPublicId(),
            'input' => $input,
        ]);

        return $this->mutex->acquireAndRelease(
            $paymentLink->getId(),
            function() use ($paymentLink, $input)
            {
                $paymentLink->reload();

                // TODO: cases related to expire_by and times_payable to be handled
                $paymentLink->edit($input);

                $this->repo->saveOrFail($paymentLink);

                return $paymentLink;
            });
    }

    /**
     * This method sets the short_url of a paymentLink
     * @param Entity $paymentLink
     */
    protected function setShortUrl(Entity $paymentLink)
    {
        $longUrl = $this->getPaymentLinkLongUrl($paymentLink);

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $this->trace->info(TraceCode::PAYMENT_LINK_URLS, [
            'id'        => $paymentLink->getId(),
            'short_url' => $shortenedUrl,
            'long_url'  => $longUrl,
        ]);

        $paymentLink->setShortUrl($shortenedUrl);
    }

    /**
     * Payment link long url is of the following format:
     * <base payment link url>/(t|l)/<Payment link public id>
     * Here t or l is short form for test or live mode.
     * @param Entity $paymentLink
     *
     * @return string $paymentLinkLongUrl
     */
    protected function getPaymentLinkLongUrl(Entity $paymentLink): string
    {
        $shortMode = self::SHORT_MODE_TEST;

        if ($this->mode === Mode::LIVE)
        {
            $shortMode = self::SHORT_MODE_LIVE;
        }

        $paymentLinkLongUrl = $this->basePaymentLinkUrl . '/' . $shortMode . '/' . $paymentLink->getPublicId();

        return $paymentLinkLongUrl;
    }
}
