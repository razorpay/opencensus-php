<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base\EsRepository as BaseEsRepository;
use RZP\Models\Base\EsMappping;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends BaseEsRepository
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
        ENTITY::CUSTOMER_EMAIL,
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
        ENTITY::CUSTOMER_EMAIL,
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
            Entity::CUSTOMER_NAME    => EsMappping::$textFieldMapping,
            Entity::CUSTOMER_CONTACT => EsMappping::$textFieldMapping,
            Entity::CUSTOMER_EMAIL   => EsMappping::$textFieldMapping,
            Entity::DESCRIPTION      => EsMappping::$textFieldMapping,
            Entity::TERMS            => EsMappping::$textFieldMapping,
            Entity::NOTES            => EsMappping::$objectFieldMapping,
        ];
    }
}
