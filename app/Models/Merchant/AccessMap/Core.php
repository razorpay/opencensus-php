<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use Razorpay\OAuth\Token;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $merchant,
        array $input = null,
        Base\PublicEntity $entity = null)
    {
        $merchantMapping = (new Entity)->build($input);

        $merchantMapping->generateId();

        $merchantMapping->merchant()->associate($merchant);

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
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function addMappingForOAuthApp(Merchant\Entity $merchant, array $input): Entity
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

        return $this->create($merchant, $data);
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
                return $item->merchant_id.$item->application_id;
            });

            $mappings = $mappings->values()->all();

            $this->trace->info(
                TraceCode::ACCESS_MAP_UPDATE_REQUEST,
                [
                    'total_tokens' => count($mappings)
                ]);

            foreach ($mappings as $mapping)
            {
                $appId      = $mapping->application_id;
                $merchantId = $mapping->merchant_id;
                $createdAt  = $mapping->created_at;

                $traceData = [
                    Entity::APPLICATION_ID => $appId,
                    Entity::MERCHANT_ID    => $merchantId,
                ];

                $this->trace->info(TraceCode::ACCESS_MAP_UPDATE_REQUEST, $traceData);

                try
                {
                    $this->processMigration($appId, $merchantId, $createdAt);

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

    protected function processMigration(string $appId, string $merchantId, int $createdAt)
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
                    Entity::CREATED_AT  => $createdAt,
                    Entity::UPDATED_AT  => $createdAt
                ]
            );
        }
    }
}
