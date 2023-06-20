<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Request as MerchantRequest;

class Repository extends Base\Repository
{
    const VALID_ENTITY_TYPE = [
        E::DISPUTE,
        E::WORKFLOW_ACTION,
    ];

    protected $entity = 'state';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID    => 'filled|string|size:14',
        Entity::MERCHANT_ID => 'filled|string|size:14',
        Entity::ENTITY_ID   => 'filled|string|size:14',
        Entity::ENTITY_TYPE => 'filled|string|max:100|custom',
    ];

    protected function validateEntityType($attribute, $value)
    {
        if (in_array($value, self::VALID_ENTITY_TYPE, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MORPHED_ENTITY_INVALID,
                Entity::ENTITY_TYPE,
                [
                    Entity::ENTITY_TYPE => $value
                ]);
        }
    }

    public function findLastMerchantRequestState(MerchantRequest\Entity $request)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $request->getId())
                    ->where(Entity::NAME, $request->getStatus())
                    ->firstOrFailPublic();
    }

    public function getPreviousActivationStatus(string $merchantId)
    {
        return $this->newQuery()
            ->select(Entity::NAME)
            ->where(Entity::ENTITY_ID, '=', $merchantId)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->skip(1)
            ->take(1)
            ->first();
    }

    public function fetchPaymentsEnabledMerchants(int $from,int $to)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->whereIn(Entity::NAME, Status::PAYMENTS_ENABLED_STATUSES)
                    ->where(Entity::CREATED_AT, '>=', $from)
                    ->where(Entity::CREATED_AT, '<=', $to)
                    ->get()
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function filterPaymentsEnabledMerchants(array $merchantIdList,int $from,int $to)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->whereIn(Entity::ENTITY_ID, $merchantIdList)
                    ->whereIn(Entity::NAME, Status::PAYMENTS_ENABLED_STATUSES)
                    ->groupBy(Entity::ENTITY_ID)
                    ->selectRaw('MIN(' . Entity::CREATED_AT . ') as first_enabled_at,' . Entity::ENTITY_ID)
                    ->having('first_enabled_at', '>=', $from)
                    ->having('first_enabled_at', '<=', $to)
                    ->get()
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function fetchAutoKycPassMerchants(int $createdAt)
    {
        $stateCreatedAtCol  = $this->repo->state->dbColumn(Entity::CREATED_AT);
        $merchantDetailMerchantIdCol = $this->repo->merchant_detail->dbColumn
        (\RZP\Models\Merchant\Detail\Entity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->leftjoin(Table::MERCHANT_DETAIL, Entity::ENTITY_ID, $merchantDetailMerchantIdCol)
            ->select(Entity::ENTITY_ID)
            ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
            ->where(Entity::NAME, '=', 'activated_mcc_pending')
            ->where(\RZP\Models\Merchant\Detail\Entity::ACTIVATION_STATUS, '=', 'activated_mcc_pending')
            ->where($stateCreatedAtCol, '>', $createdAt)
            ->distinct()
            ->get()
            ->pluck(Entity::ENTITY_ID)
            ->toArray();
    }

    public function isEntryPresentForNameAndEntityId($name, $entityId): bool
    {
        $result = $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
            ->where(Entity::NAME, '=', $name)
            ->first();

        return empty($result) === false;
    }

    public function getEntityIdsWithName($name)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->select(Entity::ENTITY_ID)
            ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
            ->where(Entity::NAME, '=', $name)
            ->distinct()
            ->get()
            ->pluck(Entity::ENTITY_ID)
            ->toArray();
    }

    // Merchant ID's with state transition between the given timestamp

    public function getEntityIdsWithNameInRange($name, int $from, int $to): array
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->select(Entity::ENTITY_ID)
                    ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->where(Entity::NAME, '=', $name)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();

    }

    public function fetchByEntityIdAndEntityTypeAndName($merchantId, $names)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchantId)
                    ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
                    ->whereIn(Entity::NAME, $names)
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->limit(1)
                    ->get();
    }

    public function fetchByEntityIdAndState($entityId, $name)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->select('*')
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, '=', 'merchant_detail')
                    ->where(Entity::NAME, '=', $name)
                    ->get();
    }
}
