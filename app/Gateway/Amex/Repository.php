<?php

namespace RZP\Gateway\Amex;

use RZP\Gateway\AxisMigs;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'Amex';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('amex', '=', '1');
    }
}