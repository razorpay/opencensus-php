<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    /*
     * entity_id => can be a pulic id, hence 20 chars
     * entity    => should be one of the allowed entities
     */
    protected static $entityFetchRules = [
        'entity_id'     => 'required|alpha_dash|max:20',
        'entity'        => 'required|string|in:report'
    ];
}
