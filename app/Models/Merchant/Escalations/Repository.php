<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_onboarding_escalations';

    public function fetchEscalationsForMerchants(array $merchantIdList): array
    {
        return $this->newQuery()
            ->select(Entity::MERCHANT_ID, Entity::MILESTONE, Entity::THRESHOLD)
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->get()
            ->toArray();
    }

    public function fetchLatestEscalation(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }

    public function fetchEscalations(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function fetchEscalationForThresholdAndMilestone(string $merchantId, string $milestone, int $threshold)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::MILESTONE, $milestone)
            ->where(Entity::THRESHOLD, $threshold)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->get()
            ->toArray();
    }

    public function fetchLiveEscalationForThresholdAndMilestone(string $merchantId, string $milestone, int $threshold)
    {
        return $this->newQueryWithConnection(Mode::LIVE)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::MILESTONE, $milestone)
            ->where(Entity::THRESHOLD, $threshold)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->get()
            ->toArray();
    }
}
