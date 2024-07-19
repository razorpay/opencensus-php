<?php

namespace Functional\QrCode;

use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class QrCodeRefactorTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use NonVirtualAccountQrCodeTrait;

    protected function setUp(): void
    {
        //        $this->testDataFilePath = __DIR__ . '/QrCodeRefactorTestData.php';

        parent::setUp();

        $this->fixtures->merchant->createAccount('LiveAccountMer');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id' => 'LiveAccountMer']);

        // Although we are creating mock upi_yesbank and upi_icici terminal, we expect to use the new gateway module for QR create
        $this->fixtures->create(
            'terminal:dedicated_upi_yesbank_terminal',
            [
                'id'          => '10LiveAccTrmnl',
                'merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'id'          => '11LiveAccTrmnl',
                'merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->getDedicatedTerminalSplitzResponseForVariantON();

        // Mock razorx to return 'on' for qr code create refactor experiment
        $this->setMockRazorxTreatment([RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY => 'on']);

        //WARN: Remember to mock Mozart in each test, or else we shall start making network calls!!
        $this->config['applications.mozart.mock'] = false;
    }

    public function mockMozartResponse(&$count = 0, $res = null)
    {
        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use ($res, &$count) {
                    ++$count;

                    if (is_null($res) === false)
                    {
                        return json_encode($res);
                    }

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'qr_string' => 'RandomQrString',
                                               ],
                                           ],
                                       ]);
                }
            );
    }

    public function testCreateQrCodeViaRefactorFlow()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // This asserts that we are actually using the response from Mozart
        $this->assertEquals('RandomQrString', $qrCode->getQrString());

        // This asserts that calls are going to the mock Mozart layer properly
        $this->assertEquals(1, $count);
    }

    public function testCreateQrCodeViaRefactorFlowWithAllGatewaysDown()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    return json_encode([
                                           'success' => false,
                                           'error'   => [
                                               'gateway_error_code'        => 'RandomErrorCode',
                                               'gateway_error_description' => 'Gateway faced a random error',
                                           ],
                                           'data'    => [],
                                       ]);
                }
            );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode('SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR');
        $this->expectExceptionMessage('We are facing some trouble completing your request at the moment. Please try again shortly.');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        // No more assertions shall work as expectException shall catch the exception and finish asserting the
        // exception object and halt the test.
    }

    public function testCreateQrCodeViaRefactorFlowWithOneGatewayDown()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    if ($count++ === 0)
                    {
                        return json_encode([
                                               'success' => false,
                                               'error'   => [
                                                   'gateway_error_code'        => 'RandomErrorCode',
                                                   'gateway_error_description' => 'Gateway faced a random error',
                                               ],
                                               'data'    => [],
                                           ]);
                    }

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'qr_string' => 'RandomQrString2',
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
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // This asserts that we are actually using the response from Mozart
        $this->assertEquals('RandomQrString2', $qrCode->getQrString());

        // This asserts that calls are going to the mock Mozart layer properly
        $this->assertEquals(2, $count);
    }
}