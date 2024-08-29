<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiYesBankQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

        $this->getDedicatedTerminalSplitzResponseForVariantON();
    }


    public function testCreateStaticQRWithoutAnyTerminal() :void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateStaticQrWithTerminal() :void
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateStaticQrWithTerminalForYesbank60(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateStaticQrWithAmount() :void
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'payment_amount' => '300',
                'type'  => 'upi_qr',
                'fixed_amount' => true,
            ],
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testPaymentForStaticQrCode(): void
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions();
    }

    public function testPaymentForStaticQrCodeForYesbank60(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity             = $this->getLastEntity('qr_code', true);
        $upiEntity['npci_txn_id'] = 'YES25356b6cf2664d5c9448912701c54c61';
        $this->makeUpiYesBankPayment60($qrCodeEntity, [], $upiEntity);
        $paymentRequestEntity['description'] = 'PaymenttoMitasha';
        $this->runQrPaymentEntityAssertions(true, $paymentRequestEntity);
        $upi = $this->getLastEntity('upi', true);
        $this->assertEquals($upi['npci_txn_id'], $upiEntity['npci_txn_id']);

    }

    public function testPaymentOnDynamicQrCode() :void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions();
    }

    public function testPaymentOnDynamicQrCodeForYesbank60(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity             = $this->getLastEntity('qr_code', true);
        $upiEntity['npci_txn_id'] = 'YES25356b6cf2664d5c9448912701c54c61';
        $this->makeUpiYesBankPayment60($qrCodeEntity, [], $upiEntity);
        $paymentRequestEntity['description'] = 'PaymenttoMitasha';
        $this->runQrPaymentEntityAssertions(true, $paymentRequestEntity);
        $upi = $this->getLastEntity('upi', true);
        $this->assertEquals($upi['npci_txn_id'], $upiEntity['npci_txn_id']);
    }


    public function testQrPaymentOnIntentSubType() :void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $upi_metadata     = $this->getLastEntity('upi_metadata', true);
        $this->assertEquals('intent', $upi_metadata['flow']);
        $qrPayment        = $this->getLastEntity('qr_payment', true);
        $payment          = $this->getLastEntity('payment', true);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true);
        $upi              = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);
        $this->assertEquals(null, $qrPaymentRequest['failure_reason']);
        $this->assertEquals(true, $qrPaymentRequest['expected']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $qrPayment['expected']);
    }

    public function testPaymentForClosedQrCode(): void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeId = $qrCodeEntity['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions();

        //Payment made before Closing Time so, we will accept this Callback
        $refund = $this->getDbLastEntity('refund');
        $this->assertNull($refund,'Refund Entity should be null');
    }

    public function testPaymentForClosedQrCodeForYesbank60(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeId = $qrCodeEntity['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $upiEntity['npci_txn_id'] = 'YES25356b6cf2664d5c9448912701c54c61';
        $this->makeUpiYesBankPayment60($qrCodeEntity, [], $upiEntity);
        $paymentRequestEntity['description'] = 'PaymenttoMitasha';
        $this->runQrPaymentEntityAssertions(true, $paymentRequestEntity);
        $upi = $this->getLastEntity('upi', true);
        $this->assertEquals($upi['npci_txn_id'], $upiEntity['npci_txn_id']);

        //Payment made before Closing Time so, we will accept this Callback
        $refund = $this->getDbLastEntity('refund');
        $this->assertNull($refund, 'Refund Entity should be null');
    }

    public function testPaymentForInvalidQrCode()
    {
        self::markTestSkipped();

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeEntity['id'] = 'qr_ABCEDF1234567890';
        $qrCodeEntity['reference'] = 'ABCEDF1234567890';

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
    }

    public function testPaymentWithDisabledUpiMethod(): void
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(UnexpectedPaymentReason::QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED);

        $this->fixtures->merchant->disableMethod('10000000000000', 'upi');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );
    }

    public function testPaymentForUnsuccessfulStatusCallback(): void
    {
        //Note: Callbacks with failed status are not processed but qr_payment_request entity is saved in DB

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $payment = [
            'amount'          => '300',
            'description'     => 'callback_failed_v2',
            'vpa'             => 'testvpa@yesb',
        ];

        $this->makeUpiYesBankPayment($qrCodeEntity, $payment);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true);

        $this->assertEquals('failed callback', $qrPaymentRequest['failure_reason']);
        $this->assertEquals(null, $qrPayment);
        $this->assertEquals(null, $payment);
    }

    public function testPaymentForUnsuccessfulStatusCallbackForYesbank60(): void
    {
        //Note: Callbacks with failed status are not processed but qr_payment_request entity is saved in DB

        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $payment = [
            'amount'      => '300',
            'description' => 'callback_failed_v2',
            'vpa'         => 'testvpa@yesb',
        ];

        $upiEntity['npci_txn_id'] = 'YES25356b6cf2664d5c9448912701c54c61';

        $this->makeUpiYesBankPayment60($qrCodeEntity, $payment, $upiEntity);

        $qrPayment        = $this->getDbLastEntity('qr_payment');
        $payment          = $this->getLastEntity('payment', true);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true);

        $this->assertEquals('failed callback', $qrPaymentRequest['failure_reason']);
        $this->assertEquals(null, $qrPayment);
        $this->assertEquals(null, $payment);
    }

    public function testMultiplePaymentsForStaticQR(): void
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions();
    }

    public function testCreateDynamicQrCode() :void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicQrCodeForYesbank60(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::ENABLE_YES_BANK_TERMINAL_FOR_6_0_STACK => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal_60');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicQrWithoutTerminal() :void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateDynamicQrWithCloseByMoreThanExpectedLimit(): void
    {
        //Maximum permissible limit of closeBy is 45 days or 64800 minutes

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('QR expiry time cannot be more than 64800 minutes from the current time');

        $days = 46;

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60),
            ],
        );
    }

    public function testCreateDynamicQrCodeFalseGatewayResponse() :void
    {
        $this->expectException(BadRequestException::class);

        $this->fixtures->create('terminal:live_dedicated_upi_yesbank_terminal');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');
    }

    //It tests Qr payment fetch flow done via internal flow for Yes bank qr codes whose payment is not received by razorpay
    public function testProcessYesBankQrReconInternalWithoutPayment(): void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $request = $this->testData['testProcessYesBankQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = 'RZPY' . $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessYesBankQrReconInternal(): void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesBankQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = 'RZPY' . $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    private function runQrCodeEntityAssertions(): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertStringContainsString('@yesb', $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['provider'] === 'upi_qr')
        {
            $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
            $this->assertEquals('RZPY' . $qrCodeEntity['reference'] . 'qrv2', $intentParam['tr']);
        }

        if($qrCodeEntity['usage'] === 'single_use')
        {
            $amount = $qrCodeEntity['amount'] / 100;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
    }

    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = []): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true);
        $payment          = $this->getLastEntity('payment', true);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true);
        $upi              = $this->getLastEntity('upi', true);
        $intentParam      = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals($upi['merchant_reference'], $intentParam['tr']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals('closed', $qrCodeEntity['status']);
        }
        else
        {
            $this->assertEquals('active', $qrCodeEntity['status']);
        }

        if ($expected === true)
        {
            $this->assertEquals(null, $qrPaymentRequest['failure_reason']);
            $this->assertEquals(true, $qrPaymentRequest['expected']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals(true, $qrPayment['expected']);
        }
        else
        {
            $this->assertEquals(false, $qrPaymentRequest['expected']);
            $this->assertEquals('refunded', $payment['status']);
            $this->assertEquals(false, $qrPayment['expected']);
        }
    }

    public function testCreateQrWithCloseOnDemandEnabled(): void
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON
            ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("Your current configuration does not support QR creation. Contact support for further assistance");

        $this->fixtures->merchant->addFeatures(['close_qr_on_demand']);

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name'  => 'Mitasha']
        );
    }

    public function testCreateBharatQrCodeWithDedicatedTerminal(): void
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $response = $this->createQrCode();

        $expectedResponse = $this->testData['testCreateBharatQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateBharatQrCodeWithNoDedicatedTerminal(): void
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );
    }

    public function testRemarksPassedDuringPaymentOnQrCode() :void
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $notes = "Sample notes passed during payment";

        $payment = [
            'amount'      => '300',
            'description' => $notes,
            'vpa'         => 'abcba@yesbank',
        ];

        $this->makeUpiYesBankPayment($qrCodeEntity, $payment);

        $this->runQrPaymentEntityAssertions(true, $payment);
    }

    public function testProcessYesbankQrPaymentInternalWithPayerAccountType()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesbankQrPaymentInternalWithPayerAccountType'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment    = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals('bank_account', $payment['reference2']);
    }

    public function testProcessYesbankQrPaymentInternalWithInvalidPayerAccountType()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesbankQrPaymentInternalWithInvalidPayerAccountType'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment    = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals(null, $payment['reference2']);
    }


    public function testProcessYesbankQrPaymentInternalWithNullPayerAccountType()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesbankQrPaymentInternalWithNullPayerAccountType'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment    = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals(null, $payment['reference2']);
    }

    public function testProcessYesbankQrPaymentInternalWithEmptyPayerAccountType()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesbankQrPaymentInternalWithEmptyPayerAccountType'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment    = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals(null, $payment['reference2']);
    }
}
