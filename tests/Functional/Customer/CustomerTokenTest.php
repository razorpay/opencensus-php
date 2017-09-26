<?php

namespace RZP\Tests\Functional\CustomerToken;

use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class CustomerTokenTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['tokens', 'cardsaving']);
    }

    public function testAddCustomerTokenCard()
    {
        $this->fixtures->create('card', ['id' => '10000savedcard']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenWallet()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenNetbanking()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetCustomerTokens()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerToken()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testUpdateCustomerToken()
    {
        $this->fixtures->edit('token', '1000custwallet', ['recurring' => 1]);

        $this->ba->privateAuth();

        $this->startTest();

        $token = $this->getEntityById('token', 'token_1000custwallet', true);

        $this->assertEquals(false, array_key_exists('recurring', $token));
    }

    public function testDeleteCustomerToken()
    {
        $this->mockSession();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDeleteCustomerTokenById()
    {
        $this->mockSession();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerTokensByAppToken()
    {
        $this->mockSession();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusSaved()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }

    public function testFetchSavedCustomerStatusWithDeviceToken()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusNotSaved()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }

    public function testDeleteAppToken()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromApp()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromDevice()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromAllDevices()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchNbRecurringFalseRecurringStatusNullToken()
    {
        $token = $this->createCustomerToken(0);

        // Public mode
        $token = $this->getTokenById($token['id']);

        // Recurring is false, and recurring status is null, and therefore recurring is not shown to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING, $token);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        // We don't append recurring status and recurring failure reason when they are null
        $this->assertNull($token[Token\Entity::RECURRING_DETAILS]);
    }

    public function testFetchNbRecurringTrueRecurringStatusNullToken()
    {
        $token = $this->createCustomerToken(1);

        // Public mode
        $token = $this->getTokenById($token['id']);

        $this->assertEquals(true, $token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        // We append null values to the recurring details array because recurring = true
        $this->assertNull($token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS]);
        $this->assertNull($token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_FAILURE_REASON]);
    }

    public function testFetchNbRecurringFalseRecurringStatusRejected()
    {
        $token = $this->createCustomerToken(0);

        $this->fixtures->edit('token', $token['id'], ['recurring_status' => 'rejected', 'recurring_failure_reason' => 'Registration failed']);

        $token = $this->getTokenById($token['id']);

        $this->assertEquals(false, $token[Token\Entity::RECURRING]);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        // recurring status and recurring failure reason are appended to the recurring details array
        $this->assertEquals('rejected', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS]);
        $this->assertEquals('Registration failed', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_FAILURE_REASON]);
    }

    public function testFetchNbRecurringTrueRecurringStatusConfirmed()
    {
        $token = $this->createCustomerToken(1);

        $this->fixtures->edit('token', $token['id'], ['recurring_status' => 'confirmed']);

        $token = $this->getTokenById($token['id']);

        $this->assertEquals(true, $token[Token\Entity::RECURRING]);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        // recurring status and recurring failure reason are appended to the recurring details array
        $this->assertEquals('confirmed', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS]);
        $this->assertEquals(null, $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_FAILURE_REASON]);
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token'   => 'capp_1000000custapp',
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }
}
