<?php

namespace RZP\Models\Transaction\Statement;

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
        Entity::NOTES . '.value',
        Entity::FUND_ACCOUNT_NUMBER,
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

        $this->addTermFilter($query, Entity:: CONTACT_EMAIL_RAW, $value);
    }

    public function buildQueryForContactPhonePs(array &$query, string $value)
    {
        if (empty($value) == true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_PHONE, $value);
    }

    public function buildQueryForContactEmailPs(array &$query, string $value)
    {
        if (empty($value) == true) {
            return;
        }

        $this->addTermFilter($query, Entity::CONTACT_EMAIL_PARTIAL_SEARCH, strtolower($value));
    }
}
