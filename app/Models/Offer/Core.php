<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\CobrandingPartner;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Card;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Order\ProductType;
use RZP\Error\PublicErrorDescription;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\Error;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Offer\SubscriptionOffer;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Currency\Core as CurrencyCore;
use RZP\Exception\BadRequestValidationFailureException;
use Throwable;

class Core extends Base\Core
{
    protected $mutex;

    protected OffersEngine $offersEngine;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->mode = $this->app['rzp.mode'] ?? 'live';

        $this->offersEngine = new OffersEngine();
    }

    public function create(array $input): array
    {
        $this->trace->info(TraceCode::OFFER_CREATE_REQUEST, $input);

        $merchant = $this->merchant;

        $resource = 'offer_create_' . $merchant->getId();

        return $this->mutex->acquireAndRelease(
            $resource,
            function () use ($input, $merchant)
            {
                return $this->repo->transaction(function () use ($input, $merchant)
                {
                    $offers_array = array();

                    // Check to find where it's either a nc or lc emi offer
                    if (isset($input[Entity::EMI_SUBVENTION]) and $input[Entity::EMI_SUBVENTION] == 1)
                    {
                        $offers_array = $this->createSubventedOffer($input, $offers_array, $merchant);
                    }
                    else
                    {
                        $offer = $this->createOffer($merchant, $input);

                        array_push($offers_array, $offer);
                    }
                    return $offers_array;

                });
            });
    }

    public function validateBulkOfferInput(array $input)
    {

        $input_offer = $input['offer'];

        $offer = new Entity;

        $offer->build($input_offer);

    }

    public function update(Entity $offer, array $input)
    {
        $merchant = $this->merchant;

        $this->verifyIdAndStripSignForLinkedOfferIds($input);

        $offer->setExternal(false);

        $offer->exists = true;

        $offer->edit($input);

        $this->repo->transaction(
            function () use ($offer, $input, $merchant)
            {

                $this->repo->saveOrFail($offer);

                $this->traceNonExistingIins($offer, $merchant);

                if ($this->shouldRouteToOffersEngine($merchant->getId(), Constants::CREATE_OFFER_DUAL_WRITE_EXP) === true) {

                    $this->offersEngine->update($offer, $input);

                }
            }
        );

        return $offer;
    }

    public function bulkDeactivateOffers(array $offerIds): array
    {
        $success = [];
        $failed = [];
        $response = [];

        if (count($offerIds) > 1500)
        {
            throw new Exception\BadRequestException(
                TraceCode::BAD_REQUEST_ONLY_1500_OFFERS_DEACTIVATE_IN_BULK);
        }

        foreach ($offerIds as $offerId) {

            try {

                $this->trace->info(

                    TraceCode::OFFER_DEACTIVATE,
                    [
                        'offer_id' => $offerId,
                    ]
                );

                // NOTE - not handling this as part of offers decomp as OE
                // expects merchant_id always but this has just offer_id in request
                // Even so, the update happens in OE as well so not an issue.
                $offer = $this->repo->offer->findByPublicId($offerId);

                $this->repo->transaction(
                    function () use ($offer) {
                        $offer->deactivate();

                        $this->repo->saveOrFail($offer);

                        if ($this->shouldRouteToOffersEngine($offer->getMerchantId(), Constants::CREATE_OFFER_DUAL_WRITE_EXP) === true) {

                            $this->offersEngine->update($offer, [Entity::ACTIVE => false]);

                        }

                    }
                );


                $success[] = $offer->getPublicId();

            }
            catch (\Exception $e) {

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
                'response' => $response,
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
                    'offer_id' => $offer->getPublicId(),
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

        foreach ($defaultOffers as $offer)
        {
            $offer = $this->validateDefaultOfferForMerchant($offer);

            if ($offer !== null)
            {
                array_push($applicableOffers, $offer);
            }
        }

        if (count($applicableOffers) > 0)
        {
            $defaultOffersBool = true;
        }

        return $defaultOffersBool;

    }
    public function getOrderEntityForPlatformOffer(Entity $offer, Payment\Entity $payment)
    {
        $order = $payment->order;

        if ($order === null and $offer->isPlatformOffer() === true)
        {
            $order = new Order\Entity();

            $order->setAmount($payment->getAmount());

            $order->setAttribute(Order\Entity::CURRENCY,$payment->getCurrency());
        }

        return $order;
    }

    public function validateOfferApplicableOnPayment(Entity $offer, Payment\Entity $payment, array $input)
    {
        $verbose = true;

        $checker = new Checker($offer, $verbose);

        $order = $this->getOrderEntityForPlatformOffer($offer,$payment);

        if ($order === null)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Offer ID");
        }

        if ($checker->checkApplicabilityForPayment($payment, $order) === false)
        {
            $this->trace->info(
                TraceCode::OFFER_NOT_APPLIED_ON_PAYMENT,
                [
                    'payment_id' => $payment->getId(),
                    'offer_id' => $offer->getId()
                ]);

            $this->lockDecrementCurrentOfferUsage($payment);

            //As offer is not applicable, dissociating it
            (new OffersEngine())->failOnOffersEngine($payment);

            if ($offer->shouldBlockPayment() === true)
            {
                $errorMessage = $offer->getErrorMessage();

                throw new Exception\BadRequestValidationFailureException($errorMessage);
            }

            $this->revertOfferPaymentInput($offer, $payment, $input);

        }

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_PAYMENT,
            [
                'payment_id' => $payment->getId(),
                'offer_id' => $offer->getId()
            ]);
    }

    public function revertOfferPaymentInput(Entity $offer, Payment\Entity $payment, array $input)
    {
        //In case of discounted offer where Rzp modifies the amount, if offer validations fails
        //and merchant does not want to block payment for that offer, setting the original order amount
        //again for payment amount.

        if (($offer->getOfferType() === Constants::INSTANT_OFFER) and ($input['order_amount'] !== null))
        {
            $payment->setAmount($input['order_amount']);

            $baseAmount = (new CurrencyCore())->getBaseAmount($input['order_amount'], $input['currency'], $payment->merchant->getCurrency());

            $payment->setBaseAmount($baseAmount);
        }

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
            $result = $this->shouldApplySharedOffer($sharedOffer, $directOffers, $merchantId);

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
        else if (($order->getProductType() !== ProductType::SUBSCRIPTION) and
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

        usort($offers, static function ($offer1, $offer2) use ($offerUsages)
        {
            // Sort Descending
            return $offerUsages[$offer2['id']] <=> $offerUsages[$offer1['id']];
        });

        foreach ($offers as &$offer)
        {
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
     * @param string[] $offerIds The list of offer ids whose usage needs to be calculated
     * @param string $merchantId The primary key of the merchant to whom these offers belong to
     * @param int $minCreatedAt The epoch timestamp post which data needs to be scanned
     *
     * @return array
     */
    public function getOffersUsage(array $offerIds, string $merchantId, int $minCreatedAt): array
    {
        if (empty($offerIds))
        {
            return [];
        }

        $offersUsage = $this->repo->payment->getOffersUsage($offerIds, $merchantId, $minCreatedAt);

        // Initialize offer ids which haven't been used yet with a zero (0).
        foreach ($offerIds as $id)
        {
            if (!array_key_exists($id, $offersUsage))
            {
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
        /**
         * This will check if any existing offer with
         * same emi duration exists. For example
         * existing offer has null emi_durations that
         * means all emi durations are valid. So any
         * new offer with same issuer and any emi duration like
         * 3 will fail
         */

        // Check to see if there are any offers with same values for the set of attributes
        // required to uniquely define an offer
        if ($offer->getEmiSubvention() === true)
        {
            // percent rate should not be used to check existing subvention offers, hence unsetting it
            $percent_rate = $offer[Entity::PERCENT_RATE];
            unset($offer[Entity::PERCENT_RATE]);

            $existingOffers = $this->repo->offer->fetchExistingOffers($offer, $this->merchant->getId());

            $offer[Entity::PERCENT_RATE] = $percent_rate;


            $existingDurations = [];

            $existingOffers->each(function ($existingOffer) use (& $existingDurations)
            {
                $existingOfferDuration = $existingOffer[Entity::EMI_DURATIONS] ?: Emi\Entity::VALID_DURATIONS;

                $existingDurations = array_merge($existingDurations, $existingOfferDuration);
            });

            $offerEmiDurations = $offer->getEmiDurations() ?: Emi\Entity::VALID_DURATIONS;

            if (empty(array_intersect($offerEmiDurations, $existingDurations)) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
            }
        }
        else {
            // for upi we are not doing any conflicting offer checks
            if ($offer->getPaymentMethod() === Payment\Method::UPI)
            {
                return;
            }

            $existingOffers = $this->repo->offer->fetchExistingOffers($offer, $this->merchant->getId());

            if ($existingOffers->count() > 0)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_ALREADY_EXISTS);
            }
        }
    }

    /**
     * @deprecated
     * Checks if the offer ids provided in linked_offer_ids are valid and also
     * removes public sign from them
     *
     * @param array $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function verifyIdAndStripSignForLinkedOfferIds(array &$input)
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

            // log only if there are non-existing iins
            if (count($nonExistingIins) > 0)
            {
                $this->trace->info(
                    TraceCode::OFFER_IIN_DOES_NOT_EXISTS,
                    [
                        'merchant_id' => $merchant->getId(),
                        'non_existing_iins' => array_values($nonExistingIins),
                    ]);
            }
        }
    }

    protected function validateMerchant(Merchant\Entity $merchant, array &$input)
    {
        if ($merchant->isShared() === true)
        {
            return;
        }

        if (empty($input[Entity::PAYMENT_METHOD]) === true)
        {
            return;
        }

        $merchantPaymentMethods = $merchant->methods;

        $method = $input[Entity::PAYMENT_METHOD];

        if ($merchantPaymentMethods->isMethodEnabled($method) === false)
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

            if ($checker->checkApplicabilityForPaymentBeforeCheckout($payment, $order))
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
        if ($offer !== null)
        {
            $offer = $this->repo->transaction(function () use ($offer)
            {
                $offer = $this->repo->offer->lockForUpdate($offer->getId());

                $offer->setCurrentUsageCount($offer->getCurrentOfferUsage() + 1);

                $this->repo->saveOrFail($offer);

                // not handling this as part of decomp reads as it is part of payment flow and involves usage updates
                return $this->repo->offer->findByPublicIdAndMerchant($offer->getPublicId(), $this->merchant);
            });

            return $offer;
        }
    }

    //decrement the offer usage count after failed payment for max offer validation.
    public function lockDecrementCurrentOfferUsage(Payment\Entity $payment)
    {
        $offer = $payment->getOffer();

        if ($offer !== null && $offer->getMaxOfferUsage() !== null)
        {
            $offer = $this->repo->transaction(function () use ($offer)
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
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, ['enabled' => true]);

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

        $offerId = Entity::verifyIdAndStripSign($input['offer']);
        $fetchActive = $input[SubscriptionOffer\Entity::ACTIVE] ?? true;
        $fetchExpired = $input[SubscriptionOffer\Entity::EXPIRED] ?? false;

        $offer = $this->repo->offer->fetchSubscriptionOfferById($offerId, $this->merchant->getId(), $fetchActive, $fetchExpired);

        $data = [
            SubscriptionOffer\Entity::DISCOUNTED_AMOUNT => (int)$input['amount'],
            SubscriptionOffer\Entity::ORIGINAL_AMOUNT => (int)$input['amount'],
            SubscriptionOffer\Entity::OFFER_VALID => 0,
            SubscriptionOffer\Entity::MESSAGE => null,
            SubscriptionOffer\Entity::OFFER_NAME => '',
            SubscriptionOffer\Entity::OFFER_DESC => '',
        ];

        if ($offer === null)
        {
            $data[SubscriptionOffer\Entity::MESSAGE] = 'Offer Not Found';

            return $data;
        }

        try {
            $data[SubscriptionOffer\Entity::OFFER_NAME] = $offer->getName();
            $data[SubscriptionOffer\Entity::OFFER_DESC] = $offer->getDisplayText();

            $data[SubscriptionOffer\Entity::DISCOUNTED_AMOUNT] = $offer->getDiscountedAmount($input['amount']);

            // Checking this separately as -
            // 1. there will be cases where payment id won't be present
            // 2. Don't need to have db calls when amount it self is not discountable
            if ($data[SubscriptionOffer\Entity::DISCOUNTED_AMOUNT] === $data[SubscriptionOffer\Entity::ORIGINAL_AMOUNT])
            {
                $data[SubscriptionOffer\Entity::MESSAGE] = PublicErrorDescription::OFFER_ORDER_AMOUNT_LESS_OFFER_MIN_AMOUNT;
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
        } catch (\Exception $e)
        {
            // Not an error for just the calculation, so printing in info
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION_NA,
                [
                    'input' => $input,
                    'reason' => $e->getMessage()
                ]
            );

            $data[SubscriptionOffer\Entity::MESSAGE] = $e->getMessage();
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
                    'offer_method' => $offer->getPaymentMethod()
                ]
            );
        }

        // NOTE - not handling this as part of offers decomp as this
        // involves reading current offer usage and validates on same
        $baseOffer = $this->repo->offer->findByPublicId(Entity::getSignedId($offerId));
        $checker = new Checker($baseOffer, false);

        $this->repo->beginTransactionAndRollback(
            function () use ($checker, $baseOffer, $payment, $input)
            {

                $orderInput = [
                    'amount' => $input['amount'],
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

    /**
     * @param Merchant\Entity $merchant
     * @param array $input
     * @param bool $isNcEmi
     * @return Entity
     * @throws BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    protected function createOffer(Merchant\Entity $merchant, array $input): Entity
    {
        $subscriptionInput = [];

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

        $this->repo->transaction(
          function () use (&$offer, $merchant, $subscriptionInput, $input)
          {
              $this->repo->saveOrFail($offer);

              if (empty($subscriptionInput) === false)
                {
                    // create entry in subscription_offers_master
                    $this->addSubscriptionData($offer, $subscriptionInput);
                }

                $this->traceNonExistingIins($offer, $merchant);

                if ($this->shouldRouteToOffersEngine($merchant->getId(), Constants::CREATE_OFFER_DUAL_WRITE_EXP) === true)
                {
                    $this->offersEngine->createOffer($offer, $subscriptionInput ?? [], $input);
                }
          }
        );
        return $offer;
    }

    public function bulkCalltoSplitz(string $merchantId): array{
        $result = [];

        try{
            $whitelistExperiments = [
                $this->app['config']->get(Constants::OFFERS_ENGINE_VALIDATE_OFFER_EXP) => Constants::OFFERS_ENGINE_VALIDATE_OFFER_EXP,
                $this->app['config']->get(Constants::OFFERS_ENGINE_REVERSE_SHADOW_EXP) => Constants::OFFERS_ENGINE_REVERSE_SHADOW_EXP,
            ];

            foreach ($whitelistExperiments as $experimentId => $instrument)
            {
                $result[$instrument] = false; // Default value

                $experimentsData[] = [
                    "id" => $merchantId,
                    "experiment_id" => $experimentId,
                    'request_data'  => json_encode(
                        [
                            'merchant_id' => $merchantId,
                        ]),
                ];
            }
            $experimentResponses = $this->app['splitzService']->bulkCallsToSplitz($experimentsData);

            foreach ($experimentResponses as $response)
            {
                $variables = $response['variant']['variables'];

                foreach ($variables as $variable)
                {
                    if ($variable['key'] == "enabled" && $variable['value'] == "true") {

                        $experimentId = $response['experiment']['id'];

                        if(array_key_exists($experimentId, $whitelistExperiments))
                        {
                            $result[$whitelistExperiments[$experimentId]] = true;
                        }
                    }
                }
            }

        }catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::OFFERS_ENGINE_ROUTING_SPLITZ_ERROR,
                [
                    'msg' => $e->getMessage()
                ]);
        }

        return $result;
    }

    public function shouldRouteToOffersEngine(string $merchantId, $experiment): bool
    {
        try
        {
            $properties = [
                "id"            => $merchantId,
                "experiment_id" => $this->app['config']->get($experiment),
                "request_data"  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === 'variant_on';
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::OFFERS_ENGINE_ROUTING_SPLITZ_ERROR,
                [
                    'msg' => $e->getMessage()
                ]);
        }

        return false;
    }

    /**
     * @param array $input
     * @param array $offers_array
     * @param Merchant\Entity $merchant
     * @return array
     */
    protected function createSubventedOffer(array $input, array $offers_array, Merchant\Entity $merchant): array
    {
        $exception = null;

        $success = 0;

        // both no cost and low cost requests are empty
        if (empty($input[Entity::EMI_DURATIONS]) === true && empty($input[Entity::LOW_COST_EMI]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_SUBVENTION_PARAMS);
        }

        // create NC EMI Offer
        if (empty($input[Entity::EMI_DURATIONS]) === false)
        {
            try
            {
                array_push($offers_array, $this->createOffer($merchant, $input));

                $success++;
            }
            catch (\Exception $e)
            {
                $error = new Error($e->getError()->getPublicErrorCode(), $e->getError()->getDescription(), null, null);

                array_push($offers_array, $error);

                $exception = $e;
            }
        }

        // low cost emi offer creation
        if (empty($input[Entity::LOW_COST_EMI]) === false)
        {

            $lc_emi_values = $input[Entity::LOW_COST_EMI];

            foreach ($lc_emi_values as $lc_emi)
            {

                $merchant_subvention = $lc_emi["discount_to_avail"]["discount_percentage"];

                $tenure = array($lc_emi["tenure"]);

                $input[Entity::EMI_DURATIONS] = $tenure;

                $input[Entity::PERCENT_RATE] = $merchant_subvention;

                try
                {
                    $lc_emi_offer = $this->createOffer($merchant, $input);

                    array_push($offers_array, $lc_emi_offer);

                    $success++;
                }
                catch (\Exception $e)
                {
                    $error = new Error($e->getError()->getPublicErrorCode(), $e->getError()->getDescription(), null, null);

                    array_push($offers_array, $error);

                    $exception = $e;
                }
            }
        }

        if ($success === 0)
        {
            throw $exception;
        }

        return $offers_array;
    }

    public function fetchCardIIN(Payment\Entity $payment): string
    {
        $card = $payment->card;

        $cardActualIin = null;

        $cardTokenIin = $card->getTokenIin();

        if (empty($cardTokenIin) === false)
        {
            $cardActualIin = (string)Card\IIN\IIN::getTransactingIinforRange($cardTokenIin);

            if (empty($cardActualIin) === true)
            {
                $this->trace->info(TraceCode::BIN_MAPPING_FOR_TOKEN_NOT_AVAILABLE);
            }
        }
        // not adding this in else condition because this check is needed even for tokenised cards flow after mapping fails.
        if (empty($cardActualIin) === true)
        {
            $cardActualIin = $card->getIin();
        }

        return $cardActualIin;
    }

    public function getParValue(Payment\Entity $payment, bool $dummyPayment) {

        $card = $payment->card;
        $vaultToken = $card->getVaultToken();
        $cardNumber = (new Card\CardVault)->getCardNumber($vaultToken);

        $cardInput = (new Token\Core())->buildCardInputForPar($cardNumber, $card);

        // Fetches par value for given card number
        list($network, $data) = (new Token\Core())->fetchParValue($cardInput, true);
        $providerReferenceId = $data["fingerprint"];


        $card->setProviderReferenceId($providerReferenceId);

        // For dummy payment we will not persist the card entity
        if ($dummyPayment === false)
        {
            $this->repo->card->saveOrFail($card);
        }
        return $providerReferenceId;
    }

    public function validateOnOffersEngine(bool $shouldValidateOnOffersEngine,
                                           Payment\Entity $payment, Order\Entity $order, Entity $offer, bool $isDummyPayment)
    {
        if ($shouldValidateOnOffersEngine === false)
        {
            return [
                Constants::VALIDATE_OFFER_CALLED => false,
            ];
        }

        $iin = '';

        // perform checks if we can call offers engine
        if ($payment->isMethodCardOrEmi() === true)
        {
            $iin = $this->fetchCardIIN($payment);
        }

        try
        {
            $oeResp = $this->offersEngine->validateOffer(
                $payment->getMerchantId(),
                $offer,
                $payment,
                $order,
                $isDummyPayment,
                $iin);

            return [
                Constants::VALIDATE_OFFER_RESPONSE => $oeResp,
                Constants::VALIDATE_OFFER_CALLED => true,
            ];
        }
        catch (\Throwable $e)
        {
            // do nothing
            return [
                Constants::VALIDATE_OFFER_CALLED => true,
            ];
        }
    }

    public function compareValidateOfferResponse($apiResp, $oeResp, Entity $offer)
    {
        $mismatch = true;

        // API VALIDATION CHECK PASSED, OE RESPONSE SHOULD HAVE OFFER_ID
        if ($apiResp === true)
        {
            if ($oeResp['offer_id'] === $offer->getPublicId())
            {
                // id matched, no mismatch
                $mismatch = false;
            }

        }
        // API VALIDATION CHECK FAILED, OE RESPONSE SHOULD HAVE ERROR
        else
        {
            if ($oeResp['error']['description'] === "No Active offers found")
            {
                // error desc matched, no mismatch
                $mismatch = false;
            }
        }

        if ($mismatch === true)
        {
            $this->trace->count(Metric::OFFERS_ENGINE_DISCOUNT_MISMATCH,
                [
                    'offer_type' => $offer->getOfferType(),
                    'emi_subvention' => $offer->getEmiSubvention(),
                ]);

            $this->trace->debug(TraceCode::VALIDATE_OFFER_RESPONSE_MISMATCH, [
                'API_RESPONSE' => $apiResp,
                'OE_RESPONSE' => $oeResp
            ]);
        }
    }
}
