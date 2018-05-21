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

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Creates a payment link
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        $paymentLink = (new Entity)->build($input);

        $paymentLink->merchant()->associate($merchant);

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
            'pl_id' => $paymentLink->getId(),
            'input' => $input,
        ]);

        $paymentLinkId = $paymentLink->getId();

        return $this->mutex->acquireAndRelease(
            $paymentLinkId,
            function() use ($paymentLink, $input)
            {
                $paymentLink->edit($input);

                $this->repo->saveOrFail($paymentLink);

                return $paymentLink;
            });
    }
}
