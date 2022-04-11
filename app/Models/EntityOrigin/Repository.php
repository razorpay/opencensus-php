<?php

namespace RZP\Models\EntityOrigin;

use RZP\Constants;
use RZP\Models\Base\Repository as BaseRepository;
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
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->first();
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
}
