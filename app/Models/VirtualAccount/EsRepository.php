<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\BankAccount;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::NOTES,
        Entity::CREATED_AT,
        Entity::DESCRIPTION,
        Entity::BANK_ACCOUNT_ID,
        Entity::OFFLINE_CHALLAN_ID,
        Entity::VPA_ID,
        Entity::QR_CODE_ID,
        Customer\Entity::EMAIL,
        Customer\Entity::NAME,
        Customer\Entity::CONTACT,
        BankAccount\Entity::ACCOUNT_NUMBER,
        Entity::VPA,
    ];

    public function buildQueryForReceiverType(array &$query, $value)
    {
        $receiverTypes = explode(',', $value);

        $exists = [];

        foreach ($receiverTypes as $receiverType)
        {
            array_push($exists, $this->getExistsQueryForField($receiverType . '_id'));
        }

        $innerShouldQuery = [];

        $this->addShould($innerShouldQuery, $exists);

        $this->addFilter($query, $innerShouldQuery);
    }

    public function buildQueryForPayeeAccount(array &$query, $value)
    {
        $termFilter = [];

        $innerShouldQuery = [];

        $value = $this->modifySearchValue($value);

        array_push($termFilter, $this->getQueryForWildcard(BankAccount\Entity::ACCOUNT_NUMBER, $value));

        array_push($termFilter, $this->getQueryForWildcard(Entity::VPA, $value));

        $this->addShould($innerShouldQuery, $termFilter);

        $this->addFilter($query, $innerShouldQuery);
    }

    private function modifySearchValue($string)
    {
        $string = preg_replace('/[^A-Za-z0-9]/', '*', $string); // Removes special chars.

        return $string . '*';
    }

    public function buildQueryForEmail(array &$query, $value)
    {
        $this->addMatchPhrasePrefix($query, Customer\Entity::EMAIL, $value);
    }
}
