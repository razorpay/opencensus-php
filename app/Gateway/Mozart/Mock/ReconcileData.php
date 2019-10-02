<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Trace\TraceCode;

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
                            'ACC_NUM'   => '21180100010529',
                            'BANKID'    => 'BOB',
                            'CRN'       => 'INR',
                            'ITC'       => 'RAZORPAY',
                            'PRN'       => 'D85nLQUuW4i5Jp',
                            'REFNUM'    => '108114286',
                            'STATUS'    => 'SUC',
                            'TXN_AMT'   => 'INR|1.00',
                        ],
                        [
                            'ACC_NUM'   => '21180100010529',
                            'BANKID'    => 'BOB',
                            'CRN'       => 'INR',
                            'ITC'       => 'RAZORPAY',
                            'PRN'       => 'D85nLQUuW4i5Jp',
                            'REFNUM'    => '108114286',
                            'STATUS'    => 'SUC',
                            'TXN_AMT'   => 'INR|1.00',
                        ],
                        [
                            'ACC_NUM'   => '21180100010529',
                            'BANKID'    => 'BOB',
                            'CRN'       => 'INR',
                            'ITC'       => 'RAZORPAY',
                            'PRN'       => 'D85nLQUuW4i5Jp',
                            'REFNUM'    => '108114286',
                            'STATUS'    => 'SUC',
                            'TXN_AMT'   => 'INR|1.00',
                        ],
                    ],
                    'status' => 'recon_successful',
                    '_raw' => '',
                ],
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function netbanking_cub($entities)
    {
        $this->trace->info(TraceCode::GATEWAY_RECONCILE_REQUEST, [$entities]);
        if (isset($entities['reconRequest']['meta_data']['return_no_records'])){
            return [
                'data'              => [
                ],
                'next'              => [],
                'error'             => null,
                'success'           => true,
                'mozart_id'         => '',
                'external_trace_id' => '',
            ];
        }
        $response = [
            'data' =>
                [
                    'records' => [
                        [
                            'Payment Id'    => 'DEelpRi0HMBGOi',
                            'Payment Amount'=> '1.00',
                            'Bank Ref No'   => '108114286',
                            'Payment Date'  => '2019-09-04',
                        ],
                        [
                            'Payment Id'    => 'DEelpRi0HMBGOi',
                            'Payment Amount'=> '1.00',
                            'Bank Ref No'   => '108114286',
                            'Payment Date'  => '2019-09-04',
                        ],
                        [
                            'Payment Id'    => 'DEelpRi0HMBGOi',
                            'Payment Amount'=> '1.00',
                            'Bank Ref No'   => '108114286',
                            'Payment Date'  => '2019-09-04',
                        ],
                    ],
                    'status' => 'recon_successful',
                    '_raw' => '',
                ],
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}