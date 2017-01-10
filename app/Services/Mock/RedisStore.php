<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment\Gateway;
use RZP\Services\RedisStore as BaseStore;

class RedisStore extends BaseStore
{
    // Returns dummy data for tests
    protected function fetchSortedSetData(string $key)
    {
        if ($key === 'gateway_priorities:card')
        {
            return [
                Gateway::HDFC        => '50',
                Gateway::AXIS_MIGS   => '40',
                Gateway::AMEX        => '30',
                Gateway::CYBERSOURCE => '20',
                Gateway::FIRST_DATA  => '10'
            ];
        }

        return [
            Gateway::BILLDESK => '50',
            Gateway::EBS      => '40'
        ];
    }
}
