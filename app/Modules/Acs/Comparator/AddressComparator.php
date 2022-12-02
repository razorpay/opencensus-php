<?php

namespace RZP\Modules\Acs\Comparator;

class AddressComparator extends Base
{

    protected $excludedKeys = [
        "created_at" => true,
        "deleted_at" => true,
        "updated_at" => true,
        "entity_id" => true,
        "entity_type" => true,
        "type" => true,
        "primary" => true,
        "name" => true,
        "contact" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
