<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartRouting as BaseSmartRouting;

class SmartRouting extends BaseSmartRouting
{

    public function sendPaymentData($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function createGateway($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function updateGateway($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function deleteGateway($id)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
