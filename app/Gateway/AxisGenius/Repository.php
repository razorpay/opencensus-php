<?php

namespace RZP\Gateway\AxisGenius;

use RZP\Gateway\AxisMigs;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'axis_genius';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('genius', '=', '1');
    }
}
