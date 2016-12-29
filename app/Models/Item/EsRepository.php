<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::ORDER;

    protected $fields = [
        Entity::ID,
        Entity::NAME,
        Entity::DESCRIPTION,
    ];

    protected $fieldsMappings = [
        Entity::NAME => [

            //
            // TODO: Following mapping can be made default for all fields?
            //

            'type'            => 'text',
            'analyzer'        => 'edge_ngram_analyzer',
            'search_analyzer' => 'standard',
            'index_options'   => 'offsets',
        ],
        Entity::DESCRIPTION => [
            'type'            => 'text',
            'analyzer'        => 'edge_ngram_analyzer',
            'search_analyzer' => 'standard',
            'index_options'   => 'offsets',
        ],
    ];
}
