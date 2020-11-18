<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Models\Customer;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
        Entity::STATUS,
        Entity::DESCRIPTION,
        Customer\Entity::EMAIL,
        Customer\Entity::NAME,
        Customer\Entity::CONTACT,
    ];
}
