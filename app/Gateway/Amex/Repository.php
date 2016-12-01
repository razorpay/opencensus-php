<?php

namespace RZP\Gateway\Amex;

use RZP\Gateway\AxisMigs;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'amex';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('amex', '=', '1');
    }
}