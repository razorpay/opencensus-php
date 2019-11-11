<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;

class EsRepository extends Transaction\EsRepository
{
    /**
     * {@inheritDoc}
     */
    protected $queryFields = [
        Entity::ID,
        Entity::UTR,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
    ];

    /**
     * {@inheritDoc}
     */
    public function getIndexSuffix(): string
    {
        return E::TRANSACTION . '_' . $this->mode;
    }
}
