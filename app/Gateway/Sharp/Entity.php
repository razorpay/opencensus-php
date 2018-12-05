<?php

namespace RZP\Gateway\Sharp;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected $entity = 'sharp';

    //Dummy functions to make tests pass
    public function getAuthCode()
    {
        assertTrue($this->mode === null);

        return '000000';
    }

    //Dummy functions to make tests pass
    public function getTransactionId()
    {
        assertTrue($this->mode === null);

        return '123456';
    }
}
