<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::ITEM;

    protected $fields = [
        Entity::ID,
        Entity::NAME,
        Entity::DESCRIPTION,
    ];

    protected $fieldsMappings = [];
}
