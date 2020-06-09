<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;

class PayVerifyData extends Base\Mock\Server
{
    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'rrn' => '09321',
                    'txnStatus' => 'SUCCESS',
                    'paymentId' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'] / 100,
                    'hash' => 'abcd',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'callback');

        return $response;
    }

    public function upi_citi($entities)
    {
        $data = $entities['gateway']['redirect']['PushNotificationToSSG'];

        $errors = [
            'ZA' => [
                'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                'gateway_error_code'        => 'ZA',
                'gateway_error_description' => 'TRANSACTION DECLINED BY CUSTOMER',
            ],
        ];

        $response = [
            'data' =>
                [
                    'NPCITxnId' => $data['NPCITxnId'] ?? null,
                    'paymentId' => $entities['payment']['id'],
                    'amount'    => intval(floatval($data['SettlementAmount']) * 100),
                    '_raw' => '',
                ],
            'error'             => $errors[$data['RespCode']] ?? null,
            'success'           => $data['RespCode'] === '00',
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'callback');

        return $response;
    }

    public function upi_juspay($entities)
    {
        $response = [
                'data' =>
                    [
                        '_raw' => '{"amount":"100.00","customResponse":"{}","expiry":"2016-11-25T00:10:00+05:30","gatewayReferenceId":"806115044725","gatewayResponseCode":"00","gatewayResponseMessage":"Transaction is approved","gatewayTransactionId":"XYZd0c077f39c454979...","merchantChannelId":"DEMOUATAPP","merchantId":"DEMOUAT01","merchantRequestId":"TXN1234567","payeeVpa":"merchant@abc","payerName":"Customer Name","payerVpa":"customer@xyz","transactionTimestamp":"2016-11-25T00:00:00+05:30","type":"MERCHANT_CREDITED_VIA_COLLECT","udfParameters":"{}"}',
                        'paymentId' => $entities['payment']['id'],
                        'amount' => $entities['payment']['amount'],
                        'customResponse' => '{}',
                        'expiry' => '2016-11-25T00:10:00+05:30',
                        'gatewayReferenceId' => '806115044725',
                        'gatewayResponseMessage' => 'Transaction is approved',
                        'gatewayResponseCode' => '00',
                        'gatewayTransactionId' => 'XYZd0c077f39c454979...',
                        'merchantChannelId' => 'DEMOUATAPP',
                        'merchantId' => 'DEMOUAT01',
                        'merchantRequestId' => $entities['payment']['id'],
                        'payeeVpa' => 'merchant@abc',
                        'payerName' => 'Customer Name',
                        'payerVpa' => 'customer@xyz',
                        'status' => 'collect_successful',
                        'transactionTimestamp' => '2016-11-25T00:00:00+05:30',
                        'type' => 'MERCHANT_CREDITED_VIA_COLLECT',
                        'udfParameters' => '{}',
                    ],
                'error' => NULL,
                'external_trace_id' => 'DUMMY_REQUEST_ID',
                'mozart_id' => 'DUMMY_MOZART_ID',
                'next' => [],
                'success' => true
            ];


        switch ($entities['gateway']['redirect']['body']['gatewayResponseCode']) {
            case 'U69':
                $response['success'] = false;
                $response['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED';
                break;
        }

        return $response;
    }

    public function netbanking_yesb($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                    '_raw'            => 'dummy_raw_value',
                    'paymentId'       => $entities['gateway']['redirect']['paymentId'],
                    'bank_payment_id' => '999999',
                    'amount'          => $entities['gateway']['redirect']['amount'],
                    'status'          => 'callback_successful',
                ],
            ];

        return $response;
    }

    public function netbanking_kvb($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['gateway']['redirect']['paymentId'],
                'bank_payment_id' => '1234',
                'amount'          => $entities['gateway']['redirect']['amount'],
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
            'error' => null,
            'data' => [
                'paymentId' => $entities['payment']['id'],
                'amount' => $entities['payment']['amount'] / 100,
                'bank_payment_id' => '999999',
                'status' => 'callback_successful',
                '_raw' => null
                ],
            ];

        return $response;
    }

    public function netbanking_ubi($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [],
            'success'           => true,
            'error'             => null,
            'data' => [
                'paymentId'         => $entities['payment']['id'],
                'amount'            => $entities['payment']['amount'] / 100,
                'bank_payment_id'   => '999999',
                'payment_status'    => 'Y',
                'status'            => 'callback_successful',
                '_raw'              => null
            ],
        ];
        return $response;
    }

    public function netbanking_scb($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [],
            'success'           => true,
            'error'             => null,
            'data' => [
                'paymentId'         => $entities['payment']['id'],
                'amount'            => $entities['payment']['amount'],
                'bank_payment_id'   => '999999',
                'transaction_id'    => '1234',
                'payment_status'    => 'Y',
                'status'            => 'callback_successful',
                '_raw'              => null
            ],
        ];
        return $response;
    }

    public function netbanking_cbi($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
            'error' => null,
            'data' => [
                'paymentId' => $entities['payment']['id'],
                'amount' => $entities['payment']['amount'] / 100,
                'bank_payment_id' => '999999',
                'status' => 'callback_successful',
                '_raw' => []
            ],
        ];

        return $response;
    }

    public function netbanking_cub($entities)
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
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    public function netbanking_ibk($entities)
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
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    public function upi_sbi($entities)
    {
        $response = [
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'gateway_response'=> [
                    'pspRefNo'      => $entities['gateway']['redirect']['payment_id'],
                    'upiTransRefNo' => 99999,
                    'npciTransId'   => 99999999999,
                    'custRefNo'     => "99999999999",
                    'amount'        => 500,
                    'txnAuthDate'       => "2020-06-01 19:23:51",
                    'responseCode'      => "00",
                    'approvalNumber'    => 840600,
                    'status'            => "S",
                    'statusDesc'        => "Payment Successful",
                    'addInfo'           => [
                        'addInfo2'          => "7971807546",
                        'statusDesc'        => "status description in addInfo not expected from gateway, but we
                                                                       still need to remove before making database call, because our
                                                                       poor database can only take 255 characters and gateway can still
                                                                       send a very large data in addInfo, Off course same applies
                                                                       for addInfo2, but since this contract is different story we are
                                                                       fine with db failure",
                    ],
                    'payerVPA' => "vishnu@icici",
                    'payeeVPA' => "razorpay@sbi",
                ],
                'amount' => 500,
                'paymentId'       => $entities['gateway']['redirect']['payment_id'],
                'bank_payment_id' => '999999',
                'status'          => 'callback_successful',
            ],
        ];

        if($entities['gateway']['redirect']['vpa'] === 'rejectedcollect@sbi'){
            $response['data']['gateway_response']['status'] = 'R';
            $response['success'] = false;
            $response['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED';
        }

        if(isset($entities['gateway']['redirect']['amount'])){
            $response['data']['gateway_response']['amount'] = $entities['gateway']['redirect']['amount'];
        }

        if(isset($entities['gateway']['redirect']['upiTransRefNo'])){
            $response['data']['gateway_response']['upiTransRefNo'] = $entities['gateway']['redirect']['upiTransRefNo'];
        }

        return $response;
    }

    public function netbanking_idbi($entities)
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
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    public function bajajfinserv($entities)
    {
        $otp = $entities['gateway']['redirect']['otp'];

        switch($otp)
        {
            case 111111:
                $response = [
                    'data' =>
                        [
                            'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'MobileNo' => '2376',
                            'RequestID' => 'RZP190219162906768',
                            'Responsecode' => '0',
                            'status' => 'created',
                            'OrderNo' => '104',
                            'DealID' => 'CS905114097404',
                            '_raw' => '',
                        ],
                    'error'             => null,
                    'success'           => true,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
                break;
            default:
                $response = [
                    'data' =>
                        [
                            'Errordescription' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                            'MobileNo' => '2376',
                            'RequestID' => 'RZP190219162906768',
                            'Responsecode' => 'L3',
                            'status' => 'creation_failed',
                            'OrderNo' => '104',
                            'DealID' => '905909104900',
                            '_raw' => '',
                        ],
                    'error'             => [
                        'description' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                        'gateway_error_code' => 'L3',
                        'gateway_error_description' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                        'gateway_status_code' => 200,
                        'internal_error_code' => 'BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED',
                    ],
                    'success'           => false,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
        }

        return $response;
    }

    public function cred($entities)
    {
        $response = [
            'data' =>
                    [
                        'paymentId' => $entities['payment']['id'],
                        'gatewayTransactionId' => '123ase!234',
                        'amount'  => $entities['payment']['amount'],
                        'status' => 'BLOCKED',
                        '_raw' => '{"response": {"tracking_id": "<PARTNER_ORDER_ID\/MERCHANT_ORDER_ID>","reference_id": "<CRED_REF_ID>","state": "<ORDER_STATE>","expiry_time": "<TIME_IN_EPOCH>","amount": {"currency": "INRPAISE","value": 10000},"refunds": [{"tracking_id": "<REFUND_ID>","reference_id": "<CRED_REF_ID>","state": "<REFUND_STATE>","amount": {"value": 10000,"currency": "INRPAISE"}}]},"metadata": {"key": "value"},"status": "200","error_code": "","error_message": "","error_description": ""}',
                    ],
                'error' => NULL,
                'external_trace_id' => 'DUMMY_REQUEST_ID',
                'mozart_id' => 'DUMMY_MOZART_ID',
                'next' => [
                    'redirect' => [
                           'method' => 'post',
                           "url" => "cred://pay?am=100.00&cu=INRPAISE&mc=5411"
                       ]
                ],
                'success' => true,
        ];

        return $response;
    }

    public function wallet_phonepe($entities)
    {
        if (isset($entities['gateway']['redirect']['data']) == true)
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'amount' => intval($entities['gateway']['redirect']['data']['amount']),
                    'code' => $entities['gateway']['redirect']['code'],
                    'merchantId' => $entities['gateway']['redirect']['data']['merchantId'],
                    'paymentId' => $entities['gateway']['redirect']['data']['transactionId'],
                    'providerReferenceId' => $entities['gateway']['redirect']['data']['providerReferenceId'],
                    'status' => 'callback_successfull'
                ],
                'error' => null,
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => $entities['gateway']['redirect']['success']
            ];

            return $response;
        }

        try
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'amount' => intval($entities['gateway']['redirect']['amount']),
                    'checksum' => $entities['gateway']['redirect']['checksum'],
                    'code' => $entities['gateway']['redirect']['code'],
                    'merchantId' => $entities['gateway']['redirect']['merchantId'],
                    'paymentId' => $entities['gateway']['redirect']['paymentId'],
                    'providerReferenceId' => $entities['gateway']['redirect']['providerReferenceId'],
                    'status' => 'callback_successfull'
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

    public static function wallet_phonepeswitch($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw '               => '',
                    'code'                => 'PAYMENT_SUCCESS',
                    'amount'              => $entities['payment']['amount'],
                    'merchantId'          => 'abc',
                    "data_status"         => "SUCCESS",
                    'payResponseCode'     => 'SUCCESS',
                    'providerReferenceId' => 'phonepeProviderRefId',
                    'transactionId'       => $entities['payment']['id'],
                    'paymentId'           => $entities['payment']['id'],
                    'message'             => 'Your payment is successful.',
                    'received'            => true,
                    'status'              => 'verification_successful',
                    'success'             => true
                ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => '',
            'next'              => [],
            'success'           => true,
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

    public function  upi_mindgate($entities)
    {
        switch ($entities['gateway']['redirect']['mandateDtls'][0]['callback_type'])
        {
            case 'MANDATE_STATUS':
                return $this->mandateCreateCallbackDataUpiMindgate($entities);
            case 'MANDATE_UPDATE':
                return $this->mandateUpdateCallbackDataUpiMindgate($entities);
            default:
                throw new Exception\LogicException(
                    'Invalid gateway passed for prcessing S2S callback');
        }
    }

    protected function mandateCreateCallbackDataUpiMindgate($entities)
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
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    protected function mandateUpdateCallbackDataUpiMindgate($entities)
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
                'amount'          => 70000,
                'status'          => 'callback_successful',
                'start_time'      => 1893456000,
            ],
        ];

        return $response;
    }

    public function netbanking_jsb($entities)
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
                'amount'          => $entities['payment']['amount'],
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }
}
