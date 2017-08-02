<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Virtual Account MIS files contain only payment info
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
