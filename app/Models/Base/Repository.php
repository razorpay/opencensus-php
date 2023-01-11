<?php

namespace RZP\Models\Base;

use RZP\Base\RepositoryManager;
use RZP\Base\Repository as BaseRepository;
use Rzp\Wda_php\Symbol;

/**
 * Class Repository
 *
 * @package RZP\Models\Base
 *
 * @property RepositoryManager $repo
 */
class Repository extends BaseRepository
{
    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        return $this->fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip, $relations);
    }

    /**
     * In case any entity defines email fetch filter, we ensure
     * that unicode is handled properly via this function
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamEmail($query, $params)
    {
        $attribute = $this->dbColumn('email');

        // Email should be case insensitive
        $email = mb_strtolower($params['email']);

        $query->where($attribute, '=', $email);
    }

    protected function addWDAQueryParamEmail($wdaQueryBuilder, $params)
    {
        // Email should be case insensitive
        $email = mb_strtolower($params['email']);

        $wdaQueryBuilder->filters($this->getTableName(), 'email', [$email], Symbol::EQ);
    }
}
