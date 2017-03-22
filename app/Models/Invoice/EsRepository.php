<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base\Es;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends Es\Repository
{
    protected static $table = Table::INVOICE;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::PAYMENT_ID,
        Entity::ORDER_ID,
        Entity::USER_ID,
        Entity::RECEIPT,
        Entity::STATUS,
        Entity::TYPE,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES,
    ];

    protected $queryFields = [
        Entity::MERCHANT_ID,
        Entity::PAYMENT_ID,
        Entity::ORDER_ID,
        Entity::USER_ID,
        Entity::RECEIPT,
        Entity::STATUS,
        Entity::TYPE,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES . '.*',
    ];

    protected function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::CUSTOMER_NAME    => Es\Mapping::$textFieldMapping,
            Entity::CUSTOMER_CONTACT => Es\Mapping::$textFieldMapping,
            Entity::CUSTOMER_EMAIL   => Es\Mapping::$textFieldMapping,
            Entity::DESCRIPTION      => Es\Mapping::$textFieldMapping,
            Entity::TERMS            => Es\Mapping::$textFieldMapping,
            Entity::NOTES            => Es\Mapping::$objectFieldMapping,
        ];
    }
}
