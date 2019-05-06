<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartRouting as BaseSmartRouting;

class SmartRouting extends BaseSmartRouting
{

    public function sendNonBlockingRequest($url, $data = null)
    {
        return;
    }

    public function sendRequest($url, $method, $data = null)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
