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
        Entity::DESCRIPTION,
        Entity::BANK_ACCOUNT_ID,
        Entity::VPA_ID,
        Entity::QR_CODE_ID,
        Customer\Entity::EMAIL,
        Customer\Entity::NAME,
        Customer\Entity::CONTACT,
    ];

    public function buildQueryForReceiverType(array & $query, $value)
    {
        $receiverTypes = explode(',', $value);

        $exists = [];

        foreach ($receiverTypes as $receiverType)
        {
            array_push($exists, $this->getExistsQueryForField($receiverType.'_id'));
        }

        $innerShouldQuery = [];

        $this->addShould($innerShouldQuery, $exists);

        $this->addFilter($query, $innerShouldQuery);
    }
}
