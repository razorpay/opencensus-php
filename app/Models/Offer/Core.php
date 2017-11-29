<?php

namespace RZP\Models\Offer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $this->verifyIdAndStripSignForLinkedOfferIds($input);

        $offer = new Entity;

        $offer->merchant()->associate($merchant);

        $offer = $offer->build($input);

        $this->checkConflictingOffers($offer);

        $this->repo->saveOrFail($offer);

        $this->traceNonExistingIins($offer, $merchant);

        return $offer;
    }

    public function update(Entity $offer, array $input)
    {
        $merchant = $this->merchant;

        $this->verifyIdAndStripSignForLinkedOfferIds($input);

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
            $this->trace->info(
                TraceCode::OFFER_DEACTIVATE,
                [
                    'offer_id'   => $offer->getPublicId(),
                ]);

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
            ($order->hasOffer() === false))
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

            if ($appliedOffer->shouldBlockPayment() === true)
            {
                $errorMessage = $appliedOffer->getErrorMessage();

                throw new Exception\BadRequestValidationFailureException($errorMessage);
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

        $offer = $order->getOfferIfExists();

        return $offer;
    }

    public function fetchMerchantOffersForCheckout(Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $offers = $this->repo->offer->fetchOffersForCheckout([
            $merchantId,
            Account::SHARED_ACCOUNT
        ]);

        $groupedOffers = $offers->groupBy(Entity::MERCHANT_ID);

        //
        // We split the offers belonging to shared merchant
        // and current merchant in two separate groups
        //
        $directOffers = $groupedOffers->get($merchantId) ?? new PublicCollection;

        $sharedOffers = $groupedOffers->get(Account::SHARED_ACCOUNT) ?? new PublicCollection;

        $applicableOffers = $directOffers;

        //
        // For shared merchant offers if there is no similar offer (i,e for same method,
        // issuer, network etc defined), we also send the shared merchant offer to checkout
        //
        foreach ($sharedOffers as $sharedOffer)
        {
            $result = $directOffers->search(function ($offer) use ($sharedOffer)
            {
                return $offer->matches($sharedOffer);
            });

            if ($result === false)
            {
                $applicableOffers->push($sharedOffer);
            }
        }

        return $applicableOffers;
    }

    public function fetchSharedOffers()
    {
        $offers = $this->repo->offer->fetchSharedOffers();

        return $offers;
    }

    protected function checkConflictingOffers(Entity $offer)
    {
        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer
        $existingOffers = $this->repo->offer->fetchExistingOffers($offer, $this->merchant->getId());

        if ($existingOffers->count() > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
        }
    }

    /**
     * Checks if the offer ids provided in linked_offer_ids are valid and also
     * removes public sign from them
     *
     * @param  array $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function verifyIdAndStripSignForLinkedOfferIds(array & $input)
    {
        try
        {
            if (empty($input[Entity::LINKED_OFFER_IDS]) === false)
            {
                $input[Entity::LINKED_OFFER_IDS] = Entity::verifyIdAndStripSignMultiple(
                                                    $input[Entity::LINKED_OFFER_IDS]);
            }
        }
        catch (Exception\BadRequestException $e)
        {
            throw new Exception\BadRequestValidationFailureException(
                'linked_offer_ids are not valid');
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
