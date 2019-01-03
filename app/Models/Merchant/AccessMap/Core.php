<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use Razorpay\OAuth\Token;
use Razorpay\Trace\Logger as Trace;
use Razorpay\OAuth\Application as OAuthApp;

class Core extends Base\Core
{
    public function create(
        $partnerMerchant,
        Merchant\Entity $merchant,
        array $input = null,
        Base\PublicEntity $entity = null)
    {
        $merchantMapping = (new Entity)->build($input);

        $merchantMapping->generateId();

        $merchantMapping->merchant()->associate($merchant);
        if(empty($partnerMerchant) === false)
        {
            $merchantMapping->entityOwner()->associate($partnerMerchant);
        }

        if (empty($entity) === false)
        {
            $merchantMapping->entity()->associate($entity);
        }

        $this->repo->saveOrFail($merchantMapping);

        return $merchantMapping;
    }

    /**
     * Here we add the mapping between merchant and application  We
     * maintain this mapping so that we can run flows like webhook calls based
     * on this relation. This can be otherwise fetched from auth-service but
     * since it is read-heavy, we maintain it in the access_map table too.
     *
     * @param Merchant/Entity $aggregateMerchant can be null
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function addMappingForOAuthApp($aggregateMerchant, Merchant\Entity $merchant, array $input): Entity
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

        return $this->create($aggregateMerchant, $merchant, $data);
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
            return $this->repo->merchant_access_map->deleteOrFail($mapping);
        }
    }

    /**
    * Gets all rows from merchant_access_map where partner_id is null
    * and updates by querying auth.applications table
    **/
    public function updateMerchantAccessMapHavingEmptyPartner()
    {
        $batch = 500;
        $skip = 0;
        $count = 500;

        $failed = 0;
        $failedIds = [];
        $succeeded = 0;
        $processed = 0;

        $oauthRepo = new OAuthApp\Repository;

        $applications = [];

        while($batch === $count)
        {
            $mappings = $this->repo->merchant_access_map->fetchApplicationRowsWithEmptyPartnerId($batch, $skip);
            if(empty($mappings) === true)
            {
                break;
            }

            $count = $mappings->count();
            $skip  += $count;

            foreach ($mappings as $row)
            {
                $applicationId = $row->{Entity::ENTITY_ID};
                if(array_key_exists($applicationId, $applications) === false)
                {
                    $applications[$applicationId] = $oauthRepo->findOrFail($applicationId);
                }
                
                $partnerId     = $applications[$applicationId]->getMerchantId();
                $merchantId    = $row->{Entity::MERCHANT_ID};

                $traceData = [
                    Entity::APPLICATION_ID => $applicationId,
                    Entity::MERCHANT_ID    => $merchantId,
                    Entity::ENTITY_OWNER_ID     => $partnerId
                ];
                
                try
                {
                    $row->{Entity::ENTITY_OWNER_ID} = $partnerId;
                    $this->repo->merchant_access_map->saveOrFail($row);

                    $succeeded++;
                }
                catch(\Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::ACCESS_MAP_UPDATE_ERROR,
                        $traceData
                    );

                    $failed++;

                    $failedIds[] = $merchantId . '.' . $applicationId;
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
                    Entity::APPLICATION_ID => $appId,
                    Entity::MERCHANT_ID    => $merchantId,
                    Entity::ENTITY_OWNER_ID     => $partnerId
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
                    Entity::ID          => $id,
                    Entity::ENTITY_TYPE => Entity::APPLICATION,
                    Entity::ENTITY_ID   => $appId,
                    Entity::MERCHANT_ID => $merchantId,
                    Entity::ENTITY_OWNER_ID  => $partnerId,
                    Entity::CREATED_AT  => $createdAt,
                    Entity::UPDATED_AT  => $createdAt
                ]
            );
        }
    }
}
