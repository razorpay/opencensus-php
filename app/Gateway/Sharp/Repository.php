<?php

namespace RZP\Gateway\Sharp;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'sharp';

    public function findByPaymentId($id)
    {
        assertTrue($this->mode === 'test');

        return array();
    }

    // Override Base\Repository function since sharp table doesn't exist
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return (new Entity);
    }
}
