<?php

namespace RZP\Models\Base;

use RZP\Models\Base\Entity as BaseEntity;
use RZP\Models\Base\Traits\InvalidatesAffordabilityCache;
use RZP\Models\Emi\Entity as EmiEntity;
use RZP\Models\Feature\Repository as FeatureRepository;
use RZP\Models\Key\Repository as KeyRepository;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Terminal\Status as TerminalStatus;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;
use RZP\Services\AffordabilityService;

/**
 * Observe events on entities which are a part of Razorpay's affordability suite.
 *
 * @package RZP\Models\Base
 */
final class AffordabilityObserver
{
    use InvalidatesAffordabilityCache;

    private $supportedEntities = [
        EmiEntity::class => 'handleEmi',
        OfferEntity::class => 'handleOffer',
        TerminalEntity::class => 'handleTerminal',
        MethodsEntity::class => 'handleMethods',
    ];

    /**
     * Create AffordabilityObserver instance.
     *
     * @param AffordabilityService $service
     * @param FeatureRepository $featureRepo
     * @param KeyRepository $keyRepo
     */
    public function __construct(AffordabilityService $service, FeatureRepository $featureRepo, KeyRepository $keyRepo)
    {
        $this->affordabilityService = $service;

        $this->featureRepository = $featureRepo;

        $this->keyRepository = $keyRepo;
    }

    /**
     * Handle the entity "created" event.
     *
     * @param BaseEntity $entity
     */
    public function created(BaseEntity $entity): void
    {
        $this->handle($entity);
    }

    /**
     * Handle the entity "updated" event.
     *
     * @param BaseEntity $entity
     */
    public function updated(BaseEntity $entity): void
    {
        $this->handle($entity);
    }

    /**
     * Handle the entity "deleted" event.
     *
     * @param BaseEntity $entity
     */
    public function deleted(BaseEntity $entity): void
    {
        $this->handle($entity);
    }

    /**
     * Handle the entity "restored" event.
     *
     * @param BaseEntity $entity
     */
    public function restored(BaseEntity $entity): void
    {
        $this->handle($entity);
    }

    /**
     * Handle the entity "force deleted" event.
     *
     * @param BaseEntity $entity
     */
    public function forceDeleted(BaseEntity $entity): void
    {
        $this->handle($entity);
    }

    /**
     * Handle the model events.
     *
     * @see handleEmi()
     * @see handleOffer()
     * @see handleTerminal()
     * @see handleMethods()
     *
     * @param $entity
     */
    private function handle($entity): void
    {
        foreach ($this->supportedEntities as $class => $method) {
            if ($entity instanceof $class) {
                $this->{$method}($entity);

                return;
            }
        }
    }

    /**
     * Handle the emi entity events.
     *
     * @param EmiEntity $emiPlan
     */
    private function handleEmi(EmiEntity $emiPlan): void
    {
        if ($emiPlan->isShared()) {
            $this->invalidateAffordabilityCacheForAllMerchants();

            return;
        }

        $this->invalidateAffordabilityCache($emiPlan->merchant);
    }


    /**
     * Handle the methods entity events.
     *
     * @param MethodsEntity $methods
     */
    private function handleMethods(MethodsEntity $methods): void
    {
        $this->invalidateAffordabilityCacheForEligibility($methods->merchant, false, true);
    }

    /**
     * Handle the offer entity events.
     *
     * @param OfferEntity $offer
     */
    private function handleOffer(OfferEntity $offer): void
    {
        $this->invalidateAffordabilityCache($offer->merchant);
    }

    /**
     * Handle the terminal entity events.
     *
     * @param TerminalEntity $terminal
     */
    private function handleTerminal(TerminalEntity $terminal): void
    {
        if (!$this->isAffordabilityTerminal($terminal) ||
            !in_array($terminal->getStatus(), [TerminalStatus::ACTIVATED, TerminalStatus::DEACTIVATED], true)
        ) {
            return;
        }

        if ($terminal->isShared()) {
            $this->invalidateAffordabilityCacheForAllMerchants();
            $this->invalidateAffordabilityCacheForEligibility($terminal->merchant, true, false, $terminal->getGateway());

            return;
        }

        $this->invalidateAffordabilityCache($terminal->merchant);
        $this->invalidateAffordabilityCacheForEligibility($terminal->merchant, true, false, $terminal->getGateway());
    }

    private function isAffordabilityTerminal(TerminalEntity $terminal): bool
    {
        return $terminal->isEmiEnabled() ||
            $terminal->isCardlessEmiEnabled() ||
            $terminal->isPayLaterEnabled();
    }
}
