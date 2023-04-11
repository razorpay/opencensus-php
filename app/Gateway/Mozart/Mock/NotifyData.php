<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Error\ErrorCode;

class NotifyData
{
    public function upi_icici($entities)
    {
        $response = [
            'data' => [
                'status_code'       => '0',
                'npci_reference_id' => '615519221396', // Bank RRN
                'umn'               => $entities['upi_mandate']['umn'],
                'merchantId'        => '106161',
                'subMerchantId'     => '12234',
                'terminalId'        => '5411',
                'BankRRN'           => '615519221396',
                'merchantTranId'    => '612411454593',
                'amount'            => $entities['payment']['amount'],
                'success'           => 'true',
                'message'           => 'Transaction Successful',
                '_raw' => '{"response" : "0","merchantId" : "106161","subMerchantId" : "12234", "terminalId" : "5411", "BankRRN" : "615519221396", "merchantTranId" : "612411454593", "amount" : "12", "success" : "true", "message" : "Transaction Successful"}',
             ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        switch ($entities['payment']['description'])
        {
            case 'notify_fails_twice':
                if ($entities['upi']['gateway_data']['ano'] < 3)
                {
                    $response['data']['status_code'] = '08';
                    $response['success'] = false;
                    $response['error'] = [
                        'gateway_error_code'        => '08',
                        'gateway_error_description' => 'PSP DOWN',
                        'internal_error_code'       => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
                    ];
                }
                break;

            case 'notify_fails':
                $response['data']['status_code'] = '08';
                $response['success'] = false;
                $response['error'] = [
                    'gateway_error_code'        => '08',
                    'gateway_error_description' => 'PSP DOWN',
                    'internal_error_code'       => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
                ];
                break;

            case 'notify_fails_revoke':
                $response['data']['status_code'] = 'VA';
                $response['status_code'] = 'VA';
                $response['success'] = false;
                $response['error'] = [
                    'gateway_error_code'        => 'VA',
                    'gateway_error_description' => 'MANDATE HAS BEEN REVOKED',
                    'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REVOKED,
                ];
                break;
        }

        return $response;
    }

    public function paysecure($entities)
    {
        return [
            "data" => [
                'status' => 'success',
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];
    }
}
