<?php

namespace RZP\Models\Offer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::OFFER_CREATE_REQUEST, $input);

        $offer = $this->core->create($input);

        return $offer->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_UPDATE_REQUEST, $input);

        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = $this->core->update($offer, $input);

        return $offer->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        return $offer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $offers = $this->repo->offer->fetch($input, $this->merchant->getId());

        return $offers->toArrayPublic();
    }

    public function deactivate()
    {
        $disabledOffers = $this->core->deactivate();

        return $disabledOffers;
    }
}
