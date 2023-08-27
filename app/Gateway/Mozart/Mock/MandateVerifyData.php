<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class MandateVerifyData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function payu($entities)
    {
        $response = [
            'data' =>
                [
                    "action" => "check_mandate_status",
                    "amount" => 250,
                    "mandate_end_date" => "2023-09-22",
                    "mandate_id" => $entities['card_mandate']['mandate_id'],
                    "mandate_start_date" => "2023-06-25",
                    "mandate_status" => "active",
                    "status" => "mandate_verify_successful",
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

}

