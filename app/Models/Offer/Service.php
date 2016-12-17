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

        return $offer->toArrayAdmin();
    }

    public function updateOffer(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_UPDATE_REQUEST, $input);

        $offer = $this->repo->offer->findOrFailPublic($id);

        $offer = (new Core)->update($offer, $input);

        return $offer->toArrayAdmin();
    }

    public function deleteOffer(string $id)
    {
        $offer = $this->repo->offer->findOrFailPublic($id);

        $data = (new Core)->delete($offer);

        return $data;
    }

    public function updateMerchants(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_MERCHANT_UPDATE_REQ, $input);

        $offer = $this->repo->offer->findOrFailPublic($id);

        $offer->validateInput('merchant', $input);

        $merchantIds = $input['merchant_ids'];

        $action = $input['action'];

        if ($action === 'add')
        {
            $offer->merchants()->attach($merchantIds);
        }
        else
        {
            $offer->merchants()->detach($merchantIds);
        }

        $this->repo->saveOrFail($offer);

        return $offer->toArrayAdmin();
    }
}
