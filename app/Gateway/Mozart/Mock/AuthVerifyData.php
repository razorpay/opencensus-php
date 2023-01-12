<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class AuthVerifyData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'ResponseCode'          => '000',
                    'MobileNumber'          => '93884739457',
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

    public function upi_mindgate($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['payment']['id'],
                'bank_payment_id' => '999999',
                'mandate_amount'  => $entities['upi_mandate']['max_amount'],
                'status'          => 'callback_successful',
                'umn'             => $entities['payment']['id'] . '@hdfcbank',
                'rrn'             => '012345678912',
                'npci_txn_id'     => 'HDFC00001124',
            ],
        ];

        return $response;
    }

    public function upi_icici($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['payment']['id'],
                'bank_payment_id' => '999999',
                'mandate_amount'  => $entities['upi_mandate']['max_amount'],
                'status'          => 'callback_successful',
                'status_desc'     => 'Debit Success   |ZM|Valid MPIN',
                'umn'             => $entities['payment']['id'] . '@icici',
                'rrn'             => '012345678912',
                'npci_txn_id'     => 'HDFC00001124',
                'gateway_data'    => [
                    'id'          => $entities['gateway']['redirect']['merchantTranId'],
                ],
                'upi'             => [
                    'vpa'           => $entities['gateway']['redirect']['PayerVA']
                ]
            ],
        ];

        return $response;
    }

    public function wallet_paypal($entities)
    {
        try
        {
            $response = [
                'data' => [
                    'amount'    => $entities['payment']['amount'],
                    'paymentId' => $entities['payment']['id'],
                    'PayId'     => $entities['gateway']['redirect']['PayId'],
                    'status'    => $entities['gateway']['redirect']['status'],
                    'token'     => $entities['gateway']['redirect']['token'],
                ],
                'error' => null,
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => true
            ];
        }
        catch (\Exception $e)
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'status' => 'callback_failed'
                ],
                'error' => [
                    'description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_error_code' => '',
                    'gateway_error_description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_status_code' => 0,
                    'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
                ],
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => false
            ];
        }

        return $response;
    }

    public function billdesk_sihub($entities)
    {
        return [
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
            'data'              => [
                'amount'    => 6,
                'currency'  => 356,
                'id'        => 'VkHYuA3NH3',
                'status'    => 'pending'
            ],
        ];
    }
}
