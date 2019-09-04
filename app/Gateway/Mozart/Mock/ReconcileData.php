<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class ReconcileData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function netbanking_bob($entities)
    {
        $response = [
            'data' =>
                [
                    'records' => [
                        [
                            "ACC_NUM" => "21180100010529",
                            "BANKID" => "BOB",
                            "CRN"=> "INR",
                            "ITC"=> "RAZORPAY",
                            "PRN"=> "D85nLQUuW4i5Jp",
                            "REFNUM"=> "108114286",
                            "STATUS"=> "SUC",
                            "TXN_AMT"=> "INR|1.00",
                        ],
                        [
                            "ACC_NUM" => "21180100010529",
                            "BANKID" => "BOB",
                            "CRN"=> "INR",
                            "ITC"=> "RAZORPAY",
                            "PRN"=> "D85nLQUuW4i5Jp",
                            "REFNUM"=> "108114286",
                            "STATUS"=> "SUC",
                            "TXN_AMT"=> "INR|1.00",
                        ],
                        [
                            "ACC_NUM" => "21180100010529",
                            "BANKID" => "BOB",
                            "CRN"=> "INR",
                            "ITC"=> "RAZORPAY",
                            "PRN"=> "D85nLQUuW4i5Jp",
                            "REFNUM"=> "108114286",
                            "STATUS"=> "SUC",
                            "TXN_AMT"=> "INR|1.00",
                        ],
                    ],
                    'status' => 'recon_successful',
                    '_raw' => '',
                ],
            'next' => [],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}