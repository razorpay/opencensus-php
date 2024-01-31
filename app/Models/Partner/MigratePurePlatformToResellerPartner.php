<?php

namespace RZP\Models\Partner;

use Event;
use Throwable;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Referral;
use Razorpay\OAuth\Token as Token;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\PartnerMigrationAuditJob;
use Razorpay\OAuth\Application as OAuthApp;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Merchant\AccessMap as AccessMap;
use Illuminate\Contracts\Foundation\Application;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;

class MigratePurePlatformToResellerPartner extends Core
{
    /**
     * @var Application|mixed
     */
    private mixed $authService;

    public function __construct()
    {
        parent::__construct();

        $this->authService = app('authservice');
    }

    public function migrate(string $merchantId, array $actorDetails) : bool
    {
        $mutexKey = Constants::PURE_PLATFORM_TO_RESELLER_MIGRATE.$merchantId;

        return $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchantId, $actorDetails)
            {
                return $this->updatePurePlatformToReseller($merchantId, $actorDetails);
            },
            Constants::PURE_PLATFORM_TO_RESELLER_MIGRATE_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_PURE_PLATFORM_TO_RESELLER_MIGRATION_IN_PROGRESS
        );
    }

    /**
     * @throws LogicException
     * @throws Throwable
     */
    private function updatePurePlatformToReseller(string $merchantId, array $actorDetails)
    {
        $partner = $this->fetchPurePlatformPartner(
            $merchantId
        );
        if ($partner === null) return false;
        $oldPartnerType = $partner->getPartnerType();

        $result = $this->deleteAndCreateSupportingEntities($partner);

        if ($result === true)
        {
            $this->trace->info(TraceCode::MIGRATE_PURE_PLATFORM_TO_RESELLER_SUCCESS, ['merchant_id' => $partner->getId()]);
            $this->trace->count(Metric::PURE_PLATFORM_TO_RESELLER_MIGRATION, ['success' => true]);

            PartnerMigrationAuditJob::dispatch($merchantId, $actorDetails, $oldPartnerType);
        }
        else
        {
            $this->trace->info(
                TraceCode::PURE_PLATFORM_TO_RESELLER_MIGRATE_ERROR,
                ['merchant_id' => $partner->getId()]
            );
            $this->trace->count(Metric::PURE_PLATFORM_TO_RESELLER_MIGRATION, ['success' => false]);
        }
        return $result;
    }

    /**
     * @throws Throwable
     * @throws LogicException
     */
    private function deleteAndCreateSupportingEntities(Entity $partner) : bool
    {
        try
        {
            $existingAppIds = $this->repo->merchant_application->fetchMerchantApplications(
                $partner->getId(), [ MerchantApplicationsEntity::OAUTH ]
            )->pluck(MerchantApplicationsEntity::APPLICATION_ID)->toArray();
            if ($this->validateIfPartnerHasNoActiveToken($partner->getId()) === false)
            {
                $this->trace->error(TraceCode::PURE_PLATFORM_TO_RESELLER_ACTIVE_TOKEN_PRESENT_ERROR);
                return false;
            }

            list($configs, $accessMaps, $subMs) = $this->fetchAndValidatePurePlatformPartnerEntities($partner, $existingAppIds);
            $accessMaps = $accessMaps->groupBy(AccessMap\Entity::MERCHANT_ID);

            $this->repo->transactionOnLiveAndTestAndAsv(function () use (
                $partner, $existingAppIds, $configs, $accessMaps, $subMs
            ) {
                $partner->setPartnerType(MerchantConstants::RESELLER);
                $this->repo->merchant->saveOrFail($partner);

                $this->createEntitiesForReseller($partner, $configs , $accessMaps, $subMs);

                $this->notifyPartnerAboutSwitch($partner);
            });

            $this->deleteEntitiesForPurePlatformPartner($existingAppIds, $partner->getId());
        }
        catch (\Exception $exception)
        {
            $this->trace->error(TraceCode::PURE_PLATFORM_TO_RESELLER_MIGRATE_ERROR);
            $this->trace->count(
                Metric::PURE_PLATFORM_TO_RESELLER_MIGRATION,
                ['success' => false, 'code' => TraceCode::PURE_PLATFORM_TO_RESELLER_MIGRATE_ERROR]
            );
            throw $exception;
        }

        return true;
    }

    private function createEntitiesForReseller(
        Entity $partner, PublicCollection $configs, PublicCollection $accessMaps, PublicCollection $subMs
    ): void
    {
        $newReferredAppId = $this->createReferredAppAndSupportingEntities($partner);
        $this->updateOverriddenConfigsAndAccessMaps($configs, $accessMaps, $newReferredAppId);
        $this->appendRefTagsForSubMerchant($partner, $subMs);
        (new Referral\Core())->createOrFetch($partner);
        $this->updatePartnerActivation($partner);
    }

    private function notifyPartnerAboutSwitch($partner): void
    {
        \Event::dispatch(new TransactionalClosureEvent(function() use ($partner) {
            $partner = $partner->unsetRelations();

            (new NotifyPartnerAboutPartnerTypeSwitch(
                $partner,
                MerchantConstants::PURE_PLATFORM,
                MerchantConstants::RESELLER)
            )->notify();
        }));
    }

    private function updatePartnerActivation(Entity $partner)
    {
        \Event::dispatch(new TransactionalClosureEvent(function() use ($partner) {
            (new UpdatePartnerActivationContext($partner))->update();
        }));
    }

    /**
     * This method deletes the remaining access_map and partner_config entries,
     * and deletes oauth applications and webhooks for those applications
     *
     * @param array $existingAppIds Existing application IDs
     * @param string $partnerId     Partner MID
     *
     * @return void
     * @throws Throwable
     */
    private function deleteEntitiesForPurePlatformPartner(array $existingAppIds, string $partnerId): void
    {
        $partnerConfigs = $this->repo->partner_config->fetchAllConfigForApps($existingAppIds);
        $accessMaps = $this->repo->merchant_access_map->fetchAllMappingsByEntityIdAndEntityOwnerId($existingAppIds, $partnerId);
        $merchantApps = $this->repo->merchant_application->fetchMerchantApplicationByAppIds($existingAppIds);

        $this->repo->transactionOnLiveAndTest(function () use (
            $partnerId, $partnerConfigs, $accessMaps, $merchantApps, $existingAppIds
        ) {
            $partnerConfigs->each(function ($config) {
                $this->repo->partner_config->deleteOrFail($config);
            });
            $accessMaps->each(function ($accessMap) {
                $this->repo->merchant_access_map->deleteOrFail($accessMap);
            });
            $merchantApps->each(function ($merchantApp) {
                $this->repo->merchant_application->deleteOrFail($merchantApp);
            });

            \Event::dispatch(new TransactionalClosureEvent(function() use ($partnerId, $existingAppIds) {
                foreach ($existingAppIds as $existingAppId)
                {
                    app('authservice')->deleteApplication($existingAppId, $partnerId, false);
                    $this->deleteWebhooksForApplication($existingAppId);
                }
            }));
        });
    }

    /**
     * Updates applicationID for all overridden partner_configs and merchant_access_map with new $newReferredAppId.
     * Only the first linked partner_config and merchant_access_map are considered for updation,
     * as all other entries will get deleted.
     *
     * @param PublicCollection $configs
     * @param PublicCollection $accessMaps
     * @param string $newReferredAppId
     * @return void
     */
    private function updateOverriddenConfigsAndAccessMaps(
        PublicCollection $configs, PublicCollection $accessMaps, string $newReferredAppId
    ): void
    {
        $overriddenConfigs = $this->filterOverriddenConfigs($configs);
        $configsToUpdate = new PublicCollection();
        $accessMapsToUpdate = new PublicCollection();
        foreach ($accessMaps as $subMerchantAccessMaps)
        {
            $firstLinkedAccessMap = $subMerchantAccessMaps->sortBy(AccessMap\Entity::CREATED_AT)->first();
//            It is possible that a subM has default partner_config for 1st linked application,
//            which would result null for $firstLinkedConfig.
            $firstLinkedConfig = $overriddenConfigs->where(
                PartnerConfig\Entity::ORIGIN_ID, $firstLinkedAccessMap->getEntityId()
            )->first();

            $accessMapsToUpdate->push($firstLinkedAccessMap);
            if ($firstLinkedConfig !== null)
            {
                $configsToUpdate->push($firstLinkedConfig);
            }
        }
        $this->updateApplicationsForMerchantAccessMap($accessMapsToUpdate, $newReferredAppId);
        $this->updateApplicationsForOverriddenPartnerConfigs($configsToUpdate, $newReferredAppId);
    }

    private function createReferredAppAndSupportingEntities(Entity $partner) : string
    {
        $newReferredApp = $this->merchantCore->createPartnerApp(
            $partner, [OAuthApp\Entity::NAME => Entity::REFERRED_APPLICATION]
        );
        $newReferredAppId = $newReferredApp[OAuthApp\Entity::ID];
        $this->merchantCore->createMerchantApplication(
            $partner, $newReferredAppId, MerchantApplicationsEntity::REFERRED
        );
        $this->createPartnerConfig($newReferredAppId, $partner);

        return $newReferredAppId;
    }

    private function createPartnerConfig(string $applicationId, Entity $partner, array $config = [])
    {
        $defaultConfig = $this->getPartnerDefaultConfig($partner);

        $config = array_merge($defaultConfig, $config);
        $application = (new OAuthApp\Repository())->findOrFail($applicationId);

        (new PartnerConfig\Core)->create($application, $config);
    }

    private function appendRefTagsForSubMerchant(Entity $partner, PublicCollection $subMs): void
    {
        $subMs->each(function (Entity $subM) use ($partner) {
            $existingTags = $subM->tagNames();
            $refTag = MerchantConstants::PARTNER_REFERRAL_TAG_PREFIX . $partner->getId();

            if (in_array(strtolower($refTag), array_map('strtolower', $existingTags)) === true)
            {
                return;
            }

            $this->merchantCore->appendTag($subM, $refTag);
        });
    }
    private function filterOverriddenConfigs($configs) : PublicCollection
    {
        return $configs->where(PartnerConfig\Entity::ENTITY_TYPE, 'merchant')
                       ->where(PartnerConfig\Entity::ORIGIN_TYPE, 'application')
                       ->whereNotNull(PartnerConfig\Entity::ORIGIN_ID);
    }

    /**
     * @throws LogicException
     */
    private function fetchAndValidatePurePlatformPartnerEntities(Entity $partner, array $existingAppIds): array
    {
        $configs = $this->repo->partner_config->fetchAllConfigsInSyncOrFail($existingAppIds);
        $accessMaps = $this->repo->merchant_access_map->fetchAccessMapsInSyncOrFail(
            $existingAppIds, $partner->getId()
        );
        $merchantIds = $accessMaps->pluck(AccessMap\Entity::MERCHANT_ID)->unique()->toArray();
        $subMs = $this->repo->merchant->findManyOnReadReplica($merchantIds)->unique(Entity::ID);

        return [ $configs, $accessMaps, $subMs ];
    }

    public function updateApplicationsForMerchantAccessMap(PublicCollection $accessMaps, string $newAppId): void
    {
        foreach ($accessMaps as $accessMap)
        {
            $accessMap->setEntityId($newAppId);
        }

        $this->repo->saveOrFailCollection($accessMaps);
    }

    private function updateApplicationsForOverriddenPartnerConfigs(PublicCollection $configs, string $appId): void
    {
        foreach($configs as $config)
        {
            $config->setOriginId($appId);
            $config->setCommissionsEnabled(true);
        }

        $this->repo->saveOrFailCollection($configs);
    }

    private function validateIfPartnerHasNoActiveToken(string $partnerId) : bool
    {
        $tokens = $this->authService->getTokens([], $partnerId)['items'];
        if (empty($tokens) === true)
        {
            return true;
        }

        $now = Carbon::now('Asia/Kolkata')->timestamp;
        $activeTokens =  array_filter($tokens, function ($token) use ($now) {
            return (
                ($token[Token\Entity::EXPIRES_AT] > $now ) &&
                ($token[Token\Entity::REVOKED] !== true)
            );
        });

        if (empty($activeTokens) === false)
        {
            $activeAppIds = collect($activeTokens)->pluck(Token\Entity::APPLICATION_ID);
            $this->trace->info(
                TraceCode::PURE_PLATFORM_TO_RESELLER_ACTIVE_TOKEN_PRESENT,
                ['merchant_id' => $partnerId, 'application_ids' => $activeAppIds]
            );
            $this->trace->count(
                Metric::PURE_PLATFORM_TO_RESELLER_MIGRATION,
                [ 'success' => false, 'code' => TraceCode::PURE_PLATFORM_TO_RESELLER_UPDATE_INVALID_PARTNER]
            );

            return false;
        }

        return true;
    }

    private function fetchPurePlatformPartner(string $merchantId): ?Entity
    {
        $merchant = $this->repo->merchant->find($merchantId);
        if ($merchant === null || $merchant->isPurePlatformPartner() === false)
        {
            $this->trace->info(
                TraceCode::PURE_PLATFORM_TO_RESELLER_UPDATE_INVALID_PARTNER,
                ['merchant_id' => $merchantId]
            );
            $this->trace->count(
                Metric::PURE_PLATFORM_TO_RESELLER_MIGRATION,
                [ 'success' => false, 'code' => TraceCode::PURE_PLATFORM_TO_RESELLER_UPDATE_INVALID_PARTNER]
            );

            return null;
        }

        return $merchant;
    }
}
