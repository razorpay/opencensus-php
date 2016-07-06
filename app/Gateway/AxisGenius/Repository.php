<?php

namespace Gateway\AxisGenius;

use Gateway\AxisMigs;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'AxisGenius';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('genius', '=', '1');
    }
}