<?php

namespace Models\Settlement\Daily;

use Models\Base;
use Models\Settlement\Daily;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Daily';

    protected static $fetchExtraParamRules = array(
        'date' => 'integer|digits:8');

    public function getSettlementForToday($channel = 'kotak')
    {
        $timestamp = Daily\Entity::getTodayTimestamp();

        return Daily\Entity::where('day', '=', $timestamp)
                               ->where('channel', '=', $channel)
                               ->firstOrFail();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

//    protected function buildFetchQuery($params, )
}