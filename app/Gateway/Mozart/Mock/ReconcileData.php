<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Base;
use RZP\Trace\TraceCode;

class ReconcileData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function netbanking_bob($entities)
    {
        if (isset($entities['reconRequest']['meta_data']['gateway_failure'])){
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                '',
                '',
                ['message' => 'Request to gateway failed']
            );
        }

        $response = [
            'data' =>
                [
                    'records' => [
                        [
                            'Response' =>
                                [
                                    'ACC_NUM'   => '21180100010529',
                                    'PRN'       => 'D85nLQUuW4i5Jp',
                                    'REFNUM'    => '99999',
                                    'STATUS'    => 'SUC',
                                    'TXN_AMT'   => 'INR|1.00',
                                ],
                        ],
                        [
                            'Response' =>
                                [
                                    'ACC_NUM'   => '21180100010529',
                                    'PRN'       => 'D85nLQUuW4i5Jp',
                                    'REFNUM'    => '99999',
                                    'STATUS'    => 'SUC',
                                    'TXN_AMT'   => 'INR|1.00',
                                ],
                        ],
                        [
                            'Response' =>
                                [
                                    'ACC_NUM'   => '21180100010529',
                                    'PRN'       => 'D85nLQUuW4i5Jp',
                                    'REFNUM'    => '99999',
                                    'STATUS'    => 'SUC',
                                    'TXN_AMT'   => 'INR|1.00',
                                ],
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
        if (isset($entities['reconRequest']['meta_data']['gateway_failure'])){
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                '',
                '',
                ['message' => 'Request to gateway failed']
            );
        }

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
                    'records' =>[
                        [
                            'Response' =>
                                [
                                    'Payment Id'    => 'DEelpRi0HMBGOi',
                                    'Payment Amount'=> '1.00',
                                    'Bank Ref No'   => '108114286',
                                    'Payment Date'  => '2019-09-04',
                                ],
                        ],
                        [
                            'Response' =>
                                [
                                    'Payment Id'    => 'DEelpRi0HMBGOi',
                                    'Payment Amount'=> '1.00',
                                    'Bank Ref No'   => '108114286',
                                    'Payment Date'  => '2019-09-04',
                                ],
                        ],
                        [
                            'Response' =>
                                [
                                    'Payment Id'    => 'DEelpRi0HMBGOi',
                                    'Payment Amount'=> '1.00',
                                    'Bank Ref No'   => '108114286',
                                    'Payment Date'  => '2019-09-04',
                                ],
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

    public function wallet_paypal($entities)
    {
        if (isset($entities['reconRequest']['meta_data']['gateway_failure'])){
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                '',
                '',
                ['message' => 'Request to gateway failed']
            );
        }

        $response = [
            'data' =>
                [
                    'records' =>[
                        [
                            'Response' =>
                                [
                                    'Amount' => '1.00',
                                    'PayPal_Charges' => '0.04',
                                    'Custom_Id' => 'DJEN97tL54dTIN',
                                    'Gateway_Merchant_ID'=> 'SPSZR25DLBKN6',
                                    'Gateway_Transaction_ID'=> '74X988560K9095031',
                                    'Method'=> 'PAYPAL',
                                    'RZP_Transaction_ID'=> 'DJEN97tL54dTIN',
                                    'Type'=> 'PAYMENT',
                                    'currency_code'=> 'USD',
                                    'Payment_Initiation_Time' => '2019-10-04T12:52:36+00:00'
                                ]
                        ]
                    ],
                    'status' => 'recon_successful',
                    '_raw' => '',
                    'total_pages' => 2,
                    'current_page' => '1',
                    'total_items' => 4,
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