<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account as Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'schedule';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = [
        self::WITH_TRASHED => 'sometimes|in:0,1',
    ];

    public function getDailySettlementScheduleByDelay($delay)
    {
        return $this->newQuery()
                    ->where(Entity::PERIOD, '=', Period::DAILY)
                    ->where(Entity::MERCHANT_ID, '=', Merchant::SHARED_ACCOUNT)
                    ->where(Entity::DELAY, '=', $delay)
                    ->first();
    }

    protected function shouldSync($entity) : bool
    {
        return Type::isSyncedInLiveAndTest($entity->getType());
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }
}
