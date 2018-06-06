<?php

namespace RZP\Models\FundTransfer\Batch;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;

class Repository extends Base\Repository
{
    protected $entity = 'batch_fund_transfer';

    protected $appFetchParamRules = [
        'date'          => 'integer|digits:8',
        Entity::TYPE    => 'string|max:10',
        Entity::CHANNEL => 'sometimes|string',
    ];

    protected static $fetchExtraParamRules = [
        'date' => 'integer|digits:8'
    ];

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        if (isset($params['date']))
        {
            $timestamp = Carbon::createFromFormat('dmY', $params['date'], Timezone::IST)->getTimestamp();

            $query->where(Daily\Entity::DATE, '=', $params['date']);
        }

        return $query;
    }

    /**
     * Gives the count of batch fund transfer generated on current day
     *
     * @param string $channel
     * @return mixed
     */
    public function getSettlementBatchCountOfDay(string $channel)
    {
        $today  = Carbon::today(Timezone::IST)->getTimestamp();

        $count  = $this->newQuery()
                       ->where(Entity::CHANNEL, $channel)
                       ->where(Entity::CREATED_AT, '>=' , $today)
                       ->count();

        return $count;
    }
}
