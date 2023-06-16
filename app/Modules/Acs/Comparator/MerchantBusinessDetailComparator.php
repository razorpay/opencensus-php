<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantBusinessDetailComparator extends Base
{
    protected $excludedKeys = [
        'gst_details' => true,
    ];

    function __construct()
    {
        parent::__construct();
    }
}
