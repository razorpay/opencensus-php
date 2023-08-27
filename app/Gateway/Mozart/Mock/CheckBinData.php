<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class CheckBinData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function payu($entities)
    {
        $response = [
            'data' =>
                [
                    'bin_data' => [
                        'additonalCardType' => null,
                        'authorized_by_bank' => '0',
                        'bin' => '999999',
                        'card_type' => 'RUPAY',
                        'category' => 'debitcard',
                        'is_atmpin_card' => '1',
                        'is_domestic' => '1',
                        'is_otp_on_the_fly' => 0,
                        'is_si_supported' => 1,
                        'is_zero_redirect_supported' => 0,
                        'issuing_bank' => null,
                        'pg_id' => null
                    ],
                    'status' => 'check_bin_successful',
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

