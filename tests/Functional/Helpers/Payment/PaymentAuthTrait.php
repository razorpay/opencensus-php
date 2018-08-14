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
                                'amount' => '500.00',
                                'xid'    => base64_encode(str_pad($input['payment_id'], 20, '0', STR_PAD_LEFT)),
                                'purchAmount' => '50000',
                                'currency' => '356',
                                'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                'exponent' => 2,
                            ]
                        ]
                    ]
                ];

                $content['Message']['@attributes']['id'] = $payment['public_id'];
                $content['Message']['PARes'] = (new \RZP\Gateway\Mpi\Blade\Mock\Response\Pareq('route'))->enrolledValidResponse($req);

                $xml = base64_encode(\Lib\Formatters\Xml::create('ThreeDSecure', $content));

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
