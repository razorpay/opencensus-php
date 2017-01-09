<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{
    public function generate($from, $to)
    {
        return parent::generate($from, $to);
    }
}
