<?php

namespace RZP\Services\Mock;

use RZP\Services\Drip as BaseDrip;

class Drip extends BaseDrip
{
    /**
     * Returning null as mocked drip response
     */
    protected function sendRequest($url, $data, $method)
    {
        return null;
    }
}
