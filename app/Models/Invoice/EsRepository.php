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
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES,
    ];

    protected $queryFields = [
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES . '.*',
    ];

    public function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::DESCRIPTION => EsMappping::$textFieldMapping,
            Entity::TERMS       => EsMappping::$textFieldMapping,
            Entity::NOTES       => EsMappping::$objectFieldMapping,
        ];
    }
}
