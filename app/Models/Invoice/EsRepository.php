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
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES,
    ];

    protected $queryFields = [
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES . '.*',
    ];

    protected function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::RECEIPT          => Es\Mapping::$textFieldMapping,
            Entity::CUSTOMER_NAME    => Es\Mapping::$textFieldMapping,
            Entity::CUSTOMER_CONTACT => Es\Mapping::$textFieldMapping,
            Entity::CUSTOMER_EMAIL   => Es\Mapping::$textFieldMapping,
            Entity::DESCRIPTION      => Es\Mapping::$textFieldMapping,
            Entity::TERMS            => Es\Mapping::$textFieldMapping,
            Entity::NOTES            => Es\Mapping::$objectFieldMapping,
        ];
    }
}
