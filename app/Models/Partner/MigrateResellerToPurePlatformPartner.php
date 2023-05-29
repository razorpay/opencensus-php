<?php

namespace RZP\Models\Partner;

use Event;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\PartnerMigrationAuditJob;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Merchant\Constants as MerchantConstants;

class MigrateResellerToPurePlatformPartner extends Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function migrate(string $merchantId, array $actorDetails) : bool
    {
        $mutexKey = MerchantConstants::RESELLER_TO_PURE_PLATFORM_MIGRATE.$merchantId;

        return $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchantId, $actorDetails)
            {
                return $this->updateResellerToPurePlatform($merchantId, $actorDetails);
            },
            MerchantConstants::RESELLER_TO_PURE_PLATFORM_MIGRATE_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_RESELLER_TO_PURE_PLATFORM_MIGRATION_IN_PROGRESS
        );
    }

    public function updateResellerToPurePlatform(string $merchantId, array $actorDetails): bool
    {
        $partner = $this->fetchResellerPartner(
            $merchantId,
            TraceCode::RESELLER_TO_PURE_PLATFORM_UPDATE_INVALID_PARTNER,
            Metric::RESELLER_TO_PURE_PLATFORM_MIGRATION_FAILURE
        );
        if ($partner === null) return false;
        $oldPartnerType = $partner->getPartnerType();

        $result = $this->deleteAndCreateSupportingEntities($partner);

        if ($result === true)
        {
            $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_PURE_PLATFORM_SUCCESS, ['merchant_id' => $partner->getId()]);
            $this->trace->count(Metric::RESELLER_TO_PURE_PLATFORM_MIGRATION_SUCCESS);

            PartnerMigrationAuditJob::dispatch($merchantId, $actorDetails, $oldPartnerType);
        }
        else
        {
            $this->trace->info(
                TraceCode::RESELLER_TO_PURE_PLATFORM_MIGRATE_ERROR,
                ['merchant_id' => $partner->getId()]
            );
        }
        return $result;
    }

    public function deleteAndCreateSupportingEntities(Entity $partner): bool
    {
        try
        {
            $existingAppId = $this->fetchApplicationIdForReseller($partner->getId());
            if ($existingAppId === null)
            {
                return false;
            }

            list($configs, $accessMaps, $subMs, $kyc_states) = $this->fetchResellerPartnerEntities(
                $existingAppId, $partner
            );

            $this->repo->transactionOnLiveAndTest(function () use (
                $partner, $existingAppId, $configs, $accessMaps, $subMs, $kyc_states
            ) {
                $this->deleteOldRelations($partner, $existingAppId, $configs, $accessMaps, $kyc_states);
                $this->removeRefTagsForSubMerchant($partner, $subMs);

                $partner->setPartnerType(MerchantConstants::PURE_PLATFORM);
                $this->repo->merchant->saveOrFail($partner);

                $this->notifyPartnerAboutSwitch($partner);
            });
        }
        catch (LogicException $e)
        {
            $this->trace->error(TraceCode::RESELLER_TO_PURE_PLATFORM_MIGRATE_ERROR);
            $this->trace->count(
                Metric::RESELLER_TO_PURE_PLATFORM_MIGRATION_FAILURE,
                ['code' => TraceCode::RESELLER_TO_PURE_PLATFORM_MIGRATE_ERROR]
            );
            throw $e;
        }

        return true;
    }

    private function fetchApplicationIdForReseller($partnerId): ?string
    {
        $applications = $this->repo->merchant_application->fetchMerchantAppInSyncOrFail($partnerId);
        if (count($applications) === 1)
        {
            return $applications[0]->getApplicationId();
        }

        $this->trace->info(
            TraceCode::RESELLER_TO_PURE_PLATFORM_UPDATE_INVALID_APPLICATIONS,
            [ 'application_ids' => $applications->getIds() ]
        );
        return null;
    }

    private function notifyPartnerAboutSwitch(Entity $partner)
    {
        \Event::dispatch(new TransactionalClosureEvent(function() use ($partner) {
            (new NotifyPartnerAboutPartnerTypeSwitch($partner))->notify();
        }));
    }

    private function removeRefTagsForSubMerchant(Entity $partner, PublicCollection $subMs): void
    {
        $subMs->each(function (Entity $subM) use ($partner) {
            $tag = MerchantConstants::PARTNER_REFERRAL_TAG_PREFIX . $partner->getId();

            $this->merchantCore->deleteTag($subM->getPublicId(), $tag);
        });
    }

    /**
     * Deletes old entities (application, partner_configs, partner_kyc_states, and merchant_access_maps)
     * related to Reseller partner.
     *
     * @param   Entity              $partner
     * @param   string              $existingAppId
     * @param   PublicCollection    $configs
     * @param   PublicCollection    $accessMaps
     * @param   PublicCollection    $kycStates
     *
     * @return  void
     */
    private function deleteOldRelations(
        Entity           $partner, string $existingAppId, PublicCollection $configs,
        PublicCollection $accessMaps, PublicCollection $kycStates
    )
    {
        $configs->each(function ($config) {
            $this->repo->partner_config->deleteOrFail($config);
        });
        $kycStates->each(function ($kycState) {
            $this->repo->partner_kyc_access_state->deleteOrFail($kycState);
        });
        $accessMaps->each(function ($accessMap) {
            $this->repo->merchant_access_map->deleteOrFail($accessMap);
        });

        app('authservice')->deleteApplication($existingAppId, $partner->getId());
    }

    /**
     * Fetches partner related entities (partner configs, access maps, sub-merchants and kyc states) on live,
     * and returns them.
     *
     * @param   string      $existingAppId      the application ID of partner
     * @param   Entity      $merchant           the reseller partner's MerchantID
     *
     * @return  array       An associative array containing default partner configs, access maps, and sub-merchants
     *
     * @throws  LogicException
     */
    private function fetchResellerPartnerEntities(string $existingAppId, Entity $merchant) : array
    {
        $configs = $this->repo->partner_config->fetchAllConfigForApps([$existingAppId]);
        $accessMaps = $this->repo->merchant_access_map->getAllMappingsByEntityIdAndEntityOwnerId(
            $existingAppId, $merchant->getId()
        );
        $subMs = $this->repo->merchant->getSubMerchantsForPartnerAndApplication($existingAppId, $merchant->getId());
        $kycStates = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityIds($merchant->getId(), $subMs->getIds());

        return [ $configs, $accessMaps, $subMs, $kycStates ];
    }
}
