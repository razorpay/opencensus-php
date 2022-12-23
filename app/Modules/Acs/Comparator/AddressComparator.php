<?php

namespace RZP\Modules\Acs\Comparator;

class AddressComparator extends Base
{

    protected $excludedKeys = [
        "entity_id" => true,
        "entity_type" => true,
        "type" => true,
        "primary" => true,
        "deleted_at" => true,
        "created_at" => true,
        "updated_at" => true,
        "tag" => true,
        "landmark" => true,
        "name" => true,
        "contact" => true,
        "source_id" => true,
        "source_type" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
