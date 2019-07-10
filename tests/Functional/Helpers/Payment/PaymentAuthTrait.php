<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Mockery;

trait PaymentAuthTrait
{
    protected function mockOtpElf($callback = null)
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $defaultCallback = function (array $input)
        {
            $payment = $this->getEntityById('payment', $input['payment_id'], true);

            $req = [
                'Message' => [
                    'PAReq' => [
                        'Merchant' => [
                            'acqBIN' => '11111111111',
                            'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                        ],
                        'CH' => [
                            'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                        ],
                        'Purchase' => [
                            'xid'    => base64_encode(str_pad($input['payment_id'], 20, '0', STR_PAD_LEFT)),
                            'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                            'amount' => '500.00',
                            'purchAmount' => '50000',
                            'currency' => '356',
                            'exponent' => 2,
                        ]
                    ]
                ],
            ];

            $content['Message']['@attributes']['id'] = $payment['public_id'];
            $content['Message']['PARes'] = (new \RZP\Gateway\Mpi\Blade\Mock\Response\Pareq('route'))->enrolledValidResponse($req);
            $content['Message']['Signature'] = (new \RZP\Gateway\Mpi\Blade\Mock\Server('route'))->getSignature();


            $xml = base64_encode(gzcompress(\Lib\Formatters\Xml::create('ThreeDSecure', $content)));

            return [
                'success' => true,
                'data' => [
                    'action' => 'submit_otp',
                    'data'   => [
                        'PaRes' => $xml,
                        'MD' => $input['payment_id']
                    ]
                ]
            ];
        };

        $otpelf->shouldReceive('otpSubmit')
               ->with(\Mockery::type('array'))
               ->andReturnUsing($callback ?: $defaultCallback);

        $this->app->instance('card.otpelf', $otpelf);

        return $otpelf;
    }

    protected function mockOtpElfForRupay()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                $gatewayEntity = $this->getLastEntity('hdfc', true);

                $payment = $this->getEntityById('payment', $input['payment_id'], true);

                $data = [];

                $data['paymentid'] = $gatewayEntity['gateway_payment_id'];
                $data['trackid'] = $input['payment_id'];
                $data['tranid'] = '201833401251688';
                $data['auth'] = '235823';
                $data['ref'] = '833423964074';
                $data['amt'] = $payment['amount'] / 100;
                $data['result'] = 'CAPTURED';
                $data['udf1'] = 'test';
                $data['udf2'] = 'test@razorpay.com';
                $data['udf3'] = ' 917226086092';
                $data['udf4'] = 'test';
                $data['udf5'] = 'test';
                $data['postdate'] = '1130';
                $data['avr'] = 'N';
                $data['authRespCode'] = '00';
                $data['AccuResponseCode'] = 'ACCU000';

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'submit_otp',
                        'data'   => $data
                    ]
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);
    }

    protected function mockSubmitOtpElf()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                $payment = $this->getEntityById('payment', $input['payment_id'], true);

                $req = [
                    'Message' => [
                        'PAReq' => [
                            'Merchant' => [
                                'acqBIN' => '11111111111',
                                'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                            ],
                            'CH' => [
                                'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                            ],
                            'Purchase' => [
                                'xid'    => base64_encode(str_pad($input['payment_id'], 20, '0', STR_PAD_LEFT)),
                                'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                'amount' => '500.00',
                                'purchAmount' => '50000',
                                'currency' => '356',
                                'exponent' => 2,
                            ]
                        ]
                    ],
                ];

                $content['Message']['@attributes']['id'] = $payment['public_id'];
                $content['Message']['PARes'] = (new \RZP\Gateway\Mpi\Blade\Mock\Response\Pareq('route'))->enrolledValidResponse($req);
                $content['Message']['Signature'] = (new \RZP\Gateway\Mpi\Blade\Mock\Server('route'))->getSignature();


                $xml = base64_encode(gzcompress(\Lib\Formatters\Xml::create('ThreeDSecure', $content)));

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'submit_otp',
                        'data'   => [
                            'PaRes' => $xml,
                            'MD' => $input['payment_id']
                        ]
                    ]
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);
    }
}
