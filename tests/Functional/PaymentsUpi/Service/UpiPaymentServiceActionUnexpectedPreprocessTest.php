<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiPaymentServiceActionUnexpectedPreprocessTest extends UpiPaymentServiceTest
{
    use NonVirtualAccountQrCodeTrait;
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * test payment type success
     */
    public function testPaymentTypeAPI()
    {
        $payment = $this->payment;

        $payment['description'] =  'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getDbLastPayment();

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                "payment" => [
                    "amount"                => $payment->getAmount(),
                ],
                "upi"  =>  [
                    "merchant_reference"    => $payment->getId(),
                    "npci_reference_id"     => "302450349580",
                ],
                "terminal" => [
                    "gateway"               => $payment->getGateway(),
                    "gateway_merchant_id"   => "AIRT231ed4o5fd",
                ],
                "gateway" => $payment->getGateway(),
                "source"  => 'art',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response);
        $this->assertEquals("API", $response["data"]["type"]);
    }

    /**
     * test payment type API Unexpected
     */
    public function testPaymentTypeAPIUnexpected()
    {
        $this->gateway = "upi_airtel";

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->fixtures->create('upi', [
            'payment_id'         => $payment->getId(),
            'gateway'            => $this->gateway,
            'amount'             => $payment->getAmount(),
            'merchant_reference' => 'AIR3521rfugj780s0n',
            'npci_reference_id'  => '309452271898',
        ]);

        $upiEntity = $this->getDbLastUpi();

        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                "payment" => [
                    "amount"                => $payment->getAmount(),
                ],
                "upi"  =>  [
                    "merchant_reference"    => 'AIR3521rfugj780s0n',
                    "npci_reference_id"     => "123456789012",
                ],
                "terminal" => [
                    "gateway"               => $payment->getGateway(),
                    "gateway_merchant_id"   => "AIRT231ed4o5fd",
                ],
                "gateway" => $payment->getGateway(),
                "source"  => 'art',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);
        $paymentEntity = $this->getDbLastPayment();
        $this->assertNotNull($paymentEntity->getRefundAt());
        $this->assertNotNull($response);
        $this->assertEquals("API_UNEXPECTED", $response["data"]["type"]);
        $this->assertNotNull($response["data"]["response"]);
    }

    /**
     * test payment type Unexpected
     */
    public function testPaymentTypeUnexpected()
    {
        $this->gateway = "upi_airtel";

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->fixtures->create('upi', [
            'payment_id'         => $payment->getId(),
            'gateway'            => $this->gateway,
            'amount'             => $payment->getAmount(),
            'merchant_reference' => 'AIR3521rfugj780s0n',
            'npci_reference_id'  => '309452271898',
        ]);

        $upiEntity = $this->getDbLastUpi();

        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                "payment" => [
                    "amount"                => $payment->getAmount(),
                ],
                "upi"  =>  [
                    "merchant_reference"    => 'AIR3521rfugj780s07',
                    "npci_reference_id"     => "123456789015",
                ],
                "terminal" => [
                    "gateway"               => $payment->getGateway(),
                    "gateway_merchant_id"   => "AIRT231ed4o5fd",
                ],
                "gateway" => $payment->getGateway(),
                "source" => 'art',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);
        $this->assertEquals("UNEXPECTED", $response["data"]["type"]);
    }

    /**
     * test payment type validation failure
     */
    public function testPaymentTypeValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] =  'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getDbLastPayment();

        $this->ba->appAuth();

        $content = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                "upi"  =>  [
                    "merchant_reference"    => 'AIR3521rfugj780s07',
                    "npci_reference_id"     => "123456789015",
                ],
                "terminal" => [
                    "gateway"               => $payment->getGateway(),
                    "gateway_merchant_id"   => "AIRT231ed4o5fd",
                ],
                "gateway" => $payment->getGateway(),
                "source" => 'art',
            ],
        ];

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The payment field is required.');
    }

    public function testPaymentTypeQrForOldGateway()
    {
        $this->qrRelatedSetup();

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccounTMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                'payment' => [
                    'amount'                => 100,
                    'cuurency' => 'INR',
                ],
                'upi'  =>  [
                    "merchant_reference"    => 'RZPY' . $qrCode->getId() . 'qrv2',
                    "npci_reference_id"     => '302450349580',
                ],
                'terminal' => [
                    'gateway'               => 'upi_yesbank',
                    "gateway_merchant_id"   => 'razorpayupi',
                ],
                "gateway" => 'upi_yesbank',
                "source" => 'art',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response);
        $this->assertEquals('QR', $response['data']['type']);
    }

    public function testPaymentTypeQrForNewGateway()
    {
        $this->qrRelatedSetup();

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on'
            ]
        );

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'qr_string' => 'RandomQrString',
                                                   'reference' => 'RZPY'. $reqArray['qr_code']['id'] . 'qrv2',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccounTMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/internal/paymentsupi/unexpected_preprocess',
            'content' => [
                'payment' => [
                    'amount'                => 100,
                    'cuurency' => 'INR',
                ],
                'upi'  =>  [
                    "merchant_reference"    => 'RZPY' . $qrCode->getId() . 'qrv2',
                    "npci_reference_id"     => '302450349580',
                ],
                'terminal' => [
                    'gateway'               => 'upi_yesbank',
                    "gateway_merchant_id"   => 'razorpayupi',
                ],
                "gateway" => 'upi_yesbank',
                "source" => 'art',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response);
        $this->assertEquals('QR', $response['data']['type']);
    }

    protected function qrRelatedSetup()
    {
        $this->fixtures->merchant->createAccount('LiveAccounTMer');
        $this->fixtures->on('live')->merchant->edit('LiveAccounTMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccounTMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccounTMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccounTMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id' => 'LiveAccounTMer']);

        // Although we are creating mock upi_yesbank and upi_icici terminal, we expect to use the new gateway module for QR create
        $this->fixtures->create(
            'terminal:dedicated_upi_yesbank_terminal',
            [
                'id'          => '101YesDedTrmnl',
                'merchant_id' => 'LiveAccounTMer',
                'vpa' => 'randomvpa@yesbank',
                'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
            ]
        );

        //WARN: Remember to mock Mozart in each test, or else we shall start making network calls!!
        $this->config['gateway.mock_upi_mozart'] = true;
    }
}
