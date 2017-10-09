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

        $this->assertEquals(true, array_key_exists('recurring', $token));
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

    public function testFetchTokenCardRecurring()
    {
        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => true]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertTrue($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        $this->assertArrayNotHasKey(Token\Entity::RECURRING_DETAILS, $token);
    }

    public function testFetchTokenCardRecurringWithStatus()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'card',
                'recurring' => true,
                'recurring_status' => 'pakka confirm'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertTrue($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        $this->assertArrayNotHasKey(Token\Entity::RECURRING_DETAILS, $token);
    }

    public function testFetchTokenCardNotRecurring()
    {
        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => false]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertFalse($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        $this->assertArrayNotHasKey(Token\Entity::RECURRING_DETAILS, $token);
    }

    public function testFetchTokenNbRecurringConfirmed()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'netbanking',
                'recurring' => true,
                'recurring_status' => 'confirmed'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertTrue($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        $this->assertEquals('confirmed', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS_SHORT]);
        $this->assertNull($token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_FAILURE_REASON_SHORT]);
    }

    public function testFetchTokenNbRecurringRejected()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'netbanking',
                'recurring' => false,
                'recurring_status' => 'rejected',
                'recurring_failure_reason' => 'you are rejected!',
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertFalse($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        $this->assertEquals('rejected', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS_SHORT]);
        $this->assertEquals('you are rejected!', $token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_FAILURE_REASON_SHORT]);
    }

    public function testFetchNbRecurringFalseRecurringStatusNullToken()
    {
        $token = $this->createCustomerToken(0);

        // Public mode
        $token = $this->getTokenById($token['id']);

        $this->assertFalse($token[Token\Entity::RECURRING]);

        // We never display the keys below to the public
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        $this->assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        // We don't append recurring status and recurring failure reason when they are null
        $this->assertNotNull($token[Token\Entity::RECURRING_DETAILS]);

        $this->assertNull($token[Token\Entity::RECURRING_DETAILS][Token\Entity::RECURRING_STATUS_SHORT]);
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
