<?php

namespace RZP\Gateway\Amex;

use RZP\Models\Base;
use RZP\Gateway\AxisMigs;

class Entity extends AxisMigs\Entity
{
    const VPC_TRANSACTION_NUMBER = 'vpc_TransactionNo';

    protected $entity = 'amex';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->setAmexTrue();
    }

    public function setAmexTrue()
    {
        $this->setAttribute('amex', 1);
    }

    public function getAmexAttribute()
    {
        return (bool) $this->attributes['amex'];
    }
}
