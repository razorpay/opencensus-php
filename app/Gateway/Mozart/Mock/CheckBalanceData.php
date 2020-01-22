<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class CheckBalanceData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'ResponseCode'          => '000',
                    'amount'                => '100000',
                    'AppName'               => 'MerchantName',
                    'TransactionIdentifier' => '3479278',
                    '_raw'                  => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}
