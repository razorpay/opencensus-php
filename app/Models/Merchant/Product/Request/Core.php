<?php

namespace RZP\Models\Merchant\Product\Request;

use App;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Constants;

class Core extends Base\Core
{
    private $partnerMerchantId;

    public function __construct()
    {
        parent::__construct();

        $this->partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();
    }

    function create(array $input, Merchant\Entity $merchant, string $merchantProductId, string $status, string $type)
    {
        $request = (new Entity)->generateId();

        $request[ENTITY::REQUESTED_CONFIG] = $input;

        $request[Entity::REQUESTED_ENTITY_TYPE] = Constants::MERCHANT_ID;

        $request[Entity::REQUESTED_ENTITY_ID] = $this->partnerMerchantId ?? $merchant->getId();

        $request[Entity::MERCHANT_PRODUCT_ID] = $merchantProductId;

        $request[Entity::STATUS] = $status;

        $request[Entity::CONFIG_TYPE] = $type;

        $this->repo->transactionOnLiveAndTest(function() use ($request) {
            $this->repo->saveOrFail($request);
        });

        return $request;
    }
}
