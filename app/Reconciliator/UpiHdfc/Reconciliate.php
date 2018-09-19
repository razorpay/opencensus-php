<?php

namespace RZP\Reconciliator\UpiHdfc;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

/**
 * @see https://drive.google.com/file/d/0B5y89FSSH1qSRHVuelBuYkdxcTFzMlFwcVpwNHVVMkZQZm1Z/view?usp=sharing
 */
class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
