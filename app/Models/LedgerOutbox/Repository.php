<?php

namespace RZP\Models\LedgerOutbox;

use App;
use DB;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Partitions;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Traits\PartitionRepo;
use RZP\Models\Ledger\ReverseShadow\Constants;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use PartitionRepo;

    protected $entity = 'ledger_outbox';

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }

    public function fetchOldOutboxEntriesForRetry($limit, $startTimestamp, $endTimestamp)
    {
        return  $this->newQuery()
            ->from(\DB::raw('`ledger_outbox`'))
            ->where(Entity::IS_DELETED, '=', false)
            ->where(Entity::CREATED_AT, '>=', $startTimestamp)
            ->where(Entity::CREATED_AT, '<=', $endTimestamp)
            ->where(Entity::RETRY_COUNT, '<', Constants::MAX_RETRY_COUNT_CRON)
            ->where(function($query)
            {
                $query->whereNotIn(
                    Entity::ENTITY_TYPE, [LedgerOutboxConstants::TRANSFER,LedgerOutboxConstants::ONDEMAND_SETTLEMENT]
                )
                    ->orWhereNull(Entity::ENTITY_TYPE);
            })
            ->orderBy(Entity::CREATED_AT, 'ASC')
            ->limit($limit)
            ->get();
    }

    public function fetchOldOutboxEntriesForRetryByEntityType($limit, $startTimestamp, $endTimestamp, $entityType, $maxRetryCount)
    {
        return  $this->newQuery()
            ->from(\DB::raw('`ledger_outbox`'))
            ->where(Entity::ENTITY_TYPE, '=', $entityType)
            ->where(Entity::IS_DELETED, '=', false)
            ->where(Entity::CREATED_AT, '>=', $startTimestamp)
            ->where(Entity::CREATED_AT, '<=', $endTimestamp)
            ->where(Entity::RETRY_COUNT, '<', $maxRetryCount)
            ->orderBy(Entity::CREATED_AT, 'ASC')
            ->limit($limit)
            ->get();
    }

    public function fetchOutboxEntriesByPayloadName($payloadName) : PublicCollection
    {
        return $this->newQuery()
            ->from(\DB::raw('`ledger_outbox`'))
            ->where(Entity::PAYLOAD_NAME,'=', $payloadName)
            ->get();
    }

    public function fetchOutboxEntriesByPayloadNameWithTrashed($payloadName) : PublicCollection
    {
        return $this->newQuery()
            ->from(\DB::raw('`ledger_outbox`'))
            ->where(Entity::PAYLOAD_NAME,'=', $payloadName)
            ->withTrashed()
            ->get();
    }
}
