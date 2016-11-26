<?php

namespace RZP\Models\Upi;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Upi\Npci\Gateway as UpiGateway;

class Service extends Base\Service
{
    public function __construct()
    {
        $this->gateway = new UpiGateway;
        parent::__construct();
    }

    public function makeGatewayRequest($method, array $params)
    {
        try
        {
            list($txnId, $msgId) = $this->gw->makeRequest($method, $params);

            return ApiResponse::json(['success'=>true, 'txnId'=>$txnId, 'msgId' => $msgId]);
        }

        catch(\Exception $e)
        {
            return ApiResponse::json(['success'=>false, 'msg' => $e->getMessage()]);
        }
    }
}
