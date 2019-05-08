<?php

namespace RZP\Gateway\Mozart\Mock;

class VerifyRefundData
{
    public static function bajajfinserv($entities)
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

    public static function wallet_phonepe($entities)
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

}
