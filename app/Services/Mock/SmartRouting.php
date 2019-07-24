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

    public function createGatewayRule($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function updateGatewayRule($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];    }

    public function deleteGatewayRule($id)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
