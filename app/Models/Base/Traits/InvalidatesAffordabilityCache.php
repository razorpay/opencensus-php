<?php

namespace RZP\Models\Base\Traits;

use RZP\Models\Feature\Constants as Features;
use RZP\Models\Feature\Repository as FeatureRepository;
use RZP\Models\Key\Repository as KeyRepository;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\AffordabilityService;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

trait InvalidatesAffordabilityCache
{
    /** @var AffordabilityService */
    protected $affordabilityService;

    /** @var FeatureRepository */
    protected $featureRepository;

    /** @var KeyRepository */
    protected $keyRepository;

    /**
     * Invalidate/Expire the cache of checkout-affordability-api for the given merchant.
     *
     * @param MerchantEntity|null $merchant
     *
     * @return bool
     */
    public function invalidateAffordabilityCache(?MerchantEntity $merchant): bool
    {
        if ($merchant === null) {
            // $merchant in null during tests where emi, offer & terminal entities
            // are created from fixtures but their merchant is never created.
            return true;
        }

        if ($merchant->getId() === Account::SHARED_ACCOUNT) {
            return $this->invalidateAffordabilityCacheForAllMerchants();
        }

        if (!$merchant->isAtLeastOneFeatureEnabled([Features::AFFORDABILITY_WIDGET,
                                                    DcsConstants::AffordabilityWidgetSet,])) {
            // Ignore if feature not enabled
            return true;
        }

        return $this->affordabilityService->invalidateCache($this->getKeys([$merchant->getId()]));
    }

    public function invalidateAffordabilityCacheForEligibility(?MerchantEntity $merchant,bool $InvalidateTerminalCache = false, bool $InvalidateMerchantMethodsCache = false, string $terminalMethod = null): bool
    {

        if ($merchant === null) {
            // $merchant in null during tests where method entities
            // are created from fixtures but their merchant is never created.
            return true;
        }

        if (!($merchant->isFeatureEnabled(DcsConstants::EligibilityEnabled)) && ($merchant->isFeatureEnabled(
                DcsConstants::EligibilityCheckDecline)) ) {
            // Ignore if feature not enabled
            return true;
        }


        return $this->affordabilityService->invalidateCache([], $merchant->getId(), $InvalidateTerminalCache, $InvalidateMerchantMethodsCache, $terminalMethod);
    }

    /**
     * Flush checkout-affordability-api cache for all merchants if changes are made at global level.
     *
     * @return bool
     */
    public function invalidateAffordabilityCacheForAllMerchants(): bool
    {
        $merchantIds = $this->getAllAffordabilityWidgetFeatureEnabledMerchantIds();

        if (empty($merchantIds)) {
            return true;
        }

        return $this->affordabilityService->invalidateCache($this->getKeys($merchantIds));
    }

    /**
     * Get list of merchantIds which have the 'affordability_widget' feature enabled.
     *
     * @return string[]
     */
    protected function getAllAffordabilityWidgetFeatureEnabledMerchantIds(): array
    {
        return $this->featureRepository->findMerchantIdsHavingFeatures([Features::AFFORDABILITY_WIDGET,
                                                                        DcsConstants::AffordabilityWidgetSet,]);
    }

    /**
     * Get the public keys for the given list of merchant ids.
     *
     * @param string[] $merchantIds
     *
     * @return string[]
     */
    protected function getKeys(array $merchantIds): array
    {
        $result = $this->keyRepository->getActiveKeysForMerchants($merchantIds);
        $keys = [];

        foreach ($result as $key) {
            $keys[] = $key->getPublicKey();
        }

        return $keys;
    }
}
