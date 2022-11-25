<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantWebsiteComparator extends Base
{
    protected $excludedKeys = [
        "id" => true,
        "created_at" => true,
        "status" => true,
        "grace_period" => true,
        "send_communication" => true,
        "updated_at"=> true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
