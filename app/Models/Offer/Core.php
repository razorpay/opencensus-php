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

        $existingOffers = $this->repo->offer->fetchExistingOffers($input, $this->merchant->getId());

        if ($existingOffers->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
        }

        $offer = (new Entity)->build($input);

        $offer->merchant()->associate($this->merchant);

        $this->repo->saveOrFail($offer);

        return $offer;
    }

    public function update(Entity $offer, array $input)
    {
        $offer->edit($input);

        $this->repo->saveOrFail($offer);

        return $offer;
    }

    public function delete(Entity $offer)
    {
        $this->repo->deleteOrFail($offer);

        return $offer;
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

    public function checkOfferApplicableOnPayment(Payment\Entity $payment)
    {
        $order = $payment->order;
        $appliedOffer = null;

        if ($order !== null)
        {
            $appliedOffer = $order->offer;
        }

        // Return if offer is not present for order
        if ($appliedOffer === null)
        {
            return;
        }

        $offerChecker = new Checker($appliedOffer);

        if ($offerChecker->checkOfferApplicableOnPayment($payment) === false);
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_OFFER_INVALID_FOR_PAYMENT);
        }

        return;
    }

    private function getOffersByMerchantAndMethod(Merchant\Entity $merchant, string $method)
    {
        $offers = $this->repo->offer->fetchActiveOfferByMerchantAndMethod($merchant, $method);

        return $offers->toArrayPublic();
    }
}
