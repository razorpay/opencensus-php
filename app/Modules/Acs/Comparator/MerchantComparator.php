<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantComparator extends Base
{

    protected static $excludedKeys = [
        "sample" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
