<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart;

class PayInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'Errordescription' => 'SUCCESS (OTP First Process Completed Succesfully)',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'MobileNo' => '2376',
                    'RequestID' => 'RZP190219162906767',
                    'Responsecode' => '0',
                    'status' => 'OTP_sent',
                    '_raw' => '',
                ],
            'next' => [
                'redirect' => [
                    'content' => [
                        'type' => 'otp',
                        'bank' => '',
                        'next' => [
                            'submit_otp',
                        ]
                    ],
                    'method' => 'post',
                    'url' => 'www.test.com',
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $response = [
            'data' => [],
            'next' => [
                'redirect' => [
                    'content' => [
                        'QS' => 'hBLDFfLDIA91WLou0nWL2BEH3fRBjwk0Vo/yi9soOtmEEsMV8sMgD3VYui7SdYvY4k6AnwcCQqgx+0FxvJLK5YW/AAMuPdhJV0/UDXQ/Xz8O/flBUz9SuiG39um6aA6t2BfJPTuLuDwe+6NyGTqJiW32pkLlLqGdlAoW3VNMiAoJ1B9VOjBTWnheSswTyCB1zg07KIZ2yFAxk/BKm0MmHx3i9O3+AWETmblbvmkZuMj0w7VUBpqHvd1cuLby9thB61WSaaH8t64OczIrmRjcMA=='

                    ],
                    'method' => 'post',
                    'url' => 'www.test.com',
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            "external_trace_id" => "DUMMY_REQUEST_ID",
        ];

        return $response;
    }
}