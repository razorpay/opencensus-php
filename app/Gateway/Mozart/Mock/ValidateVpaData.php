<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class ValidateVpaData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function upi_mindgate($entities)
    {
        return [
            'data' =>
                [
                    'isVpaValid' => 'Y',
                    'vpa' => 'test@hdfcbank.com',
                    'payer_name' => 'MYBANKTESTCUSTOMER',
                    'statusDesc' => 'VPAisavailablefortransaction',
                    'errCode' => 'MD525',
                    '_raw' => ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];
    }
}
