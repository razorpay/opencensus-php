<?php


namespace RZP\Tests\Functional\Contacts;


use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class ContactTypeTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ContactTypeTestData.php';

        parent::setUp();
    }

    public function testCreateContactType()
    {
        // Test with Private Auth
        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['type'] = 'Contact Type 1';

        $response = $this->startTest($data);

        $this->assertEquals(in_array(['type' => 'Contact Type 1'], $response['items'], true), true);

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $data['request']['content']['type'] = 'Contact Type 2';

        $response = $this->startTest($data);

        $this->assertEquals(in_array(['type' => 'Contact Type 2'], $response['items'], true), true);
    }

    public function testCreateCustomContactNumericType()
    {
        // Test with Private Auth
        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['type'] = 'Contact Type 1';

        $response = $this->startTest($data);

        $this->assertEquals(in_array(['type' => 'Contact Type 1'], $response['items'], true), true);

        $data['request']['content']['type'] = 1234;

        $response = $this->startTest($data);

        $this->assertEquals(in_array(['type' => 1234], $response['items'], true), true);

        $data['request']['content']['type'] = 'Contact Type 2';

        $response = $this->startTest($data);

        $this->assertEquals(in_array(['type' => 'Contact Type 2'], $response['items'], true), true);
    }

    public function testGetContactType()
    {
        // Test with Private Auth
        $this->ba->privateAuth();

        $this->startTest();

        // Test with Proxy Auth
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetContactTypeInternal()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    public function testGetCustomContactTypeInternal()
    {
        $merchantID = "10000000000000";

        $contactType = "Contact Type 1";

        //Create custom contact type
        $request = [
            'method'  => 'POST',
            'url'     => '/contacts/types',
            'content' => [
                'type'        => $contactType
            ]
        ];

        $this->ba->privateAuth();

        $this->sendRequest($request);

        //Test custom contact type
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchantID;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->payoutLinksAppAuth();

        $response = $this->startTest();

        $this->assertEquals(in_array(['type' => $contactType], $response['items'], true), true);
    }
}
