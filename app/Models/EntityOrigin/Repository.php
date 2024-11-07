<?php

namespace RZP\Models\EntityOrigin;

use RZP\Base\ConnectionType;
use RZP\Constants;
use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;
use RZP\Models\EntityOrigin\Constants as EntityOriginConstants;

class Repository extends BaseRepository
{
    protected $entity = Constants\Entity::ENTITY_ORIGIN;

    protected $appFetchParamRules = [
        Entity::ORIGIN_ID   => 'sometimes|string|size:14',
        Entity::ORIGIN_TYPE => 'sometimes|string|in:merchant,application',
        Entity::ENTITY_ID   => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string',
    ];

    public function fetchByEntityTypeAndEntityId(string $entityType, string $entityId)
    {
        $entityOrigin =  $this->newQuery()
                            ->where(Entity::ENTITY_TYPE, $entityType)
                            ->where(Entity::ENTITY_ID, $entityId)
                            ->first();

        if (is_null($entityOrigin) === true)
        {
            $entityOrigin = $this->newQueryAndResetEntityConnection(function () use ($entityType, $entityId)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                             ->where(Entity::ENTITY_TYPE, $entityType)
                             ->where(Entity::ENTITY_ID, $entityId)
                             ->first();
            });
        }

        return $entityOrigin;
    }

    public function fetchByEntityTypeAndEntityIdOnReadReplica(string $entityType, string $entityId)
    {
        $entityOrigin =  $this->newQueryWithConnection($this->getSlaveConnection())
                              ->where(Entity::ENTITY_TYPE, $entityType)
                              ->where(Entity::ENTITY_ID, $entityId)
                              ->first();


        if (is_null($entityOrigin) === true)
        {
            $entityOrigin = $this->newQueryAndResetEntityConnection(function () use ($entityType, $entityId)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->first();
            });
        }

        return $entityOrigin;
    }

    public function fetchOriginApplicationsForPartner(string $partnerId, int $limit = 100)
    {
        $merchantApplicationIdColumn = $this->repo->merchant_application->dbColumn(MerchantApplicationsEntity::APPLICATION_ID);
        $originIdColumn = $this->dbColumn(Entity::ORIGIN_ID);
        $merchantIdColumn = $this->repo->merchant_application->dbColumn(MerchantApplicationsEntity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->join(Constants\Table::MERCHANT_APPLICATION, $originIdColumn, '=', $merchantApplicationIdColumn)
                    ->where(Entity::ORIGIN_TYPE, EntityOriginConstants::APPLICATION)
                    ->where($merchantIdColumn, '=', $partnerId)
                    ->limit($limit)
                    ->get();
    }

    protected function checkHarvsterQuerySplitzAndReturnConnection()
    {
        $properties = [
            "id" => UniqueIdEntity::generateUniqueId(),
            "experiment_id" => $this->app['config']->get('app.splitz_harvester_query_partnership_experiment_id'),
        ];

        $variant = (new MerchantCore())->isSplitzExperimentEnable($properties, 'Enable');

        return $variant === true ? ConnectionType::DATA_WAREHOUSE_MERCHANT :ConnectionType::PAYMENT_FETCH_REPLICA;
    }
}
