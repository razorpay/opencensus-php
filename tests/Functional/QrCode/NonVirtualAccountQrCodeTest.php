<?php

namespace Functional\QrCode;

use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class NonVirtualAccountQrCodeTest extends TestCase
{
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateBharatQrCode()
    {
        $response = $this->createQrCode();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateUpiQrCode()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }
    
    public function testCloseQrCode()
    {
        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    private function runEntityAssertions($response)
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertNotNull($qrCodeEntity['short_url']);
        $tr = 'RZP'.substr($response['id'], 3, 14);
        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
    }
}
