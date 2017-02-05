<?php

namespace RZP\Models\Offer;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant)
    {
        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer

        $existingOffers = $this->repo->offer->fetchExistingOffers($input, $merchant->getId());

        if ($existingOffers->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
        }

        $offer = (new Entity)->build($input);

        $offer->merchant()->associate($merchant);

        $this->repo->saveOrFail($offer);

        return $offer;
    }

    public function update(Entity $offer, array $input)
    {
        $offer->edit($input);

        $this->repo->saveOrFail($offer);

        return $offer;
    }

    public function validateOfferApplicableOnPayment(Payment\Entity $payment)
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

        if ($offerChecker->checkOfferApplicableOnPayment($payment) === false)
        {
            $this->trace->info(TraceCode::OFFER_NOT_APPLIED_ON_PAYMENT, [
                'payment_id' => $payment->getId(),
                'offer_id'   => $appliedOffer->getId()
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_OFFER_INVALID_FOR_PAYMENT);
        }

        $this->trace->info(TraceCode::OFFER_APPLIED_ON_PAYMENT, [
            'payment_id' => $payment->getId(),
            'offer_id'   => $appliedOffer->getId()
        ]);

        return;
    }

    public function fetchForOrder(string $orderId, Merchant\Entity $merchant)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $merchant);

        $offer = $order->offer;

        if ($offer !== null)
        {
            return $offer->toArrayPublic();
        }

        return null;
    }
}
