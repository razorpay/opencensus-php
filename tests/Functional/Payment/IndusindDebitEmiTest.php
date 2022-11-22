<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Razorpay\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;

class IndusindDebitEmiTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IndusindDebitEmiTestData.php';

        parent::setUp();

        $this->gateway = 'indusind_debit_emi';

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray();

        $this->ba->publicAuth();
    }

    public function testIndusindDebitEmiPaymentSuccess()
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
                    case 'action/pay':
                    case 'action/callback':
                        return [
                            'data' => []
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

    public function testIndusindDebitEmiPaymentIncorrectOtp()
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
                                'internal_error_code' => 'BAD_REQUEST_PAYMENT_OTP_INCORRECT',
                                'gateway_error_code' => '',
                                'gateway_error_description' => '',
                                'description' => 'Payment processing failed because of incorrect OTP',
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

    public function testIndusindDebitEmiPaymentOtpExceedLimit()
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
                                'internal_error_code' => 'BAD_REQUEST_PAYMENT_OTP_INCORRECT',
                                'gateway_error_code' => '',
                                'gateway_error_description' => '',
                                'description' => 'Payment processing failed because of incorrect OTP',
                            ],
                        ];
                }
            });
        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $url = $this->getOtpSubmitUrl($payment);
        $data = $this->testData['testIndusindDebitEmiPaymentIncorrectOtp'];
        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data); // first attempt
        $this->runRequestResponseFlow($data); // second attempt
        $this->runRequestResponseFlow($data); // third attempt
        $this->runRequestResponseFlow($data); // fourth attempt
        $this->runRequestResponseFlow($data); // fifth attempt

        $data = $this->testData[__FUNCTION__];
        $data['request']['url'] = $url;
        $this->runRequestResponseFlow($data); // sixth attempt
    }

    // ------------- Helpers -----------------
    protected function createDependentEntitiesForSuccessPayment($addEmiPlan = true)
    {
        if ($addEmiPlan === true)
        {
            $this->fixtures->emiPlan->create(
                [
                    'merchant_id' => '10000000000000',
                    'bank'        => 'INDB',
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
                'issuer'  => 'INDB',
                'network' => 'Visa',
            ]);

        $this->fixtures->create('terminal:indusind_debit_emi');
    }

    protected function assertCreateSuccess($payment)
    {
        $this->assertArraySelectiveEquals(
            [
                'status'  => 'created',
                'amount'  => 300000,
                'method'  => 'emi',
                'gateway' => 'indusind_debit_emi',

            ],
            $payment->toArray()
        );

        $card = $this->getDbLastEntityToArray('card');

        $this->assertArraySelectiveEquals(
            [
                'id'     => $payment['card_id'],
                'iin'    => '485446',
                'issuer' => 'INDB',
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
