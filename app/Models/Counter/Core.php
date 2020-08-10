<?php

namespace RZP\Models\Counter;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class Core extends Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function fetchOrCreate($balance)
    {
        $accountType = $balance->getAccountType();

        $counter = $this->repo->counter->getCounterByAccountTypeAndBalanceId(
            $accountType,
            $balance->getId()
        );

        if ($counter === null)
        {
            $currentTimeStamp = Carbon::now(Timezone::IST)->firstOfMonth()->getTimestamp();

            $counter = (new Entity)->build();

            $counter->setAccountType($accountType);

            $counter->balance()->associate($balance);

            $counter->setFreePayoutsConsumedLastResetAt($currentTimeStamp);

            $counter->saveOrFail();

            $this->trace->info(
                TraceCode::COUNTER_ENTITY_CREATED,
                [
                    'account_type'      => $accountType,
                    'balance_id'        => $balance->getId()
                ]
            );
        }

        return $counter;
    }
}
