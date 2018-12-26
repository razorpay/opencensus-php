<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;

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
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        Entity::TYPE,
        Entity::METHOD,
        Entity::STATUS,
        Entity::CREATED_AT,
    ];

    /**
     * {@inheritDoc}
     */
    protected $queryFields = [
        Entity::ID,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
    ];
}
