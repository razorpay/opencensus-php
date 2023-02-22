<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Order\ProductType;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Offer\SubscriptionOffer;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Currency\Core as CurrencyCore;
use Throwable;

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
                    if (isset($input[Entity::PRODUCT_TYPE]) === true and
                        $input[Entity::PRODUCT_TYPE] === Order\ProductType::SUBSCRIPTION)
                    {
                        $subscriptionInput = array_pull($input, Order\ProductType::SUBSCRIPTION);
                    }

                    $this->verifyIdAndStripSignForLinkedOfferIds($input);

                    $this->setPaymentMethodTypeForDebitCardIssuers($input);

                    $offer = new Entity;

                    $offer->merchant()->associate($merchant);

                    $offer = $offer->build($input);

                    $this->validateMerchant($merchant, $input);

                    $this->checkConflictingOffers($offer);

                    $this->repo->saveOrFail($offer);

                    if (isset($input[Entity::PRODUCT_TYPE]) and
                        $input[Entity::PRODUCT_TYPE] === Order\ProductType::SUBSCRIPTION)
                    {
                        // create entry in subscription_offers_master
                        $this->addSubscriptionData($offer, $subscriptionInput ?? []);
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

    public function bulkDeactivateOffers(array $offerIds): array
    {
        $success = [];
        $failed = [];
        $response = [];

        if(count($offerIds) > 1500) {
            throw new Exception\BadRequestException(
                TraceCode::BAD_REQUEST_ONLY_1500_OFFERS_DEACTIVATE_IN_BULK);
        }

        foreach($offerIds as $offerId){

            try {

                $this->trace->info(

                    TraceCode::OFFER_DEACTIVATE,
                    [
                        'offer_id'   => $offerId,
                    ]
                );

                $offer = $this->repo->offer->findByPublicId($offerId);

                $offer->deactivate();

                $this->repo->saveOrFail($offer);

                $success[] = $offer->getPublicId();

            }
            catch (\Exception $e){

                $failed[] = $offerId;

                $this->trace->traceException($e);

                $this->trace->warning(
                    TraceCode::OFFER_DEACTIVATE_BULK_EXCEPTION,
                    [
                        'msg' => $e->getMessage()
                    ]);
            }
        }

        $response['successful'] = $success;

        $response['failed'] = $failed;

        $this->trace->info(
            TraceCode::OFFER_DEACTIVATE_BULK_RESPONSE,
            [
                'response'   => $response,
            ]);

        return $response;

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

    public function defaultOffersForMerchant(string $merchantId)
    {
        $defaultOffers = $this->fetchDefaultOffersForMerchant($merchantId);

        $defaultOffersBool = false;

        $applicableOffers = array();

        foreach($defaultOffers as $offer)
        {
            $offer = $this->validateDefaultOfferForMerchant($offer);

            if($offer !== null)
            {
                array_push($applicableOffers, $offer);
            }
        }

        if(count($applicableOffers)>0)
        {
            $defaultOffersBool = true;
        }

        return $defaultOffersBool;

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

            $baseAmount = (new CurrencyCore())->getBaseAmount($input['order_amount'], $input['currency'], $payment->merchant->getCurrency());

            $payment->setBaseAmount($baseAmount);
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
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER, null,
                [
                    'offer_id' => $offer->getPublicId(),
                    'order_id' => $order->getPublicId(),
                ]);
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

    public function validateDefaultOfferForMerchant(Entity $offer)
    {
        $verbose = true;

        $checker = new Checker($offer, $verbose);

        if ($checker->checkOfferValidityOnMerchant() === true)
        {
            return $offer;
        }
        return null;
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

    /**
     * Returns all default offers for a merchant sorted in descending order by
     * offer usage i.e. popularity.
     *
     * @param string $merchantId
     *
     * @return array
     * @throws Throwable
     */
    public function fetchOffersForAffordability(string $merchantId): array
    {
        $offers = $this->repo->offer->fetchAllActiveNonSubscriptionOffers($merchantId)->toArray();

        $offerIds = array_column($offers, Entity::ID);

        // We will be scanning only last one-month data to calculate offer usage.
        // We will re-look this strategy when we build v2 of affordability widget
        // where we might include SR rate of offer & other such parameters.
        $oneMonthAgo = Carbon::today()->subMonth()->getTimestamp();

        $offerUsages = $this->getOffersUsage($offerIds, $merchantId, $oneMonthAgo);

        usort($offers, static function ($offer1, $offer2) use($offerUsages) {
            // Sort Descending
            return $offerUsages[$offer2['id']] <=> $offerUsages[$offer1['id']];
        });

        foreach ($offers as &$offer) {
            $offer = Arr::only($offer, Entity::getVisibleForAffordability());
        }

        return $offers;
    }

    public function fetchSharedOffers()
    {
        $offers = $this->repo->offer->fetchSharedOffers();

        return $offers;
    }

    /**
     * Fetches the usage of each offer.
     * An offer is considered as used if it has been associated with an authorized payment.
     *
     * @param string[] $offerIds     The list of offer ids whose usage needs to be calculated
     * @param string   $merchantId   The primary key of the merchant to whom these offers belong to
     * @param int      $minCreatedAt The epoch timestamp post which data needs to be scanned
     *
     * @return array
     */
    public function getOffersUsage(array $offerIds, string $merchantId, int $minCreatedAt): array
    {
        if (empty($offerIds)) {
            return [];
        }

        $offersUsage = $this->repo->payment->getOffersUsage($offerIds, $merchantId, $minCreatedAt);

        // Initialize offer ids which haven't been used yet with a zero (0).
        foreach ($offerIds as $id) {
            if (!array_key_exists($id, $offersUsage)) {
                $offersUsage[$id] = 0;
            }
        }

        return $offersUsage;
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


    /**
     * @param array $input
     */
    protected function setPaymentMethodTypeForDebitCardIssuers(array &$input): void
    {
        $issuer = $input[Entity::ISSUER] ?? '';

        if (IFSC::isDebitCardIssuer($issuer)) {
            // HDFC_DC & UTIB_DC are hacks to differentiate between credit & debit card EMI plans
            $input[Entity::PAYMENT_METHOD_TYPE] = Emi\Type::DEBIT;
        }
        elseif ((isset($input[Entity::PAYMENT_METHOD]) === true) and
                ($input[Entity:: PAYMENT_METHOD] === Payment\Method::EMI) and
                (isset($input[Entity::PAYMENT_METHOD_TYPE]) === false))
        {
            $input[Entity::PAYMENT_METHOD_TYPE] = Emi\Type::CREDIT;
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

    private function addSubscriptionData(Entity $offer, array $subscriptionInput = [])
    {
        if ($this->isSubscriptionOffersEnabled() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_OFFER_SUBSCRIPTION_NOT_ENABLED);
        }

        if (empty($subscriptionInput) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_OFFER_SUBSCRIPTION_PAYLOAD_ABSENT);
        }

        $subscriptionInput[SubscriptionOffer\Entity::OFFER_ID] = $offer->getId();

        (new SubscriptionOffer\Core())->create($subscriptionInput);
    }

    /**
     * Checks RazorX is enabled for Offer On Subscription
     * @return bool
     */
    protected function isSubscriptionOffersEnabled()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::OFFER_ON_SUBSCRIPTION) === true)
        {
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => true ]);

            return true;
        }

        $treatment = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::OFFER_ON_SUBSCRIPTION,
            $this->mode
        );

        $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [
            'merchant_id' => $this->merchant->getId(),
            'enabled' => ($treatment === null or $treatment !== 'on') ? false : true,
        ]);

        if (($treatment === null) or
            ($treatment !== 'on'))
        {
            return false;
        }

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
        $fetchActive  = $input[SubscriptionOffer\Entity::ACTIVE] ?? true;
        $fetchExpired = $input[SubscriptionOffer\Entity::EXPIRED] ?? false;

        $offer = $this->repo->offer->fetchSubscriptionOfferById($offerId, $fetchActive, $fetchExpired);

        $data = [
            SubscriptionOffer\Entity::DISCOUNTED_AMOUNT => (int)$input['amount'],
            SubscriptionOffer\Entity::ORIGINAL_AMOUNT   => (int)$input['amount'],
            SubscriptionOffer\Entity::OFFER_VALID       => 0,
            SubscriptionOffer\Entity::MESSAGE           => null,
            SubscriptionOffer\Entity::OFFER_NAME        => '',
            SubscriptionOffer\Entity::OFFER_DESC        => '',
        ];

        if ($offer === null)
        {
            $data[SubscriptionOffer\Entity::MESSAGE]    = 'Offer Not Found';

            return $data;
        }

        try
        {
            $data[SubscriptionOffer\Entity::OFFER_NAME]  = $offer->getName();
            $data[SubscriptionOffer\Entity::OFFER_DESC]  = $offer->getDisplayText();

            $data[SubscriptionOffer\Entity::DISCOUNTED_AMOUNT] = $offer->getDiscountedAmount($input['amount']);

            // Checking this separately as -
            // 1. there will be cases where payment id won't be present
            // 2. Don't need to have db calls when amount it self is not discountable
            if ($data[SubscriptionOffer\Entity::DISCOUNTED_AMOUNT] === $data[SubscriptionOffer\Entity::ORIGINAL_AMOUNT])
            {
                $data[SubscriptionOffer\Entity::MESSAGE]    = PublicErrorDescription::OFFER_ORDER_AMOUNT_LESS_OFFER_MIN_AMOUNT;
            }
            else
            {
                // We will do payment entity validation, iff present
                if (isset($input[SubscriptionOffer\Entity::PAYMENT_ID]) === true)
                {
                    $this->validateForFutureSubscriptionPayment($offerId, $offer, $input);
                }

                $data[SubscriptionOffer\Entity::OFFER_VALID] = 1;
            }
        }
        catch (\Exception $e)
        {
            // Not an error for just the calculation, so printing in info
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION_NA,
                [
                    'input'  => $input,
                    'reason' => $e->getMessage()
                ]
            );

            $data[SubscriptionOffer\Entity::MESSAGE]    = $e->getMessage();
        }

        return $data;
    }

    private function validateForFutureSubscriptionPayment($offerId, $offer, $input)
    {
        $payment = $this->repo->payment->fetchByIdandSubscriptionId(
            'pay_' . $input[SubscriptionOffer\Entity::PAYMENT_ID],
            $input[SubscriptionOffer\Entity::SUBSCRIPTION_ID]
        );

        if ($payment->getMethod() !== $offer->getPaymentMethod())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_OFFER_METHOD_MISMATCH,
                null,
                [
                    'payment_method' => $payment->getMethod(),
                    'offer_method'   => $offer->getPaymentMethod()
                ]
            );
        }

        $baseOffer = $this->repo->offer->findByPublicId(Entity::getSignedId($offerId));
        $checker   = new Checker($baseOffer, false);

        $this->repo->beginTransactionAndRollback(
            function () use ($checker, $baseOffer, $payment, $input) {

                $orderInput = [
                    'amount'   => $input['amount'],
                    'currency' => 'INR',
                ];

                $order = (new Order\Core())->create($orderInput, $this->merchant);

                if ($checker->checkApplicabilityForPaymentBeforeCheckout($payment, $order) === false)
                {
                    if ($baseOffer->shouldBlockPayment() === true)
                    {
                        $errorMessage = $baseOffer->getErrorMessage();

                        throw new Exception\BadRequestValidationFailureException($errorMessage);
                    }
                }
            });
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
