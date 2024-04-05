<?php

namespace RZP\Models\Base\Traits;

use App;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Offer\Constants;
use RZP\Models\Offer\Metric;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant;
use RZP\Models\Offer\SubscriptionOffer\Entity as SubscriptionOfferEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;

trait ExternalOffersRepo
{
    use ExternalFetch;

    protected $entityName;

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            try
            {
                // add prefix to id
                $offer = $this->fetchExternalEntity('offer_' . $id, $merchantId);

                // fetch offer from API if it has limits
                return $this->fetchOffersWithLimitsFromAPI([$offer])[0];
            } catch (\Exception $e)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_BY_ID_FAIL);

                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_BY_ID_FAIL, [
                ]);
            }
        }
        // if experiment is false or exception caught, calls parent repo function but the offer response does not change
        return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
    }

    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        return $this->findByIdAndMerchantId($id, $merchant->getId(), $connectionType);
    }

    public function findByPublicIdAndMerchant(
        string $publicId,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $id = OfferEntity::verifyIdAndStripSign($publicId);

        return $this->findByIdAndMerchantId($id, $merchant->getId(), $connectionType);
    }

    public function findManyFromOE(array $offerIds, $merchantId)
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            try
            {
                $offers = $this->fetchExternalEntitiesBulk($merchantId, $offerIds);

                if (empty($offers) === true)
                {
                    throw new Exception\ServerErrorException('No offers found in OE response', ErrorCode::SERVER_ERROR_OFFERS_ENGINE_MISSING_OFFERS);
                }

                // fetch offers with limits from API db (if any)
                $updatedOffers = $this->fetchOffersWithLimitsFromAPI($offers);

                if (sizeof($offerIds) !== sizeof($updatedOffers))
                {
                    $fallbackOffers = $this->fetchRemainingFromAPI($offerIds, $updatedOffers);
                    $updatedOffers = array_merge($updatedOffers, $fallbackOffers);
                }

                return $updatedOffers;

            } catch (\Exception $exception)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_OFFERS_FAIL);

                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_OFFERS_FAIL, [
                ]);
            }
        }

        $offers = [];
        // experiment false, fetch from API db
        foreach ($offerIds as $offerId) {
            $id = OfferEntity::verifyIdAndSilentlyStripSign($offerId);

            $offers[] = parent::findOrFail($id);
        }

        return $offers;
    }

    private function fetchSubscriptionOfferByIdFromOE(string $offerId, string $merchantId, bool $fetchActive = true, bool $fetchExpired = false)
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            try
            {
                // initialise offer variable to array as we receive Offer
                // entity from gateway, but we want an array in the caller
                $offer = [];

                // add prefix when calling OE
                $responseOffer = $this->fetchExternalEntity('offer_' . $offerId, $merchantId);

                if (empty($offer) === true)
                {
                    return [];
                }

                // fetch offer from API if it has limits
                $offer = $this->fetchOffersWithLimitsFromAPI([$responseOffer])[0];

                // filter based on params
                if ($fetchActive === true && !$offer[OfferEntity::ACTIVE])
                {
                    return [];
                }
                if ($fetchExpired === false && ($offer[OfferEntity::ENDS_AT] < Carbon::now()->getTimestamp()))
                {
                    return [];
                }

                // set the subscription fields in main offer array
                $offer[SubscriptionOfferEntity::APPLICABLE_ON] = $offer[Constants::SUBSCRIPTION_FIELDS][SubscriptionOfferEntity::APPLICABLE_ON];
                $offer[SubscriptionOfferEntity::NO_OF_CYCLES] = $offer[Constants::SUBSCRIPTION_FIELDS][SubscriptionOfferEntity::NO_OF_CYCLES];
                $offer[SubscriptionOfferEntity::REDEMPTION_TYPE] = $offer[Constants::SUBSCRIPTION_FIELDS][SubscriptionOfferEntity::REDEMPTION_TYPE];

                return $offer;

            } catch (\Exception $exception)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFER_BY_ID_FAIL);

                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFER_BY_ID_FAIL, [
                ]);
            }
        }

        return [];
    }

    private function fetchAllActiveNonSubscriptionOffersFromOE(string $merchantId) : ?Base\PublicCollection
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            $response = new Base\PublicCollection();
            try
            {
                $responseOffers = $this->fetchExternalEntitiesBulk($merchantId, [], [
                    Constants::STATE => Constants::STATE_CREATED,
                ]);

                if (empty($responseOffers) === true)
                {
                    return null;
                }

                // fetch offers from API if it has limits
                $offers = $this->fetchOffersWithLimitsFromAPI($responseOffers);

                $now = Carbon::now()->getTimestamp();

                foreach ($offers as $offer)
                {
                    // filter active non subscription offers
                    if ($offer[OfferEntity::STARTS_AT] <= $now &&
                        $offer[OfferEntity::ENDS_AT] > $now &&
                        empty($offer[Constants::SUBSCRIPTION_FIELDS]))
                    {
                        $response->push($offer);
                    }
                }

                return $response;

            } catch (\Exception $exception)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_ACTIVE_NONSUBSCRIPTION_OFFERS_FAIL);

                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_ACTIVE_NONSUBSCRIPTION_OFFERS_FAIL, [
                ]);
            }
        }
        return null;
    }

    private function fetchOffersSubscriptionFromOE($paymentMethods, $offerId, $merchantId): ?Base\PublicCollection
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            $applicableOffers = new PublicCollection();

            try
            {
                $responseOffers = $this->fetchExternalEntitiesBulk($merchantId, [],
                    [
                        Constants::STATE => Constants::STATE_CREATED,
                    ]);

                if (empty($responseOffers) === true)
                {
                    return $applicableOffers;
                }

                // fetch offers from API if it has limits
                $offers = $this->fetchOffersWithLimitsFromAPI($responseOffers);

                $now = Carbon::now()->getTimestamp();
                foreach ($offers as $offer)
                {
                    // filter active subscription offers
                    if ($offer[OfferEntity::STARTS_AT] <= $now &&
                        $offer[OfferEntity::ENDS_AT] > $now &&
                        !empty($offer[Constants::SUBSCRIPTION_FIELDS]))
                    {
                        $filter = true;

                        // filter based on input params
                        if ($paymentMethods !== null &&
                            !in_array($offer[OfferEntity::PAYMENT_METHOD], $paymentMethods))
                        {
                            $filter = false;
                        }

                        if ($offerId !== null &&
                            $offer[OfferEntity::ID] !== $offerId)
                        {
                            $filter = false;
                        }

                        if ($filter === true)
                        {
                            $applicableOffers->push($offer);
                        }
                    }
                }

                return $applicableOffers;

            }
            catch (\Exception $exception)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFERS_FAIL);
                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_SUBSCRIPTION_OFFERS_FAIL, [
                ]);
                }
            }

        return null;
    }

    private function fetchAllDefaultOffersForMerchantFromOE($merchantId)
    {
        if ($this->fetchFromOE($merchantId) === true)
        {
            try
            {
                $responseOffers = $this->fetchExternalEntitiesBulk($merchantId, [],
                    [
                        Constants::OFFER_TYPE => Constants::OFFER_TYPE_STAGE_REGULAR,
                    ]
                );

                if (empty($responseOffers) === true)
                {
                    return [];
                }

                // fetch offers from API if it has limits
                return $this->fetchOffersWithLimitsFromAPI($responseOffers);


            } catch (\Exception $exception)
            {
                $this->trace->count(Metric::OFFERS_ENGINE_FETCH_DEFAULT_OFFERS_FAIL);
                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::OFFERS_ENGINE_FETCH_DEFAULT_OFFERS_FAIL, [
                ]);
            }
        }

        return [];
    }

    private function fetchExternalEntitiesBulk($merchantId, array $ids = [], $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            return $class->fetchBulk($merchantId, $ids, $input);

        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);
        }

        $data = [
            'model' => 'offer',
            'attributes' => [
                'merchant_id' => $merchantId,
                'ids' => $ids,
                'input' => $input,
            ],
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY, null, $data);
    }

    private function validateExternalFetchEnabled()
    {
        if (app()->runningUnitTests() === true) {
            $keyName = Entity::getExternalConfigKeyName('offer');
            return (bool)ConfigKey::get($keyName, false);
        }

        return true;
    }

    private function fetchFromOE(string $merchantId): bool
    {
        return ($this->validateExternalFetchEnabled() === true)
        && $this->core->shouldRouteToOffersEngine($merchantId, Constants::OFFERS_ENGINE_FETCH_EXP) === true;
    }

    private function fetchRemainingFromAPI(array $offerIds, $offerEngineOffers)
    {
        $notFoundOffers = [];

        foreach($offerIds as $id)
        {
            $found = false;
            foreach ($offerEngineOffers as $offer)
            {
                if ($id === $offer->getId())
                {
                    $found = true;
                    break;
                }
            }
            if (!$found)
            {
                $notFoundOffers[] =  parent::findOrFail($id);;
            }
        }
        return $notFoundOffers;

    }

    private function fetchOffersWithLimitsFromAPI($offers)
    {
        $offersWithLimits = [];
        $updatedOffers = [];

        // get offers with limits if any
        foreach ($offers as $offer)
        {
            if (empty($offer) === true)
            {
                continue;
            }

            if (isset($offer[OfferEntity::MAX_OFFER_USAGE]) === true)
            {
                $offersWithLimits[] = $offer->getId();
            }
        }

        // fetch offers with usage limits from API db
        foreach ($offersWithLimits as $offerId)
        {
            $updatedOffers[] = parent::findOrFail($offerId);
        }

        // append remaining offers normally
        foreach ($offers as $offer)
        {
            // if offer_id is not present in array it returns false
            if (array_search($offer->getId(), $offersWithLimits) === false)
            {
                $updatedOffers[] = $offer;
            }
        }

        $this->traceAPIFallbackFetch($updatedOffers);

        return $updatedOffers;
    }

    public function traceAPIFallbackFetch($offers)
    {
        $fallbackOffers = [];
        foreach ($offers as $offer)
        {
            if ($offer->isExternal() === false)
            {
                $fallbackOffers[] = $offer->getId();
            }
        }

        if (count($fallbackOffers) > 0) {
            $this->trace->info(
                TraceCode::OFFERS_ENGINE_FETCH_FALLBACK,
                [
                    "offer_ids" => $fallbackOffers,
                    "fallback_offer_count"      => count($fallbackOffers),
                ]
            );
        }
    }
}

