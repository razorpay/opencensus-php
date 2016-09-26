<?php

namespace RZP\Gateway\Sharp;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Sharp';

    public function findByPaymentId()
    {
        assertTrue($this->mode === 'test');

        return array();
    }

    // Override Base\Repository function since sharp table doesn't exist
    public function findCapturedPaymentById($paymentId)
    {
        return (new Entity);
    }
}
