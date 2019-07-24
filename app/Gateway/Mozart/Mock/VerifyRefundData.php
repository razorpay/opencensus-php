<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class VerifyRefundData extends Base\Mock\Server
{
    public function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        '0' => [
                            'DEALID' => 'CS905114097404',
                            'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'Key' => $entities['terminal']['gateway_secure_secret'],
                            'ORDERNO' => '104',
                            'REQUESTID' => '1234',
                            'RESPONSECODE' => '0'
                        ]
                    ],
                    'received' => true,
                    'requeryid' => '1234',
                    'reqid' => 'RZP200219195445345',
                    'rescode' => '00',
                    'rqtype' => 'CAN',
                    'status' => 'verification_successful',
                    'valkey' => $entities['terminal']['gateway_secure_secret'],
                    'errdesc' => 'SUCCESS',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function wallet_phonepe($entities)
    {
        $response = [
            'data'=>
                [
                    '_raw'=> '',
                    'code'=> 'PAYMENT_SUCCESS',
                    'data'=> [
                        'amount'=> $entities['payment']['amount'],
                        'merchantId'=> 'abc',
                        'payResponseCode'=> 'SUCCESS',
                        'paymentState'=> 'COMPLETED',
                        'providerReferenceId'=> 'phonepeProviderRefId',
                        'transactionId'=> $entities['refund']['id'],
                    ],
                    'message'=> 'Your payment is successful.',
                    'received'=> true,
                    'status'=> 'verification_successful',
                    'success'=> true
                ],
            'error'=> null,
            'external_trace_id'=> '',
            'mozart_id'=> '',
            'next'=> [],
            'success'=> true,
        ];
        return $response;
    }

    public function wallet_paypal($entities)
    {
        $response = [
            'data'=>
                [
                    '_raw'=> '',
                    'code'=> 'PAYMENT_SUCCESS',
                    'data'=> [
                        'amount'=> $entities['payment']['amount'],
                        'merchantId'=> 'abc',
                        'payResponseCode'=> 'SUCCESS',
                        'paymentState'=> 'COMPLETED',
                        'providerReferenceId'=> 'phonepeProviderRefId',
                        'transactionId'=> $entities['refund']['id'],
                    ],
                    'message'=> 'Your payment is successful.',
                    'received'=> true,
                    'status'=> 'verification_successful',
                    'success'=> true
                ],
            'error'=> null,
            'external_trace_id'=> '',
            'mozart_id'=> '',
            'next'=> [],
            'success'=> true,
        ];
        return $response;
    }

    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'rrn' => '987654321',
                    'txnStatus' => 'SUCCESS',
                    'hdnOrderID' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'hash' => 'abcd',
                    '_raw' => '{\"rrn\":\"910501000856\",\"txnStatus\":\"SUCCESS\",\"hdnOrderID\":\"ablxasaasbajahskajkg\",\"hash\":\"6256e8a43ba4e56eac1ef8c1faaad0c7236595e3638d74dd7c30e787dc00235624a5d2920230cf5478c88d616474abd1185c236b3c30107f7c931fb7070e20d9\",\"messageText\":\"\",\"code\":\"0\",\"errorCode\":\"000\",\"txnId\":\"ablxasaasbajahskajkg\"',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'verify_refund');

        return $response;
    }

}
