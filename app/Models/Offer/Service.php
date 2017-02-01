<?php

namespace RZP\Models\Offer;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createOffer(array $input)
    {
        $this->trace->info(TraceCode::OFFER_CREATE_REQUEST, $input);

        $offer = (new Core)->create($input);

        return $offer->toArrayPublic();
    }

    public function updateOffer(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_UPDATE_REQUEST, $input);

        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = (new Core)->update($offer, $input);

        return $offer->toArrayAdmin();
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
}
