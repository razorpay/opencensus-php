<?php

namespace Gateway\Sharp;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Sharp';

    public function findByPaymentId()
    {
        assert($this->mode === 'test');

        return array();
    }
}
