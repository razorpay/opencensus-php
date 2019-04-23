<?php

namespace RZP\Gateway\Mozart\Mock;

class VerifyData
{
    public static function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'enqinfo' =>[
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
                    'reqid' => 'RZP200219195445344',
                    'rescode' => '00',
                    'rqtype' => 'AUTH',
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
}