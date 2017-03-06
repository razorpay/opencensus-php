<?php

namespace RZP\Models\Offer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Card\IIN;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $offer = (new Entity)->build($input);

        $this->checkIfOfferCreateValid($offer, $merchant);

        $offer->merchant()->associate($merchant);

        $this->repo->saveOrFail($offer);

        $this->traceNonExistingIins($offer, $merchant);

        return $offer;
    }

    public function update(Entity $offer, array $input)
    {
        $merchant = $this->merchant;

        $offer->edit($input);

        $this->repo->saveOrFail($offer);

        $this->traceNonExistingIins($offer, $merchant);

        return $offer;
    }

    public function deactivate()
    {
        $activeExpiredOffers = $this->repo->offer->fetchActiveExpiredOffers();

        $response = [];

        foreach ($activeExpiredOffers as $offer)
        {
            $offer->deactivate();

            $this->repo->saveOrFail($offer);

            $response[] = $offer->getPublicId();
        }

        return $response;
    }

    public function validateOfferApplicableOnPayment(Payment\Entity $payment)
    {
        if ($payment->getApiOrderId() === null)
        {
            return;
        }

        $order = $this->repo->order->fetchForPayment($payment);

        if (($order === null) or
            ($order->getOfferId() === null))
        {
            return;
        }

        $appliedOffer = $order->offer;

        $offerChecker = new Checker($appliedOffer, true);

        if ($offerChecker->checkOfferApplicableOnPayment($payment) === false)
        {
            $this->trace->info(
                TraceCode::OFFER_NOT_APPLIED_ON_PAYMENT,
                [
                    'payment_id' => $payment->getId(),
                    'offer_id'   => $appliedOffer->getId()
                ]);

            if ($appliedOffer->shouldBlock() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_INVALID_OFFER);
            }
        }

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_PAYMENT,
            [
                'payment_id' => $payment->getId(),
                'offer_id'   => $appliedOffer->getId()
            ]);
    }

    public function fetchForOrder(string $orderId, Merchant\Entity $merchant)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $merchant);

        $offer = $this->repo->offer->fetchForOrder($order);

        return $offer;
    }

    public function fetchSharedOffers()
    {
        $offers = $this->repo->offer->fetchSharedOffers();

        return $offers;
    }

    protected function checkIfOfferCreateValid(Entity $offer, Merchant\Entity $merchant)
    {
        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer
        $existingOffers = $this->repo->offer->fetchExistingOffers($offer, $merchant->getId());

        if ($existingOffers->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
        }
    }

    protected function traceNonExistingIins(Entity $offer, Merchant\Entity $merchant)
    {
        if (isset($offer[Entity::IINS]) === true)
        {
            $iins = $offer[Entity::IINS];

            $existingIins = $this->repo->iin->findMany($iins)->getIds();

            $nonExistingIins = array_diff($iins, $existingIins);

            $this->trace->info(
                TraceCode::OFFER_IIN_DOES_NOT_EXISTS,
                [
                    'merchant_id'       => $merchant->getId(),
                    'non_existing_iins' => array_values($nonExistingIins),
                ]);
        }
    }
}
