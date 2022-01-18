<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Constants\Es;

/**
 * Class EsRepository
 *
 * @package RZP\Models\Payout
 */
class EsRepository extends Base\EsRepository
{
    /**
     * {@inheritDoc}
     */
    protected $queryFields = [
        Entity::ID,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        Entity::CONTACT_TYPE,
        Entity::PRODUCT,
        Entity::PURPOSE,
        Entity::STATUS,
        Entity::CREATED_AT,
        Entity::REVERSED_AT,
        Entity::METHOD,
        Entity::MODE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        Entity::REVERSED_AT,
        Entity::CONTACT_TYPE,
        Entity::PRODUCT,
        Entity::TYPE,
        Entity::METHOD,
        Entity::MODE,
        Entity::PURPOSE,
        Entity::STATUS,
        Entity::SOURCE_TYPE,
        Entity::CREATED_AT,
    ];

    protected function buildQueryForReversedFrom(array & $query, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        $clause = [Es::GTE => $value];

        $filter = [Es::RANGE => [Entity::REVERSED_AT => $clause]];

        $this->addFilter($query, $filter);
    }

    protected function buildQueryForReversedTo(array & $query, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        $clause = [Es::LTE => $value];

        $filter = [Es::RANGE => [Entity::REVERSED_AT => $clause]];

        $this->addFilter($query, $filter);
    }

    protected function buildQueryForContactEmail(array & $query, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_EMAIL_RAW, $value);
    }

    protected function buildQueryForSourceTypeExclude(array & $query, $value)
    {
        if (empty($value) === true)
        {
            return;
        }
        //source_type != 'xpayroll'
        $this->addNegativeTermFilter($query, Entity::SOURCE_TYPE, $value);
    }
}
