<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Bob;

class Gateway extends Bob\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }

    public function reconcile($input)
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
