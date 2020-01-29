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

        if (isset($entities['reconRequest']['meta_data']['currency_missmatch'])){
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH,
                '',
                '',
                ['message' => 'Currency Missmatch']
            );
        }

        $response = [
            'data' =>
                [
                    'records' =>[
                        [
                            'Response' =>
                                [
                                    'Amount'                    => '1.00',
                                    'Custom_Id'                 => 'DJEN97tL54dTIN',
                                    'Gateway_Merchant_ID'       => 'SPSZR25DLBKN6',
                                    'Gateway_Transaction_ID'    => '74X988560K9095031',
                                    'Method'                    => 'PAYPAL',
                                    'PayPal_Charges'            => '0.04',
                                    'Payment_Initiation_Time'   => '2019-10-04T12:52:36+00:00',
                                    'RZP_Transaction_ID'        => 'DJEN97tL54dTIN',
                                    'Type'                      => 'PAYMENT',
                                    'currency_code'             => 'USD',
                                ]
                        ],
                        [
                            'Response' =>
                                [
                                    'Amount'                    => '1.00',
                                    'Custom_Id'                 => 'DJET5t3wjBaQI1',
                                    'Gateway_Merchant_ID'       => 'SPSZR25DLBKN6',
                                    'Gateway_Transaction_ID'    => '4E238155FF697490G',
                                    'Method'                    => 'PAYPAL',
                                    'PayPal_Charges'            => '-0.05',
                                    'Payment_Initiation_Time'   => '2019-09-17T12:12:54+00:00',
                                    'RZP_Transaction_ID'        => 'DJGIIHMST4i8G4',
                                    'Type'                      => 'REFUND',
                                    'currency_code'             => 'USD',
                                ]
                        ],
                        [
                            'Response' =>
                                [
                                    "record" =>
                                        [
                                            "account_id" => "DJET5t3wjBaQI1",
                                            "amount"     =>
                                                [
                                                    "currency_code" => "USD",
                                                    "value"         => "9.99"
                                                ],
                                            "custom_field"      => "DJET5t3wjBaQI1",
                                            "id"                => "4E238155FF697490G",
                                            "initiation_time"   => "2019-12-03T09:11:12+00:00",
                                            "invoice_id"        => "DJET5t3wjBaQI1",
                                            "merchant_timezone" => "Asia/Calcutta",
                                            "partner_timezone"  => "Asia/Calcutta",
                                            "reference_id"      => "SPSZR25DLBKN6",
                                            "seller_amount"     =>
                                                [
                                                    "currency_code" => "USD",
                                                    "value"         => "-9.99"
                                                ],
                                            "type" => "DISPUTE"
                                        ]

                                ]
                        ],
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

    public function getsimpl($entities)
    {
        if (isset($entities['reconRequest']['meta_data']['gateway_failure'])){
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                '',
                '',
                ['message' => 'Request to gateway failed']
            );
        }

        if (isset($entities['reconRequest']['meta_data']['currency_missmatch'])){
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH,
                '',
                '',
                ['message' => 'Currency Missmatch']
            );
        }

        $response = [
            'data' =>
                [
                    'records' =>[
                                [
                                    'amount_in_paise' => '100',
                                    'order_id' => 'DXSh0fhShgw6np',
                                    'phone_number'=> '1192521000',
                                    'status'=> 'REFUND',
                                    'transaction_id'=> 'e7f87958-abea-4f94-b8d5-b4ccf677e9e2',
                                ],
                                [
                                    'amount_in_paise'=> '100',
                                    'order_id'=> 'DXSg7YJuXEQs5Q',
                                    'phone_number'=> '1192521000',
                                    'status'=> 'CLAIMED',
                                    'transaction_id'=> '4478925e-5139-4c34-84a7-24858d51fc2c',
                                ]
                    ],
                    'status' => 'recon_successful',
                    'Http_status' => '200',
                    '_raw' => '',
                    'total_pages' => 2,
                    'current_page' => '1',
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