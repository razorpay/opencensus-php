<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NAME,
        Entity::CONTACT,
        Entity::EMAIL,
        Entity::GSTIN,
        Entity::ACTIVE,
        Entity::CREATED_AT,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::CONTACT,
        Entity::EMAIL,
        Entity::GSTIN,
    ];
}
