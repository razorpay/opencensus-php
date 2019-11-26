<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    public function __construct()
    {
        parent::__construct();
    }
}
