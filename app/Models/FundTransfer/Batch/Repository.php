<?php

namespace RZP\Models\FundTransfer\Batch;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Settlement;

class Repository extends Base\Repository
{
    protected $entity = 'batch_fund_transfer';

    protected static $appFetchParamRules = [
        'date' => 'integer|digits:8',
        'type' => 'string|max:10',
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
}
