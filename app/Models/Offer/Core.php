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
use RZP\Models\Order\ProductType;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Offer\SubscriptionOffer;
use RZP\Models\Payment\Processor\Wallet;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::OFFER_CREATE_REQUEST, $input);

        $merchant = $this->merchant;

        $resource = 'offer_create_' . $merchant->getId();

        return $this->mutex->acquireAndRelease(
            $resource,
            function() use ($input, $merchant)
            {
                return $this->repo->transaction(function() use ($input, $merchant)
                {
                    if (isset($input[Entity::PRODUCT_TYPE]) === true and $input[Entity::PRODUCT_TYPE] === 'subscription')
                    {
                        $subscriptionInput = array_pull($input, 'subscription');
                    }

                    $this->verifyIdAndStripSignForLinkedOfferIds($input);

                    $offer = new Entity;

                    $offer->merchant()->associate($merchant);

                    $offer = $offer->build($input);

                    $this->validateMerchant($merchant, $input);

                    $this->checkConflictingOffers($offer);

                    $this->repo->saveOrFail($offer);

                    if (isset($input[Entity::PRODUCT_TYPE]) and $input[Entity::PRODUCT_TYPE] === 'subscription')
                    {
                        // create entry in subscription_offers_master
                        $this->addSubscriptionData($offer, $subscriptionInput);
                    }

                    $this->traceNonExistingIins($offer, $merchant);

                    return $offer;
                });
            });
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

    public function validateOfferApplicableOnPayment(Entity $offer, Payment\Entity $payment, array $input)
    {
        $verbose = true;

        $checker = new Checker($offer, $verbose);

        if ($checker->checkApplicabilityForPayment($payment, $payment->order) === false)
        {
            $this->trace->info(
                TraceCode::OFFER_NOT_APPLIED_ON_PAYMENT,
                [
                    'payment_id' => $payment->getId(),
                    'offer_id'   => $offer->getId()
                ]);

            $this->lockDecrementCurrentOfferUsage($payment);

            if ($offer->shouldBlockPayment() === true)
            {
                $errorMessage = $offer->getErrorMessage();

                throw new Exception\BadRequestValidationFailureException($errorMessage);
            }

            $this->revertOfferPaymentInput($offer,  $payment,  $input);

        }

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_PAYMENT,
            [
                'payment_id' => $payment->getId(),
                'offer_id'   => $offer->getId()
            ]);
    }

    public function revertOfferPaymentInput(Entity $offer, Payment\Entity $payment, array $input)
    {
        //In case of discounted offer where Rzp modifies the amount, if offer validations fails
        //and merchant does not want to block payment for that offer, setting the original order amount
        //again for payment amount.

        if(($offer->getOfferType() === Constants::INSTANT_OFFER) and ($input['order_amount'] !== null ))
        {
            $payment->setAmount($input['order_amount']);

            $payment->setBaseAmount($input['order_amount']);
        }

        //As offer is not applicable, dissociating it
        $payment->dissociateOffer($offer);
    }

    public function fetchSharedAccOffersForCheckout(Merchant\Entity $merchant)
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

        $applicableOffers = new PublicCollection();

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

        if ($this->validateOfferForOrderProductType($order, $offer) === false)
        {
            return null;
        }

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

    public function validateDefaultOfferForOrder(Order\Entity $order, Entity $offer)
    {
        if ($this->validateOfferForOrderProductType($order, $offer) === false)
        {
            return null;
        }

        $verbose = true;

        $checker = new Checker($offer, $verbose);

        if ($checker->checkValidityOnOrder($order) === true)
        {
            return $offer;
        }
    }

    public function validateOfferForOrderProductType(Order\Entity $order, Entity $offer)
    {
        if (($order->getProductType() === ProductType::SUBSCRIPTION) and
            ($offer->getProductType() !== $order->getProductType()))
        {
            return false;
        }
        else if (($order->getProductType() !== ProductType::SUBSCRIPTION)  and
                 ($offer->getProductType() === ProductType::SUBSCRIPTION))
        {
            return false;
        }

        return true;
    }

    public function fetchDefaultOffersForMerchant(string $merchantId)
    {
        $defaultOffers = $this->repo->offer->fetchAllDefaultOffersForMerchant($merchantId);

        return $defaultOffers;
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

    public function getApplicableOffersForPayment(Order\Entity $order, Payment\Entity $payment)
    {
        $applicableOffers = [];

        $offers = $order->offers;

        $verbose = true;

        foreach ($offers as $offer)
        {
            $checker = new Checker($offer, $verbose);

            if ($checker->checkApplicabilityForPayment($payment, $order))
            {
                $applicableOffers[] = $offer->getPublicId();
            }
        }

        return $applicableOffers;
    }

    public function withMerchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    //increment the offer usage count after failed payment for max offer validation.
    public function lockIncrementCurrentOfferUsage(Entity $offer)
    {
        if($offer !== null)
        {
            $offer = $this->repo->transaction(function () use($offer)
            {
                $offer = $this->repo->offer->lockForUpdate($offer->getId());

                $offer->setCurrentUsageCount($offer->getCurrentOfferUsage() + 1);

                $this->repo->saveOrFail($offer);

                return $this->repo->offer->findByPublicIdAndMerchant($offer->getPublicId(), $this->merchant);
            });

            return $offer;
        }
    }

    //decrement the offer usage count after failed payment for max offer validation.
    public function lockDecrementCurrentOfferUsage(Payment\Entity $payment)
    {
        $offer = $payment->getOffer();

        if($offer !== null && $offer->getMaxOfferUsage() !== null)
        {
            $offer = $this->repo->transaction(function () use($offer)
            {
                $offer = $this->repo->offer->lockForUpdate($offer->getId());

                $offer->setCurrentUsageCount($offer->getCurrentOfferUsage() - 1);

                $this->repo->saveOrFail($offer);

                return $offer;
            });

            return $offer;
        }


    }

    private function addSubscriptionData(Entity $offer, array $subscriptionInput)
    {
        if ($this->isSubscriptionOffersEnabled() === true)
        {
            $subscriptionInput[SubscriptionOffer\Entity::OFFER_ID] = $offer->getId();

            (new SubscriptionOffer\Core())->create($subscriptionInput);
        }
    }

    /**
     * Checks RazorX is enabled for Offer On Subscription
     * @return bool
     */
    protected function isSubscriptionOffersEnabled()
    {
        $treatment = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::OFFER_ON_SUBSCRIPTION,
            $this->mode
        );

        if (($treatment === null) or
            ($treatment !== 'on'))
        {
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => false ]);

            return false;
        }

        $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => true ]);

        return true;
    }

    /**
     * Checks If Offer is Existing and can be Applied on the Amount
     * Used by Subscription Service to validate even before forcing an offer, on subscription creation
     * @param $input
     * @return array
     */
    public function fetchOffersDiscountForSubscription($input): array
    {
        $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION_CALCULATION, ['input' => $input]);

        $offerId      = Entity::verifyIdAndStripSign($input['offer']);
        $fetchActive  = $input['active'] ?? true;
        $fetchExpired = $input['expired'] ?? false;

        $offer = $this->repo->offer->fetchSubscriptionOfferById($offerId, $fetchActive, $fetchExpired);

        $data = [
            'original_amount' => (int)$input['amount'],
            'offer_valid'     => 0,
            'message'         => null,
            'offer_name'      => '',
            'offer_desc'      => '',
        ];

        if ($offer === null)
        {
            $data['discounted_amount'] = (int)$input['amount'];
            $data['message']           = 'Offer Not Found';

            return $data;
        }

        try
        {
            $data['discounted_amount'] = $offer->getDiscountedAmount($input['amount']);

            if ($data['discounted_amount'] === $data['original_amount'])
            {
                $data['message']     = 'Offer No Discount Applied';
            }
            else
            {
                $data['offer_name']  = $offer->getName();
                $data['offer_desc']  = $offer->getDisplayText();
                $data['offer_valid'] = 1;
            }

            return $data;
        }
        catch (\Exception $e)
        {
            // Not an error for just the calculation, so printing in info
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION_NA,
                ['input'  => $input,
                 'reason' => $e->getMessage()
                ]
            );

            $data['discounted_amount'] = (int)$input['amount'];
            $data['message']           = $e->getMessage();

            return $data;
        }
    }

    /**
     * Fetches Offers that can be applied on a subscription
     * Used By subscription Service to Show On Hosted Page
     * @param $input
     * @return array
     */
    public function fetchOffersPreferenceForSubscription($input): array
    {
        $data['offers'] = [];

        $data['force_offer'] = false;

        $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

        $invoiceEntity = $this->repo->invoice->fetchIssuedInvoicesOfSubscriptionId($subscriptionId);

        if ($invoiceEntity !== null and $invoiceEntity->getOrderId() !== null)
        {
            $orderId = 'order_' . $invoiceEntity->getOrderId();

            $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            if (($order !== null) and
                ($order->hasOffers() === true))
            {
                $offers = $order->offers;

                $orderAmount = $order->getAmount();

                if ($offers->isEmpty() !== true)
                {
                    $verbose = true;

                    foreach ($offers as $offer)
                    {
                        $checker = new Checker($offer, $verbose);

                        if ($checker->checkValidityOnOrder($order) === true)
                        {
                            $data['offers'][] = $offer->toArrayCheckout($orderAmount);
                        }
                    }

                    if (($offers->count() === 1) and
                        ($order->isOfferForced() === true))
                    {
                        $data['force_offer'] = true;
                    }
                }
            }
        }

        return $data;
    }
}
