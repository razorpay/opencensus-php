<?php

namespace RZP\Services\Mock;

use RZP\Services\Nodal as BaseNodal;

class Nodal extends BaseNodal
{
    public function getBalance($data) : array
    {
        return [
            'account_number' => $data['account_number'],
            'mock'           => true,
            'balance'        => 1000,
        ];
    }

}
