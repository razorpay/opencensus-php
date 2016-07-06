<?php

namespace RZP\Gateway\Sharp;

use EE\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Sharp';

    public function findByPaymentId()
    {
        assert($this->mode === 'test');

        return array();
    }
}
