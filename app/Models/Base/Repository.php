<?php

namespace RZP\Models\Base;

use RZP\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
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
}
