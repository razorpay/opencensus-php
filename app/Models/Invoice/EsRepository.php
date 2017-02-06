<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;

class EsRepository extends Base\EsRepository
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
            Entity::DESCRIPTION => Base\EsMappping::$textFieldMapping,
            Entity::TERMS       => Base\EsMappping::$textFieldMapping,
            Entity::NOTES       => Base\EsMappping::$objectFieldMapping,
        ];
    }
}
