<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base\EsRepository as BaseEsRepository;
use RZP\Models\Base\EsMapping;
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
            Entity::CUSTOMER_NAME    => EsMapping::$textFieldMapping,
            Entity::CUSTOMER_CONTACT => EsMapping::$textFieldMapping,
            Entity::CUSTOMER_EMAIL   => EsMapping::$textFieldMapping,
            Entity::DESCRIPTION      => EsMapping::$textFieldMapping,
            Entity::TERMS            => EsMapping::$textFieldMapping,
            Entity::NOTES            => EsMapping::$objectFieldMapping,
        ];
    }
}
