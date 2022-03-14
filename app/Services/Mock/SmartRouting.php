<?php

namespace RZP\Services\Mock;

use Carbon\Carbon;
use RZP\Services\SmartRouting as BaseSmartRouting;

class SmartRouting extends BaseSmartRouting
{

    public function sendPaymentData($data)
    {
        return null;
    }

    public function syncBuyPricingRules($data)
    {
        return null;
    }

    public function createOrUpdateGatewayDowntimeData($data)
    {
        return $data;
    }

    public function deleteGatewayDowntimeData($data)
    {
        return null;
    }

    public function sendRequest($action, $data = null, $id = null, $params = null, $timeout = null)
    {
        if($action['url'] =='/fetch_downtime'){
            $begin = Carbon::now()->subMinutes(60)->timestamp;
            $end   = Carbon::now()->addMinutes(60)->timestamp;
            return [
                'id'          => 'downtime123',
                'begin'       => $begin,
                'end'         => $end,
                'gateway'     => 'axis_migs',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'source'      => 'other',
                'acquirer'    => 'axis',
                'network'     => 'VISA',
                ];
        }

        if ($action['url'] =='/resolve_downtime'){
            return null;
        }

        return  $data;

    }

    public function refreshSmartRoutingCache()
    {
        return ['response'=>['status_code'=>200]];
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
