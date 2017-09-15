<?php

namespace RZP\Models\Base;

use RZP\Base\Repository as BaseRepository;
use RZP\Constants\Mode;

class Repository extends BaseRepository
{
    const SHOULD_SYNC   = 'should_sync';

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        return $this->fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip, $relations);
    }

    /**
     * In case any entity defines email fetch filter, we ensure
     * that unicode is handled properly via this function
     */
    protected function addQueryParamEmail($query, $params)
    {
        $attribute = $this->dbColumn('email');

        // Email should be case insensitive
        $email = mb_strtolower($params['email']);

        $query = $query->where($attribute, '=', $email);
    }

    protected function isTestMode(): bool
    {
        return ($this->app['rzp.mode'] === Mode::TEST);
    }

    protected function isLiveMode(): bool
    {
        return ($this->app['rzp.mode'] === Mode::LIVE);
    }
}
