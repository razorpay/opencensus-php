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
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        Entity::TYPE,
        Entity::CREATED_AT,
    ];

    protected $queryFields = [
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
    ];
}
