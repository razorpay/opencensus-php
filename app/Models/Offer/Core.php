<?php

namespace RZP\Models\Offer;

use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\Processor\Wallet;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $this->verifyIdAndStripSignForLinkedOfferIds($input);

        $offer = new Entity;

        $offer->merchant()->associate($merchant);

        $offer = $offer->build($input);

        $this->validateMerchant($merchant, $input);

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

    public function validateOfferApplicableOnPayment(Entity $offer, Payment\Entity $payment)
    {
        $verbose = true;

        $checker = new Checker($offer, $verbose);

        if ($checker->checkApplicabilityForPayment($payment) === false)
        {
            $this->trace->info(
                TraceCode::OFFER_NOT_APPLIED_ON_PAYMENT,
                [
                    'payment_id' => $payment->getId(),
                    'offer_id'   => $offer->getId()
                ]);

            if ($offer->shouldBlockPayment() === true)
            {
                $errorMessage = $offer->getErrorMessage();

                throw new Exception\BadRequestValidationFailureException($errorMessage);
            }
        }

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_PAYMENT,
            [
                'payment_id' => $payment->getId(),
                'offer_id'   => $offer->getId()
            ]);
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
            $result =  $this->shouldApplySharedOffer($sharedOffer, $directOffers, $merchantId);

            if ($result === true)
            {
                $applicableOffers->push($sharedOffer);
            }
        }

        return $applicableOffers;
    }

    public function fetchAndValidateOfferForOrder(string $id, Order\Entity $order)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $verbose = true;

        $checker = new Checker($offer, $verbose);

        if ($checker->checkApplicabilityOnOrder($order) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER, null,
            [
                'offer_id' => $offer->getPublicId(),
                'order_id' => $order->getPublicId(),
            ]);
        }

        return $offer;
    }

    public function fetchSharedOffers()
    {
        $offers = $this->repo->offer->fetchSharedOffers();

        return $offers;
    }

    protected function shouldApplySharedOffer(
        Entity $sharedOffer,
        PublicCollection $directOffers,
        string $merchantId): bool
    {
        if (($sharedOffer->getIssuer() === Wallet::FREECHARGE))
        {
            //
            // For freecharge offers, if the merchant has a direct terminal with
            // freecharge, we don't show the offer, as freecharge does not support
            // offers on direct terminals.
            //
            $merchantsWithDirectFreechargeTerminals = $this->repo
                                                           ->terminal
                                                           ->getDirectTerminalsForGateway(Gateway::WALLET_FREECHARGE)
                                                           ->pluck(Terminal\Entity::MERCHANT_ID)
                                                           ->toArray();

            if (in_array($merchantId, $merchantsWithDirectFreechargeTerminals, true) === true)
            {
                return false;
            }
        }

        //
        // Find matching direct offers for the shared offer
        //
        $matchingDirectOfferPresent = $directOffers->search(function ($offer) use ($sharedOffer)
        {
            return $offer->matches($sharedOffer);
        });

        //
        // Only apply shared offer when no direct offer is found.
        //
        return ($matchingDirectOfferPresent === false);
    }

    protected function checkConflictingOffers(Entity $offer)
    {
        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer
        $existingOffers = $this->repo->offer->fetchExistingOffers($offer, $this->merchant->getId());

        /**
         * This will check if any existing offer with
         * same emi duration exists. For example
         * existing offer has null emi_durations that
         * means all emi durations are valid. So any
         * new offer with same issuer and any emi duration like
         * 3 will fail
         */
        if ($offer->getEmiSubvention() === true)
        {
            $existingDurations = [];

            $existingOffers->each(function ($existingOffer) use(& $existingDurations) {
                $existingOfferDuration = $existingOffer[Entity::EMI_DURATIONS] ?: Emi\Entity::VALID_DURATIONS;

                $existingDurations = array_merge($existingDurations, $existingOfferDuration);
            });

            $offerEmiDurations = $offer->getEmiDurations() ?: Emi\Entity::VALID_DURATIONS;

            if (empty(array_intersect($offerEmiDurations, $existingDurations)) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
            }
        }
        else if($existingOffers->count() > 0)
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

    protected function validateMerchant(Merchant\Entity $merchant, array & $input)
    {
        if($merchant->isShared() === true)
        {
            return;
        }

        if(empty($input[Entity::PAYMENT_METHOD]) === true)
        {
            return;
        }

        $merchantPaymentMethods = $merchant->methods;

        $method = $input[Entity::PAYMENT_METHOD];

        if($merchantPaymentMethods->isMethodEnabled($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Payment method not enabled for the merchant : $method", Entity::PAYMENT_METHOD);
        }
    }
}
