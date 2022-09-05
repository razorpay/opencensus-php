<?php

namespace RZP\Tests\Functional\Payment;

use Mockery;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardInternalPricingTest extends TestCase
{
    use PaymentTrait;

    protected $paymentData = null;
    protected $card = null;
    protected $pgService = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CardInternalPricingTestData.php';

        parent::setUp();

        $this->pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();
        $this->app->instance('pg_router', $this->pgService);
        $this->enablePgRouterConfig();

        $this->card = $this->fixtures->create('card', [
            'merchant_id' =>'10000000000000',
            'name' =>'Harshil',
            'expiry_month' =>12,
            'expiry_year' =>2024,
            'iin' =>'401200',
            'last4' =>'3335',
            'length' =>'16',
            'network' =>'Visa',
            'type' =>'credit',
            'sub_type' =>'consumer',
            'category' =>'STANDARD',
            'issuer' =>'HDFC',
            'international' =>FALSE,
            'emi' =>TRUE,
            'vault' =>'rzpvault',
            'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
            'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
            'trivia' => NULL,
            'country' =>'IN',
            'global_card_id' => NULL,
            'created_at' =>1614256967,
            'updated_at' =>1614256967,
        ]);

    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefaultValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }

    public function testPricingFeeForRearchPayment()
    {

        $card = $this->card;
        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' =>'GfnS1Fj048VHo2',
                        'merchant_id' =>'10000000000000',
                        'amount' =>50000,
                        'fees' => 0,
                        'currency' =>'INR',
                        'base_amount' =>50000,
                        'method' =>'card',
                        'status' =>'created',
                        'two_factor_auth' =>'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' =>FALSE,
                        'amount_authorized' =>50000,
                        'amount_refunded' =>0,
                        'base_amount_refunded' =>0,
                        'amount_transferred' =>0,
                        'amount_paidout' =>0,
                        'refund_status' => NULL,
                        'description' =>'description',
                        'card_id' =>$this->card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' =>FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' =>'a@b.com',
                        'contact' =>'+919918899029',
                        'notes' =>[
                            'merchant_order_id' =>'id',
                        ],
                        'authorized_at' =>1614253879,
                        'auto_captured' =>FALSE,
                        'captured_at' =>1614253880,
                        'gateway' =>'hdfc',
                        'terminal_id' =>'1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' =>5,
                        'signed' =>FALSE,
                        'verified' => NULL,
                        'gateway_captured' =>TRUE,
                        'verify_bucket' =>0,
                        'verify_at' =>1614253880,
                        'callback_url' => NULL,
                        'fee' =>0,
                        'mdr' =>0,
                        'tax' =>0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' =>FALSE,
                        'save' =>FALSE,
                        'late_authorized' =>FALSE,
                        'convert_currency' => NULL,
                        'disputed' =>FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' =>'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' =>1614253879,
                        'updated_at' =>1614253880,
                        'captured' =>TRUE,
                        'reference2' => '12343123',
                        'entity' =>'payment',
                        'fee_bearer' =>'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' =>FALSE,
                        'gateway_amount' =>50000,
                        'gateway_currency' =>'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' =>FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $this->pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $paymentData)
            {
                if ($method === 'GET')
                {
                    return $paymentData;
                }
                if ($method === 'POST')
                {
                    return [];
                }
            });


        $this->pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $paymentData)
            {
                if ($method === 'GET')
                {
                    return  $paymentData;
                }

                if ($method === 'POST')
                {
                    return [];
                }
            });

        $this->testData[__FUNCTION__]['request']['url'] = '/internal/payments/GfnS1Fj048VHo2/pricing';
        $this->ba->cardPaymentsInternalAppAuth();

        $resp = $this->startTest();

        self::assertNotNull($resp);
    }


    public function testPricingFeeCustomerFeeBearerRearchPayment(){

        $card = $this->card;
        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' =>'GfnS1Fj048VHo2',
                        'merchant_id' =>'10000000000000',
                        'amount' =>51000,
                        'fees' => 1000,
                        'currency' =>'INR',
                        'base_amount' =>51000,
                        'method' =>'card',
                        'status' =>'created',
                        'two_factor_auth' =>'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' =>FALSE,
                        'amount_authorized' =>51000,
                        'amount_refunded' =>0,
                        'base_amount_refunded' =>0,
                        'amount_transferred' =>0,
                        'amount_paidout' =>0,
                        'refund_status' => NULL,
                        'description' =>'description',
                        'card_id' =>$this->card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' =>FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' =>'a@b.com',
                        'contact' =>'+919918899029',
                        'notes' =>[
                            'merchant_order_id' =>'id',
                        ],
                        'authorized_at' =>1614253879,
                        'auto_captured' =>FALSE,
                        'captured_at' =>1614253880,
                        'gateway' =>'hdfc',
                        'terminal_id' =>'1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' =>5,
                        'signed' =>FALSE,
                        'verified' => NULL,
                        'gateway_captured' =>TRUE,
                        'verify_bucket' =>0,
                        'verify_at' =>1614253880,
                        'callback_url' => NULL,
                        'fee' =>1000,
                        'mdr' =>1000,
                        'tax' =>0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' =>FALSE,
                        'save' =>FALSE,
                        'late_authorized' =>FALSE,
                        'convert_currency' => NULL,
                        'disputed' =>FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' =>'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' =>1614253879,
                        'updated_at' =>1614253880,
                        'captured' =>TRUE,
                        'reference2' => '12343123',
                        'entity' =>'payment',
                        'fee_bearer' =>'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' =>FALSE,
                        'gateway_amount' =>51000,
                        'gateway_currency' =>'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' =>FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $this->pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $paymentData)
            {
                if ($method === 'GET')
                {
                    return $paymentData;
                }
                if ($method === 'POST')
                {
                    return [];
                }
            });


        $this->pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $paymentData)
            {
                if ($method === 'GET')
                {
                    return  $paymentData;
                }

                if ($method === 'POST')
                {
                    return [];
                }
            });

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->testData[__FUNCTION__]['request']['url'] = '/internal/payments/GfnS1Fj048VHo2/pricing';
        $this->ba->cardPaymentsInternalAppAuth();

        $resp = $this->startTest();

        self::assertNotNull($resp);
    }


}
