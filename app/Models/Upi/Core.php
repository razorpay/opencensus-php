<?php

namespace RZP\Models\Upi;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function callUpiGateway($gateway, $method, array $gatewayData)
    {
        try
        {
            $response = $this->app['gateway']->call($gateway, $method, $gatewayData, $this->mode);

            return ['success'=>true, 'txnId'=>$response['txn_id'], 'msgId' => $response['msg_id']];
        }
        catch (\Exception $ex)
        {
            return ['success'=>false, 'msg' => $ex->getMessage()];
        }
    }
}
