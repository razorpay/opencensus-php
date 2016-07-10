<?php

namespace RZP\Gateway\AxisGenius;

use RZP\Models\Base;
use RZP\Gateway\AxisMigs;

class Entity extends AxisMigs\Entity
{
    protected $entity = 'axis_genius';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->setGeniusTrue();
    }

    public function setGeniusTrue()
    {
        $this->setAttribute('genius', 1);
    }
}
