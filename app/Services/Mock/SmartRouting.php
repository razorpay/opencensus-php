<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartRouting as BaseSmartRouting;

class SmartRouting extends BaseSmartRouting
{

    public function sendPaymentData($data)
    {
        return null;
    }

    public function createOrUpdateGatewayDowntimeData($data)
    {
        return null;
    }

    public function deleteGatewayDowntimeData($data)
    {
        return null;
    }

    public function sendAuthNPaymentData($data)
    {
        return null;
    }

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

    public function deleteGatewayRule($id, $group, $step)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
