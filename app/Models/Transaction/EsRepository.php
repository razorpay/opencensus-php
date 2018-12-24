<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;
use RZP\Models\Transaction\Statement;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Statement\Entity::ID,
        Statement\Entity::MERCHANT_ID,
        Statement\Entity::CONTACT_NAME,
        Statement\Entity::CONTACT_EMAIL,
        Statement\Entity::BALANCE_ID,
        Statement\Entity::UTR,
        Statement\Entity::CREATED_AT,
        Statement\Entity::ACCOUNT_NUMBER,
    ];

    protected $queryFields = [
        Statement\Entity::CONTACT_NAME,
        Statement\Entity::CONTACT_EMAIL,
        Statement\Entity::UTR,
        Statement\Entity::ACCOUNT_NUMBER,
        Statement\Entity::BALANCE_ID,
    ];
}
