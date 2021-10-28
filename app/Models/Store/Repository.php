<?php

namespace RZP\Models\Store;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'store';
}
