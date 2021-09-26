<?php

namespace RZP\Tests\Functional\CustomerToken;

use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

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

        $this->assertArrayNotHasKey('customer_id', $response);

        $response2 = $this->startTest();

        $this->assertEquals($response['id'], $response2['id']);

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $payment['token_id']];

        $this->ba->privateAuth();

        $fetchResponse = $this->startTest($fetchPayload);

        $this->assertEquals('card', $fetchResponse['method']);

        $this->assertEquals('12', $fetchResponse['expiry_month']);

        $this->assertEquals('2024', $fetchResponse['expiry_year']);
    }

     public function testCreateTokenWithCustmerId()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $createPayload['request']['content']['customer_id'] = 'cust_100000customer';

        $response = $this->startTest($createPayload);

        $this->assertNotNull($response['customer_id']);

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('12', $response['expiry_month']);

        $this->assertEquals('2023', $response['expiry_year']);

        $this->assertNotNull($response['service_providers']);
    }


    public function testFetchToken()
    {
        $this->ba->privateAuth();
      
        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $fetchResponse = $this->startTest($fetchPayload);

        $this->assertEquals('card', $fetchResponse['method']);

        $this->assertEquals('12', $fetchResponse['expiry_month']);

        $this->assertEquals('2023', $fetchResponse['expiry_year']);

        $this->assertNotNull($fetchResponse['service_providers']);
    }

    public function testFetchCryptogram()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchCryptogram'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $response = $this->startTest($fetchPayload);

        $this->assertNotNull($response['provider']['data']['token_number']);

        $this->assertNotNull($response['provider']['data']['cryptogram_value']);

        $this->assertEquals('12', $response['provider']['data']['expiry_month']);

        $this->assertEquals('2023', $response['provider']['data']['expiry_year']);
    }

    public function testTokenDelete()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $deletePayload = $this->testData['testTokenDelete'];

        $deletePayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($deletePayload);

         $content = $this->makeRequestAndCatchException(function() use ($fetchPayload) {
            $this->makeRequestAndGetContent($fetchPayload['request']);
        },
            BadRequestValidationFailureException::class);
    }
}
