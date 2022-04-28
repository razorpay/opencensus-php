<?php

namespace RZP\Models\Transaction\Statement\DirectAccount\Statement;

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

    protected function buildQueryForContactEmail(array &$query, $value)
    {
        if (empty($value) === true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_EMAIL_RAW, $value);
    }
}
