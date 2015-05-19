<?php

namespace Gateway\AxisGenius;

use Models\Base;
use Gateway\AxisMigs;

class Entity extends AxisMigs\Entity
{
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