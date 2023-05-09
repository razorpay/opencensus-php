<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_application';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::TYPE            => 'sometimes|string',
        Entity::APPLICATION_ID  => 'sometimes|string|size:14'
    ];

    /**
     * @param   string      $merchantId     The partner's MID for whom merchant Apps need to be fetched
     * @param   array       $types          Application types, ex., referred, managed
     * @param   string|null $mode           The connection mode
     * @param   bool        $withTrashed    Whether to include soft deleted results?
     *
     * @return  Base\PublicCollection
     */
    public function fetchMerchantApplications(
        string $merchantId, array $types = [], string $mode = null, bool $withTrashed = false, string $appId = null
    ) : Base\PublicCollection
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        $query = $query->merchantId($merchantId);

        if (empty($types) === false)
        {
            $query = $query->whereIn(Entity::TYPE, $types);
        }
        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }
        if (empty($appId) === false)
        {
            $query = $query->where(Entity::APPLICATION_ID, $appId);
        }

        return $query->orderBy(Entity::TYPE)->orderBy(Entity::ID)->get();
    }

    /**
     * Fetch merchant applications in sync for given merchantIDs and given types.
     * It fails if data is not in sync in test and live DB.
     * @param   string  $merchantId     The partner's MID for whom merchant Apps need to be fetched
     * @param   array   $types          Application types, ex., referred, managed
     * @param   bool    $withTrashed    Whether to include soft deleted results?
     *
     * @return  Base\PublicCollection
     *
     * @throws  LogicException
     */
    public function fetchMerchantAppInSyncOrFail(
        string $merchantId, array $types = [], bool $withTrashed = false
    ) : Base\PublicCollection
    {
        $liveEntities = $this->fetchMerchantApplications($merchantId, $types, 'live', $withTrashed);
        $testEntities = $this->fetchMerchantApplications($merchantId, $types, 'test', $withTrashed);
        $isSynced = $this->areEntitiesSyncOnLiveAndTest($liveEntities, $testEntities);
        if ($isSynced === true)
        {
            return $liveEntities;
        }
        else
        {
            $this->trace->critical(
                TraceCode::DATA_MISMATCH_ON_LIVE_AND_TEST,
                [
                    'on_live' => $liveEntities,
                    'on_test' => $testEntities
                ]
            );
            throw new LogicException("Data is not synced on Live and Test DB");
        }
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantApplication(string $entityId, string $entityType) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->where($entityType, $entityId)
                    ->get();
    }

    /**
     * @param   array   $applicationIds Application IDs of merchant application
     *
     * @return  Base\PublicCollection
     */
    public function fetchMerchantApplicationByAppIds(array $applicationIds) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->whereIn(Entity::APPLICATION_ID, $applicationIds)
                    ->get();
    }

    /**
     * Restores the merchant applications for given appIds
     * @param   array           $deletedAppIds  The application_id of merchant applications
     * @param   string|null     $mode
     * @return  void
     */
    public function restoreDeletedApps(array $deletedAppIds, string $mode = null)
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->whereIn(Entity::APPLICATION_ID, $deletedAppIds)
                     ->withTrashed()
                     ->update([
                         Entity::DELETED_AT => null,
                     ]);
    }
}
