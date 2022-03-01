<?php

namespace RZP\Models\Transaction;
use RZP\Models\Transaction\Statement\Entity;

use RZP\Models\Base;
use RZP\Models\Transaction\Statement;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::CREATED_AT,
        Entity::CONTACT_PHONE,
        Entity::NOTES,

        // Statement\* module which extends this module also relies on transaction's index to serve its fetch.
        Statement\Entity::CONTACT_NAME,
        Statement\Entity::CONTACT_EMAIL,
        Statement\Entity::UTR,
    ];
}
