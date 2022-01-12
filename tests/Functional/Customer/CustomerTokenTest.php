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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['cardsaving']);
    }

    public function testGetTokenWithBankDetails()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetTokenMaxAmount()
    {
        //max amount should be present only in method = emandate
        $token = $this->getTokenById('token_100000emandate');
        self::assertArrayHasKey(Token\Entity::MAX_AMOUNT, $token);

        $token = $this->getTokenById('token_1000custwallet');
        self::assertArrayNotHasKey(Token\Entity::MAX_AMOUNT, $token);

        $token = $this->getTokenById('token_100001custcard');
        self::assertArrayNotHasKey(Token\Entity::MAX_AMOUNT, $token);

        $token = $this->getTokenById('token_100000custbank');
        self::assertArrayNotHasKey(Token\Entity::MAX_AMOUNT, $token);
    }

    public function testGetTokenExpiredAt()
    {
        //expired at should be present only when method is emandate and card
        $token = $this->getTokenById('token_100000emandate');
        self::assertArrayHasKey(Token\Entity::EXPIRED_AT, $token);

        $token = $this->getTokenById('token_100001custcard');
        self::assertArrayHasKey(Token\Entity::EXPIRED_AT, $token);

        //expired at should not exist in following
        $token = $this->getTokenById('token_1000custwallet');
        self::assertArrayNotHasKey(Token\Entity::EXPIRED_AT, $token);

        $token = $this->getTokenById('token_100000custbank');
        self::assertArrayNotHasKey(Token\Entity::EXPIRED_AT, $token);
    }

    public function testAddCustomerTokenCard()
    {
        $this->mockCardVault();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenWallet()
    {
        $this->markTestSkipped('To be implemented for Wallets');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenNetbanking()
    {
        $this->markTestSkipped('To be implemented for netbanking');

        $this->ba->privateAuth();

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

    public function testFetchSavedTokensStatusSavedSkipOTPSend()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
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
        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => true, 'card_id' => '100000001lcard']);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertTrue($token[Token\Entity::RECURRING]);
        $this->assertFalse($token[Token\Entity::COMPLIANT_WITH_TOKENISATION_GUIDELINES]);
        $this->assertArrayHasKey(Token\Entity::RECURRING_STATUS_SHORT, $token[Token\Entity::RECURRING_DETAILS]);
        $this->assertArrayHasKey(Token\Entity::RECURRING_FAILURE_REASON_SHORT,$token[Token\Entity::RECURRING_DETAILS]);

        $this->assertArrayHasKey(Token\Entity::RECURRING_DETAILS, $token);

    }

    public function testFetchTokenCardWithFlows()
    {
        $token = $this->fixtures->create('token', [
            'method'  => 'card',
            'card_id' => '100000001lcard',
            'bank'    => null,
            'wallet'  => null
        ]);

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $this->fixtures->edit('iin', 411111, ['flows' => $flows]);

        $token = $this->getTokenById('token_' . $token['id']);

        self::assertFalse($token[Token\Entity::RECURRING]);
        self::assertEquals(3, count($token['card']['flows']));

        // We never display the keys below to the public
        self::assertArrayNotHasKey(Token\Entity::RECURRING_STATUS, $token);
        self::assertArrayNotHasKey(Token\Entity::RECURRING_FAILURE_REASON, $token);

        self::assertArrayHasKey(Token\Entity::RECURRING_DETAILS, $token);
    }


    public function testFetchTokenCardRecurringWithStatus()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'card',
                'recurring' => true,
                'recurring_status' => 'pakka confirm',
                'card_id' => '100000001lcard'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertTrue($token[Token\Entity::RECURRING]);
        $this->assertFalse($token[Token\Entity::COMPLIANT_WITH_TOKENISATION_GUIDELINES]);
        $this->assertArrayHasKey(Token\Entity::RECURRING_STATUS_SHORT, $token[Token\Entity::RECURRING_DETAILS]);
        $this->assertArrayHasKey(Token\Entity::RECURRING_FAILURE_REASON_SHORT,$token[Token\Entity::RECURRING_DETAILS]);

        $this->assertArrayHasKey(Token\Entity::RECURRING_DETAILS, $token);
    }

    public function testFetchTokenCardNotRecurring()
    {
        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => false, 'card_id' => '100000001lcard']);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertFalse($token[Token\Entity::RECURRING]);

        $this->assertArrayHasKey(Token\Entity::RECURRING_STATUS_SHORT, $token[Token\Entity::RECURRING_DETAILS]);
        $this->assertArrayHasKey(Token\Entity::RECURRING_FAILURE_REASON_SHORT,$token[Token\Entity::RECURRING_DETAILS]);

        $this->assertArrayHasKey(Token\Entity::RECURRING_DETAILS, $token);
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
        $this->markTestSkipped('To be implemented for netbanking');

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

    public function testFetchTokenAuthType()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'emandate',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'netbanking'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertNotNull($token[Token\Entity::AUTH_TYPE]);
        $this->assertEquals('netbanking', $token[Token\Entity::AUTH_TYPE]);

        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'emandate',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'aadhaar'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertNotNull($token[Token\Entity::AUTH_TYPE]);
        $this->assertEquals('aadhaar', $token[Token\Entity::AUTH_TYPE]);
    }

    public function testFetchTokenMrn()
    {
        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'emandate',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'netbanking',
                'gateway_token' => 'test',
                'card_id' => '100000001lcard'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertNotNull($token[Token\Entity::AUTH_TYPE]);
        $this->assertNull($token[Token\Entity::MRN]);
        $this->assertEquals('netbanking', $token[Token\Entity::AUTH_TYPE]);

        $this->fixtures->merchant->addFeatures(['emandate_mrn']);

        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'emandate',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'aadhaar',
                'gateway_token' => 'test',
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertNotNull($token[Token\Entity::MRN]);
        $this->assertEquals('test', $token[Token\Entity::MRN]);

        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'nach',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'physical',
                'gateway_token' => 'test',
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertNotNull($token[Token\Entity::MRN]);
        $this->assertEquals('test', $token[Token\Entity::MRN]);

        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'card',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'auth_type' => 'otp',
                'gateway_token' => 'test',
                'card_id' => '100000001lcard'
            ]);

        $token = $this->getTokenById('token_' . $token['id']);

        $this->assertEquals(false, $token['compliant_with_tokenisation_guidelines']);

        $this->assertNull($token[Token\Entity::MRN]);
    }

    public function testPauseNotSupportedCardTokens()
    {
        $card = $this->fixtures->create('card', ['country' => 'IN']);

        $token = $this->fixtures->create(
            'token',
            [
                'method' => 'card',
                'recurring' => true,
                'recurring_status' => 'confirmed',
                'card_id' => $card->getId(),
            ]);

        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertEmpty($response['failed']);

        $this->assertEquals(1, sizeof($response['succeeded']));

        $this->assertEquals($token->getId(), $response['succeeded'][0]);
    }

    public function testAddCustomerTokenCardCardVault()
    {
        $this->mockCardVault();

        $this->ba->privateAuth();

        $this->startTest();
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
