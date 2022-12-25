<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'payout_source';

    public function getPayoutSourceBySourceIdSourceTypePayoutId(string $sourceId,
                                                                string $sourceType,
                                                                string $payoutId)
    {
        $sourceIdColumn = $this->repo->payout_source->dbColumn(Entity::SOURCE_ID);

        $sourceTypeColumn = $this->repo->payout_source->dbColumn(Entity::SOURCE_TYPE);

        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        return $this->newQuery()
                    ->where($sourceIdColumn, $sourceId)
                    ->where($sourceTypeColumn, $sourceType)
                    ->where($payoutIdColumn, $payoutId)
                    ->first();
    }

    public function getPayoutSourceByPayoutIdAndPriority(string $payoutId, string $priority)
    {
        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        $priorityColumn = $this->repo->payout_source->dbColumn(Entity::PRIORITY);

        return $this->newQuery()
                    ->where($payoutIdColumn, $payoutId)
                    ->where($priorityColumn, $priority)
                    ->first();
    }

    public function getPayoutSourcesByPayoutId(string $payoutId)
    {
        $payoutIdColumn = $this->repo->payout_source->dbColumn(Entity::PAYOUT_ID);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->where($payoutIdColumn, $payoutId)
                    ->get();
    }

    public function getPayoutServiceSources(string $payoutId, array $fields = [], string $orderBy = "", bool $asc = true)
    {
        $tableName = Table::PAYOUT_SOURCE;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_payout_sources';
        }

        if (count($fields) > 0)
        {
            $columns = join(',', $fields);
        }
        else
        {
            $columns = '*';
        }

        $orderByColumn = '';
        $order         = ($asc === true) ? 'asc' : 'desc';

        if ($orderBy !== '')
        {
            $orderByColumn = 'order by ' . $orderBy . ' ' . $order;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select $columns from $tableName where payout_id = '$payoutId' $orderByColumn");
    }
}
