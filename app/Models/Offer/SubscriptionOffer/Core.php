<?php

namespace RZP\Models\Offer\SubscriptionOffer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [
            'request' => 'create',
            'input'   => $input
        ]);

        $offerSubscription = new Entity;

        $offerSubscription = $offerSubscription->build($input);

        $this->repo->saveOrFail($offerSubscription);

        return $offerSubscription;

    }
}
