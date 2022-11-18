<?php

namespace RZP\Models\PayoutOutbox;

use Carbon\Carbon;
use RZP\Constants\Partitions;
use RZP\Models\Base;
use RZP\Models\Base\Traits\PartitionRepo;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\PayoutOutbox\Constants as PayoutOutboxConstants;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive, PartitionRepo;

    protected $entity = 'payout_outbox';

    protected $mode;

    /**
     * This function checks for deleted at status
     *
     * @param string $id
     * @param string $merchantId
     * @param string $userId
     * @return mixed
     */
    public function fetchPayoutInOutboxById(string $id, string $merchantId, string $userId)
    {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
                    ->useWritePdo()
                    ->where(Entity::ID, $id)
                    ->merchantId($merchantId)
                    ->where(Entity::USER_ID, $userId)
                    ->whereNull(Entity::DELETED_AT)
                    ->where(Entity::EXPIRES_AT, ">=", Carbon::now()->timestamp)
                    ->first();
    }

    /**
     * Returns the orphan payout - The payout for which no action was taken
     * Here, we get only orphan payout for a time range, i.e payouts which are orphaned before 15 mins or 30 mins
     *
     * select count(*) from payout_outbox where deleted_at = null and expires_at BETWEEN NOW()-ORPHAN_PAYOUT_RANGE_IN_MINUTES AND NOW();
     *
     * @return mixed
     */
    public function getOrphanedPayoutsFromOutbox() {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
            ->useWritePdo()
            ->select(Entity::ID)
            ->whereNull(Entity::DELETED_AT)
            ->whereBetween(Entity::EXPIRES_AT, [Carbon::now()->subMinutes(PayoutOutboxConstants::ORPHAN_PAYOUT_RANGE_IN_MINUTES)->timestamp, Carbon::now()->timestamp])
            ->get();
    }

    /**
     * Deletes the orphan payout - The payout for which no action was taken
     *
     * @param $payoutIds
     */
    public function deleteOrphanedPayouts($payoutIds) {
        foreach ($payoutIds as $payoutId) {
            $orphanPayout = $this->fetchPayoutById($payoutId);

            if ($orphanPayout == null || $orphanPayout->trashed()) {
                $this->trace->info(TraceCode::BAD_REQUEST_INVALID_ORPHAN_PAYOUT_ID, ['id' => $payoutId]);
            }

            $this->repo->deleteOrFail($orphanPayout);

            $this->trace->info(TraceCode::DELETED_ORPHAN_PAYOUT, $payoutId);
        }
    }

    /**
     * Gets entry from payout_outbox by id
     *
     * @param string $id
     * @return mixed
     */
    public function fetchPayoutById(string $id)
    {
        $mode = $this->app['rzp.mode'];
        return $this->newQueryWithConnection($mode)
            ->useWritePdo()
            ->where(Entity::ID, $id)
            ->first();
    }

    public function createPayoutInOutbox($input): array
    {
        $payout = $this->core->create($input);

        return $payout->toArrayPublic();
    }

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }
}
