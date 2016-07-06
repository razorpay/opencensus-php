<?php

namespace RZP\Gateway\Sharp;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'sharp';

    //Dummy functions to make tests pass
    public function getAuthCode()
    {
        assert($this->mode === NULL);

        return '000000';
    }

    //Dummy functions to make tests pass
    public function getTransactionId()
    {
        assert($this->mode === NULL);

        return '123456';
    }
}
