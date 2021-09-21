<?php

namespace RZP\Tests\Functional\CustomerToken;

use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TokenTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization']);
    }

    public function testCreateToken()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('12', $response['expiry_month']);

        $this->assertEquals('2023', $response['expiry_year']);

        $this->assertNotNull($response['service_providers']);

        $response2 = $this->startTest();

        $this->assertEquals($response['id'], $response2['id']);
    }

    public function testFetchToken()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchCryptogram()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testTokenDelete()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }
}
