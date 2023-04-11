<?php

namespace RZP\Models\Transaction\Statement\Ledger\Statement;

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
        Entity::CONTACT_PHONE_PS,
        Entity::CONTACT_EMAIL_PS,
    ];

    /**
     * {@inheritDoc}
     */
    public function getIndexSuffix(): string
    {
        return E::TRANSACTION . '_' . $this->mode;
    }

    protected function buildQueryForContactEmail(array &$query, $value)
    {
        if (empty($value) === true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_EMAIL_RAW, $value);
    }
}
