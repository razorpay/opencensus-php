<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\Customer;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    const CUSTOMER_FIELDS_PREFIX = 'cust_';

    const CUSTOMER_NAME    = self::CUSTOMER_FIELDS_PREFIX . Customer\Entity::NAME;
    const CUSTOMER_EMAIL   = self::CUSTOMER_FIELDS_PREFIX . Customer\Entity::EMAIL;
    const CUSTOMER_CONTACT = self::CUSTOMER_FIELDS_PREFIX . Customer\Entity::CONTACT;

    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::STATUS,
        Entity::CREATED_AT,
        Entity::CUSTOMER_ID,
        Entity::NAME,
        Entity::PROVIDER,
    ];

    public function buildQueryForEntityType(array &$query, $value)
    {
        return $query;
    }

    public function buildQueryForCustEmail(array &$query, $value)
    {
        $this->addMatchPhrasePrefix($query, self::CUSTOMER_EMAIL, $value);
    }
    
    public function buildQueryForCustomerId(array &$query, $value)
    {
        $this->addTermFilter($query, Entity::CUSTOMER_ID, $value);
    }
}
