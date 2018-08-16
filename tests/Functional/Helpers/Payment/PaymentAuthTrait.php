<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Mockery;

trait PaymentAuthTrait
{
    protected function mockOtpElf()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSubmit')
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
