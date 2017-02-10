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

        $offer = (new Core)->create($input, $this->merchant);

        return $offer->toArrayPublic();
    }

    public function updateOffer(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_UPDATE_REQUEST, $input);

        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = (new Core)->update($offer, $input);

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

    public function addIins(string $id, array $input)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = (new Core)->addIins($offer, $input);

        return $offer->toArrayPublic();
    }

    public function deactivate(string $id)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = (new Core)->deactivate($offer);

        return $offer->toArrayPublic();
    }

    public function bulkDeactivate()
    {
        $offers = (new Core)->bulkDeactivate();

        if ($offers !== null)
        {
            return $offers->toArrayPublic();
        }

        return null;
    }
}
