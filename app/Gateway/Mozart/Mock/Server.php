<?php

namespace RZP\Gateway\Cybersource\Mock;

use Str;
use RZP\App;
use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Cybersource\Fields as F;

class Server extends Base\Mock\Server
{

    public function payInit($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'Errordescription' => 'SUCCESS (OTP First Process Completed Succesfully)',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'MobileNo' => '2376',
                    'RequestID' => 'RZP190219162906767',
                    'Responsecode' => '0',
                    'status' => 'OTP_sent',
                    '_raw' => '',
                ],
            'next' => [
                'redirect' => [
                    'content' => [
                        'type' => 'otp',
                        'bank' => '',
                        'next' => [
                            'submit_otp',
                        ]
                    ],
                    'method' => 'post',
                    'url' => 'www.test.com',
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'pay_init');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function payVerify($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
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

        $this->content($response, 'pay_verify');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function verify($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        'DEALID' => 'CS905114097404',
                        'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                        'Key' => $entities['terminal']['gateway_secure_secret'],
                        'ORDERNO' => '104',
                        'REQUESTID' => $entities['gateway']['pay_verify']['requestid'],
                        'RESPONSECODE' => '0'
                    ],
                    'received' => true,
                    'requeryid' => $entities['gateway']['pay_verify']['requestid'],
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

        $this->content($response, 'verify');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function refund($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'RequestID' => 'RZP190219162906769',
                    'Responsecode' => '0',
                    'status' => 'refunded',
                    'received' => 'true',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'refund');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function verifyRefund($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        'DEALID' => 'CS905114097404',
                        'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                        'Key' => $entities['terminal']['gateway_secure_secret'],
                        'ORDERNO' => '104',
                        'REQUESTID' => $entities['gateway']['refund']['requestid'],
                        'RESPONSECODE' => '0'
                    ],
                    'received' => true,
                    'requeryid' => $entities['gateway']['refund']['requestid'],
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

        $this->content($response, 'verify_refund');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

}
