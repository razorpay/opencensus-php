<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;
use Config;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\OAuthScopes;
use RZP\Models\Merchant\MerchantApplications;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Application;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $entityOwner,
        Merchant\Entity $merchant,
        array $input = null,
        Base\PublicEntity $entity = null)
    {
        $merchantMapping = (new Entity)->build($input);

        $merchantMapping->generateId();

        $merchantMapping->merchant()->associate($merchant);

        $merchantMapping->entityOwner()->associate($entityOwner);

        if (empty($entity) === false)
        {
            $merchantMapping->entity()->associate($entity);
        }

        $applicationType = $this->getMerchantApplicationType($merchantMapping);

        $this->repo->transaction(function () use ($merchantMapping, $applicationType) {
            $this->repo->saveOrFail($merchantMapping);

            $this->createOutboxJob("create_impersonation_grant", $merchantMapping, $applicationType);
        });

        return $merchantMapping;
    }

    private function getMerchantApplicationType($merchantMapping)
    {
        $oauthApplicationId = $merchantMapping->entity->getId();
        $merchantApplications = $this->repo
            ->merchant_application
            ->fetchMerchantApplication($oauthApplicationId, MerchantApplications\Entity::APPLICATION_ID);

        if ($merchantApplications->count() === 0)
        {
            throw new Exception\LogicException('merchant application missing. This should not have happened.');
        }


        return $merchantApplications->get(0)->getApplicationType();
    }

    /**
     * Partners with managed merchant_application can send request on behalf of sub-merchant.
     * Here we create outbox entry to POST merchant access map in edge for authentication.
     * While, table here will act as source of truth.
     * @param string $jobName
     * @param Entity $merchantMapping
     * @param string $applicationType
     */
    public function createOutboxJob(string $jobName, Entity $merchantMapping, string $applicationType)
    {
        if ($applicationType === MerchantApplications\Entity::MANAGED)
        {
            app('outbox')->send($jobName, [
                "principal_type"    => Merchant\Constants::PARTNER,
                "principal_id"      => $merchantMapping->getEntityOwnerId(),
                "subordinate_type"  => Constants\Entity::MERCHANT,
                "subordinate_id"    => $merchantMapping->getMerchantId(),
            ]);
        }
    }

    /**
     * Here we add the mapping between merchant and application. We
     * maintain this mapping so that we can run flows like webhook calls based
     * on this relation. This can be otherwise fetched from auth-service but
     * since it is read-heavy, we maintain it in the access_map table too.
     *
     * @param Merchant\Entity $entityOwner
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function addMappingForOAuthApp(Merchant\Entity $entityOwner, Merchant\Entity $merchant, array $input): Entity
    {
        $merchantId = $merchant->getId();

        $accessMapping = $this->repo
                              ->merchant_access_map
                              ->findMerchantAccessMapOnEntityId(
                                  $merchantId,
                                  $input[Entity::APPLICATION_ID],
                                  Entity::APPLICATION
                              );

        if ($accessMapping !== null)
        {
            return $accessMapping;
        }

        $data = [
            Entity::ENTITY_TYPE => Entity::APPLICATION,
            Entity::ENTITY_ID   => $input[Entity::APPLICATION_ID],
        ];

        return $this->create($entityOwner, $merchant, $data);
    }

    /**
     * Here we delete the mapping between merchant and application. We
     * delete this mapping when the last of the access tokens given to this
     * app for the given merchant is revoked. This check for number of tokens
     * is handled by the auth-service.
     *
     * @param Merchant\Entity $merchant
     * @param string          $appId
     */
    public function deleteMappingForOAuthApp(Merchant\Entity $merchant, string $appId)
    {
        $merchantId = $merchant->getId();

        $mapping = $this->repo
                        ->merchant_access_map
                        ->findMerchantAccessMapOnEntityId(
                            $merchantId,
                            $appId,
                            Entity::APPLICATION
                        );

        if (empty($mapping) === false)
        {
            $applicationType = $this->getMerchantApplicationType($mapping);

            return $this->repo->transaction(function () use ($mapping, $applicationType)
                {
                    $this->createOutboxJob("delete_impersonation_grant", $mapping, $applicationType);

                    return $this->repo->merchant_access_map->deleteOrFail($mapping);
                });
        }
    }

    /**
     * To delete access map entries when oauth application is getting deleted
     *
     * @param string $appId
     */
    public function deleteAccessMapByApplicationId(string $appId)
    {
        $accessMaps  = $this->repo->merchant_access_map->fetchMerchantAccessMapOnEntity(Entity::APPLICATION, $appId);

        $subMerchantIds = $accessMaps->pluck(Entity::MERCHANT_ID)->toArray();

        $this->trace->info(
            TraceCode::PARTNER_ACCESS_MAPS_DELETE,
            [
                'submerchant_ids' => $subMerchantIds,
            ]
        );

        foreach ($accessMaps as $accessMap)
        {
            $this->repo->deleteOrFail($accessMap);
        }
    }

    /**
     * Gets all active oauth tokens across merchants and updates the
     * merchant_access_map accordingly. Needed for one time migrations
     * in case there are anomalies due to bugs.
     *
     * @return array
     */
    public function updateMapFromTokens()
    {
        $batch = 500;

        $skip = 0;

        $count = 500;

        $failed = 0;
        $failedIds = [];
        $succeeded = 0;
        $processed = 0;

        while ($batch === $count)
        {
            $mappings = (new Token\Repository)->fetchActiveTokensWithAppAndCreatedAt($batch, $skip);

            $count = $mappings->count();

            $skip += $count;

            $mappings = $mappings->unique(function ($item) {
                return $item->merchant_id.$item->app_id;
            });

            $mappings = $mappings->values()->all();

            $this->trace->info(
                TraceCode::ACCESS_MAP_UPDATE_REQUEST,
                [
                    'total_tokens' => count($mappings)
                ]);

            foreach ($mappings as $mapping)
            {
                $appId      = $mapping->app_id;
                $merchantId = $mapping->merchant_id;
                $partnerId  = $mapping->partner_id;
                $createdAt  = $mapping->created_at;

                $traceData = [
                    Entity::APPLICATION_ID  => $appId,
                    Entity::MERCHANT_ID     => $merchantId,
                    Entity::ENTITY_OWNER_ID => $partnerId,
                ];

                $this->trace->info(TraceCode::ACCESS_MAP_UPDATE_REQUEST, $traceData);

                try
                {
                    $this->processMigration($appId, $merchantId, $partnerId, $createdAt);

                    $succeeded++;
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::ACCESS_MAP_UPDATE_ERROR,
                        $traceData
                    );

                    $failed++;

                    $failedIds[] = $merchantId . '.' . $appId;
                }

                $processed++;
            }
        }

        return [
            'success' => $succeeded,
            'failure' => $failed,
            'total'   => $processed,
            'failed'  => $failedIds
        ];
    }

    protected function processMigration(string $appId, string $merchantId, string $partnerId, int $createdAt)
    {
        $mapping = DB::table(Table::MERCHANT_ACCESS_MAP)
                       ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                       ->where(Entity::ENTITY_ID, $appId)
                       ->where(Entity::MERCHANT_ID, $merchantId)
                       ->whereNull(Entity::DELETED_AT)
                       ->first();

        if (empty($mapping) === true)
        {
            $id = (new Entity)->generateUniqueIdFromTimestamp($createdAt);

            DB::table(Table::MERCHANT_ACCESS_MAP)->insert(
                [
                    Entity::ID              => $id,
                    Entity::ENTITY_TYPE     => Entity::APPLICATION,
                    Entity::ENTITY_ID       => $appId,
                    Entity::MERCHANT_ID     => $merchantId,
                    Entity::ENTITY_OWNER_ID => $partnerId,
                    Entity::CREATED_AT      => $createdAt,
                    Entity::UPDATED_AT      => $createdAt
                ]
            );
        }
    }

    public function getMerchantAppMapping(Merchant\Entity $merchant, Application\Entity $app)
    {
        $accessMap = $this->repo
                          ->merchant_access_map
                          ->findMerchantAccessMapOnEntityId($merchant->getId(), $app->getId(), Entity::APPLICATION);

        return $accessMap;
    }

    /**
     * Returns the internal partner oauth app associated with the submerchant.
     * Since we are querying for only non pure-platform partners here, at most one merchant access map should exist.
     *
     * @param Merchant\Entity $subMerchant
     *
     * @return mixed
     */
    public function getNonPurePlatformPartnerApp(Merchant\Entity $subMerchant)
    {
        //
        // Check if a reseller / aggregator / bank / fully managed partner exists for the submerchant
        // and fetch the internal OAuth application linked to the partner merchant account.
        //
        $accessMap = $this->repo
                          ->merchant_access_map
                          ->getNonPurePlatformPartnerMapping($subMerchant->getId());

        $partnerApp = optional($accessMap)->entity;

        return $partnerApp;
    }

    /**
     * Returns the internal partner referred oauth app associated with the submerchant
     *
     * @param Merchant\Entity $subMerchant
     *
     * @return mixed
     */
    public function getReferredAppOfSubmerchant(Merchant\Entity $subMerchant)
    {
        $accessMaps = $this->repo
                          ->merchant_access_map
                          ->getMappingByApplicationType($subMerchant->getId(), MerchantApplications\Entity::REFERRED);

        $accessMap = $accessMaps->first();

        $partnerApp = optional($accessMap)->entity;

        return $partnerApp;
    }

    /**
     * @param Merchant\Entity    $merchant
     * @param Application\Entity $app
     *
     * @return bool
     */
    public function isMerchantMappedToApplication(Merchant\Entity $merchant, Application\Entity $app) : bool
    {
        $accessMap = $this->getMerchantAppMapping($merchant, $app);

        return (empty($accessMap) === false);
    }

    /**
     * @param Merchant\Entity    $merchant
     * @param Application\Entity $app
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantMappedToApplication(Merchant\Entity $merchant, Application\Entity $app)
    {
        $isMapped = $this->isMerchantMappedToApplication($merchant, $app);

        if ($isMapped === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                null,
                [
                    'submerchant_id' => $merchant->getId(),
                    'application_id' => $app->getId(),
                ]);
        }
    }

    /**
     * @param Merchant\Entity $partner
     * @param Merchant\Entity $merchant
     * @param string $appType
     *
     * @return bool
     */
    public function isMerchantMappedToPartnerWithAppType(Merchant\Entity $partner, Merchant\Entity $merchant, string $appType) : bool
    {
        $appIds = (new MerchantApplications\Core)->getMerchantAppIds($partner->getId(), [$appType]);

        $mappings = $this->repo->merchant_access_map->findMerchantAccessMapOnEntityIds($merchant->getId(), $appIds, Entity::APPLICATION);

        return ($mappings->isEmpty() === false);
    }

    public function getConnectedApplications(string $merchantId, array $input)
    {
        (new Validator)->validateInput('connected_applications', $input);

        $accessMaps =  $this->repo->merchant_access_map->fetchMerchantAccessMapsOnEntityType($merchantId, 'application');

        $service = $input['service'] ?? null;

        if (empty($service) === true)
        {
            return $accessMaps;
        }

        $appIds = $accessMaps->pluck(Entity::ENTITY_ID);
        $scopes = OAuthScopes::getOauthScopesByServiceOwner($service);
        $finalAppIds = [];

        foreach ($appIds as $appId)
        {
            $tokens = (new Token\Repository)->fetchAccessTokensByAppAndMerchant($appId, $merchantId);

            // if tokens are empty, the merchant is connected to a non-pure platform partner
            if ($tokens->isEmpty() === true)
            {
                $finalAppIds[] = $appId;
                continue;
            }

            // filter token for revoked and having scopes as per the service owner
            $tokens = $tokens->filter(function (Token\Entity $token) use ($scopes) {
                $isRevoked = $token->isRevoked();
                $requiredScopes = array_intersect($scopes, $token->getScopes());

                return (($isRevoked === false) and (empty($requiredScopes) === false));
            });

            if ($tokens->isEmpty() === false)
            {
                $finalAppIds[] = $appId;
            }
        }

        if (empty($finalAppIds) === true)
        {
            return new Base\PublicCollection;
        }

        return $this->repo->merchant_access_map->findMerchantAccessMapOnEntityIds($merchantId, $finalAppIds, 'application');
    }

    public function isSubMerchant(string $merchantId)
    {
        $merchant = $this->repo->merchant_access_map->getByMerchantId($merchantId);

        return empty($merchant) === false;
    }
}
