<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Error\ErrorCode;

class KotakDebitEmiTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/KotakDebitEmiTestData.php';

        parent::setUp();

        $this->gateway = 'kotak_debit_emi';

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray();

        $this->ba->publicAuth();
    }

    public function testKotakDebitEmiPaymentSuccess()
    {
        $this->createDependentEntitiesForSuccessPayment();
        $this->enableCpsConfig();
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();
        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', \Mockery::type('string'), \Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $content) {
                switch ($url) {
                    case 'action/authorize':
                        $input = $content['input'];
                        return [
                            'data' => [
                                'url' => $input['otpSubmitUrl'],
                            ]
                        ];
                    case 'action/callback':
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test12',
                                ],
                                'payment' => [
                                    'reference2' => 'test12',
                                ],
                            ]
                        ];
                }
            });
        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $this->assertAuthorized();
    }

    public function testKotakDebitEmiPaymentIncorrectOtp()
    {
        $this->createDependentEntitiesForSuccessPayment();
        $this->enableCpsConfig();
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();
        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', \Mockery::type('string'), \Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $content) {
                switch ($url) {
                    case 'action/authorize':
                        $input = $content['input'];
                        return [
                            'data' => [
                                'url' => $input['otpSubmitUrl'],
                            ]
                        ];
                    case 'action/callback':
                        return [
                            'error' => [
                                'internal_error_code' => 'BAD_REQUEST_PAYMENTS_INVALID_OTP_TRY_NEW',
                                'gateway_error_code' => '',
                                'gateway_error_description' => '',
                                'description' => 'Entered OTP was incorrect. Please try again with the new OTP',
                            ],
                        ];
                }
            });
        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $this->assertOtpAttempts();
    }

    public function testKotakDebitEmiPaymentOtpExceedLimit()
    {
        $this->createDependentEntitiesForSuccessPayment();
        $this->enableCpsConfig();
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();
        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', \Mockery::type('string'), \Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $content) {
                switch ($url) {
                    case 'action/authorize':
                        $input = $content['input'];
                        return [
                            'data' => [
                                'url' => $input['otpSubmitUrl'],
                            ]
                        ];
                    case 'action/callback':
                        return [
                            'error' => [
                                'internal_error_code' => 'BAD_REQUEST_PAYMENTS_INVALID_OTP_TRY_NEW',
                                'gateway_error_code' => '',
                                'gateway_error_description' => '',
                                'description' => 'Entered OTP was incorrect. Please try again with the new OTP',
                            ],
                        ];
                }
            });
        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $url = $this->getOtpSubmitUrl($payment);
        $data = $this->testData['testKotakDebitEmiPaymentIncorrectOtp'];
        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data); // first attempt
        $this->runRequestResponseFlow($data); // second attempt
        $this->runRequestResponseFlow($data); // third attempt

        $data = $this->testData[__FUNCTION__];
        $data['request']['url'] = $url;
        $this->runRequestResponseFlow($data); // fourth attempt
    }

    // ------------- Helpers -----------------
    protected function createDependentEntitiesForSuccessPayment($addEmiPlan = true)
    {
        if ($addEmiPlan === true)
        {
            $this->fixtures->emiPlan->create(
                [
                    'merchant_id' => '10000000000000',
                    'bank'        => 'KKBK',
                    'type'        => 'debit',
                    'rate'        => 1200,
                    'min_amount'  => 300000,
                    'duration'    => 3,
                ]);
        }

        $this->fixtures->iin->create(
            [
                'iin'     => '485446',
                'network' => 'Visa',
                'type'    => 'debit',
                'issuer'  => 'KKBK',
                'network' => 'Visa',
            ]);

        $this->fixtures->create('terminal:kotak_debit_emi');
    }

    protected function assertCreateSuccess($payment)
    {
        $this->assertArraySelectiveEquals(
            [
                'status'  => 'created',
                'amount'  => 300000,
                'method'  => 'emi',
                'gateway' => 'kotak_debit_emi',

            ],
            $payment->toArray()
        );

        $card = $this->getDbLastEntityToArray('card');

        $this->assertArraySelectiveEquals(
            [
                'id'     => $payment['card_id'],
                'iin'    => '485446',
                'issuer' => 'KKBK',
            ],
            $card
        );

        $mozart = $this->getDbLastEntityToArray('mozart');
        $mozart = json_decode($mozart['raw'], true);

        $this->assertArraySelectiveEquals(
            [
                'Token'                      => '123456',
                'status'                     => 'OTP_sent',
                'AuthenticationErrorCode'    => '0000',
                'AuthenticationErrorMessage' => '',
                'BankReferenceNo'             => 'abc123456',
                'EligibilityStatus'          => 'Yes',
                'MerchantReferenceNo'        => $payment['id'],
            ],
            $mozart
        );
    }

    protected function assertOtpAttempts()
    {
        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'created',
                'otp_attempts' => 1,
            ],
            $payment
        );
    }

    protected function assertAuthorized()
    {
        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status' => 'authorized',
            ],
            $payment
        );
    }

    protected function getDefaultEmiPaymentArray()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4854460100840607';
        $payment['amount']         = 300000;
        $payment['method']         = 'emi';
        $payment['emi_duration']   = 3;

        return $payment;
    }
}
