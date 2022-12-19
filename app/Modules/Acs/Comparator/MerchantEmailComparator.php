<?php

namespace RZP\Modules\Acs\Comparator;

use RZP\Constants\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmailComparator extends Base
{
    protected $excludedKeys = [
        "created_at" => true,
        "updated_at" => true,
        "verified" => true
    ];

    function __construct()
    {
        parent::__construct();
    }

}
