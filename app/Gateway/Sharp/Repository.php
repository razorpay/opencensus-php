<?php

namespace RZP\Gateway\Sharp;

use RZP\Exception;
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
