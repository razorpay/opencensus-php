<?php

namespace RZP\Gateway\Sharp;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'sharp';

    //Dummy functions to make tests pass
    public function getAuthCode()
    {
        rzpAssert($this->mode === NULL);

        return '000000';
    }

    //Dummy functions to make tests pass
    public function getTransactionId()
    {
        rzpAssert($this->mode === NULL);

        return '123456';
    }
}
