<?php

namespace RZP\Models\Offer;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer

        $existingOffers = $this->repo->offer->fetchExistingOffers($input);

        if ($existingOffers->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
        }
        $newOffer = (new Entity)->build($input);

        $this->repo->saveOrFail($newOffer);

        return $newOffer;
    }

    public function update(Entity $offer, array $input)
    {
        $offer->edit($input);

        $this->repo->saveOrFail($offer);

        return $offer;
    }

    public function delete(Entity $offer)
    {
        $this->repo->offer->delete($offer);

        return [
            'id' => $offer->getId()
        ];
    }

    public function getMerchantOffers(Merchant\Entity $merchant)
    {
        $data = [
            'entity' => 'offers'
        ];

        $methods = Payment\Method::getAllPaymentMethods();

        foreach ($methods as $method)
        {
            $data[$method] = $this->getOffersByMerchantAndMethod($merchant, $method);
        }

        return $data;
    }

    public function fetchOffers(Merchant\Entity $merchant)
    {
        $offers = $this->repo->offer->fetchOffersForMerchant($merchant);

        return $offers;
    }

    private function getOffersByMerchantAndMethod(Merchant\Entity $merchant, string $method)
    {
        $offers = $this->repo->offer->fetchActiveOfferByMerchantAndMethod($merchant, $method);

        return $offers->toArrayPublic();
    }
}
