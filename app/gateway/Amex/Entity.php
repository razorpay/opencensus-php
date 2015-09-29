<?php

namespace Gateway\Amex;

use Models\Base;
use Gateway\AxisMigs;

class Entity extends AxisMigs\Entity
{
    protected $entity = 'amex';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->setAmexTrue();
    }

    public function setGeniusTrue()
    {
        $this->setAttribute('amex', 1);
    }
}