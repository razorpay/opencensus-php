<?php

namespace RZP\Tests\Functional\CustomerToken;

use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Trace\TraceCode;

class CustomerTokenTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;
    use DbEntityFetchTrait;

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

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

    public function testFetchSavedTokensStatusSaved()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'visa']);

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }


    public function testFetchTokenByCustomerIdWhenStatusIsActive()
    {
        $this->mode = Mode::LIVE;

        $this->mockSession();

        $this->ba->privateAuth();

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '1000ggcustomer', ['vault' => 'visa']);

        $response = $this->startTest();
        $this->assertEquals($response['status'], 'active');
        $this->assertEquals($response['error_code'], null);
        $this->assertEquals($response['error_description'], null);
    }



    public function testFetchTokenByCustomerIdWhenStatusIsFailed()
    {
        $this->app['basicauth']->setMode(Mode::LIVE);

        $this->mockSession();

        $this->ba->privateAuth();

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '1000ggcustomer', ['vault' => 'rzpvault' , 'status' => 'failed' , 'error_description' => 'The card is not eligible for tokenisation.', 'internal_error_code' => 'BAD_REQUEST_CARD_NOT_ELIGIBLE_FOR_TOKENISATION']);

        $response = $this->startTest();
        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals($response['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($response['error_description'], 'The card is not eligible for tokenisation.');
    }

    public function testFetchTokenByCustomerIdWhenStatusIsEmpty()
    {
        $this->app['basicauth']->setMode(Mode::LIVE);

        $this->mockSession();

        $this->ba->privateAuth();

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '1000ggcustomer', ['vault' => 'rzpvault', 'status'=> null]);

        $response = $this->startTest();
        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals($response['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($response['error_description'], 'Token creation failed');
    }



    public function testFetchSavedTokensStatusWhenNoCustomerTokensArePresentOnMerchantExpectsOtpGettingSkipped()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusWhenCustomerTokensArePresentOnDifferentMerchantExpectsOtpGettingSkipped()
    {
        $this->mockSession();

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000merchant1', '10000gcustomer', ['vault' => 'visa']);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testCustomerStatusApiWhenSavedCardTokensNotPresentExpectsOtpGettingSkipped()
    {
        $this->mockSession();

        $this->fixtures->create('token', [
            'method'      => 'wallet',
            'bank'        => null,
            'card_id'     => null,
            'customer_id' => '10000gcustomer',
            'merchant_id' => '100000Razorpay',
            'used_at'     => 1673359666,
        ]);

        $this->fixtures->create('token', [
            'customer_id'   => '10000gcustomer',
            'merchant_id'   => '100000Razorpay',
            'method'        => 'upi',
            'bank'          => null,
            'wallet'        => null,
            'vpa_id'        => '1000000000gupi',
            'used_at'       => 1673359666,
        ]);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusWhenInvalidCustomerTokensArePresentExpectsOtpGettingSkipped()
    {
        $this->mockSession();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '100000Razorpay', '10000gcustomer', ['vault' => 'visa']);

        $this->fixturesToCreateToken('100022xytoken2', '100000003card2', '411140', '10000000000000', '10000gcustomer', ['vault' => 'rzpvault', 'token' => '1001lcardtoken']);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusWhenCustomerDoesNotExistsExpectsOtpGettingSkipped()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusSavedSkipOTPSend()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'visa']);

        $this->startTest();
    }

    public function testFetchSavedCustomerStatusWithDeviceToken()
    {
        $this->mockSession();

        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'visa']);

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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
        $this->markTestSkipped('This test case is not applicable as we are not supporting rzp vault tokens');

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

    public function testAddCustomerTokenCardCardVault()
    {
        $this->mockCardVault();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2RazorPaySharedAccountDisplayNameSuccessful()
    {
        $this->markTestSkipped('This test case is not applicable as we are not supporting global tokens as per RBI compliance');

        $this->mockSession();

        $this->mockSharedAccountMerchant('test merchant','https://www.abcd.xyz.com', '/logos/random_image.png');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Razorpay Software Pvt Ltd',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2BillingLabelAsDisplayNameSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', null, '/logos/random_image.png', 'Billing Name');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Billing Name',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2BusinessNameAsDisplayNameSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', null, '/logos/random_image.png', null, 'Business Name');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Business Name',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2MerchantNameAsDisplayNameSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2EmailAsWebsiteBillingLabelAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'http://abcd@xyz.com', '/logos/random_image.png', 'Billing Name');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Billing Name',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2UpiIdAsWebsiteBillingLabelAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant','http://1234567890@okaxis', '/logos/random_image.png', 'Billing Name');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Billing Name',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2IpAddressAsWebsiteBillingLabelAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'http://127.0.0.1:5000/', null, 'Billing Name');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'Billing Name',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2ScemeNotHttpOrHttpsMerchantNameAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'www.testing.com');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2NumericDomainMerchantNameAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'http://2001');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2DomainFromExcludedDomainArrayMerchantNameAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.google.com');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2PlayStoreLinkAsWebsiteMerchantNameAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://play.google.com/store/apps/details?id=com.apexlearningapp.EducationalApp');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2AppleStoreLinkAsWebsiteMerchantNameAsDisplayName()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://apps.apple.com/us/app/la-milano-pizzeria/id1568854744');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $tokenMerchantId = $response['cards'][0]['tokens'][0]['merchant_id'];

        $merchantDetails = [
            'website_name'  => 'test merchant',
            'name'          => 'test merchant',
            'logo_url'      => null
        ];

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId]);
    }

    public function testFetchAppTokensV2MultipleCardsDifferentMerchantsSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $cardId = $this->createCardFixture();

        $merchantId = $this->createMerchantFixture();

        $tokenId = $this->createTokenFixture($cardId, $merchantId);

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals($tokenId, $response['cards'][0]['tokens'][0]['id']);

        $this->assertEquals('10000custgcard', $response['cards'][1]['tokens'][0]['id']);

        $tokenMerchantId1 = $response['cards'][0]['tokens'][0]['merchant_id'];

        $tokenMerchantId2 = $response['cards'][1]['tokens'][0]['merchant_id'];

        $merchantDetails1 = [
            'website_name'  => 'testing',
            'name'          => 'random merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/abcd/xyz_original.png'
        ];

        $merchantDetails2 = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails1, $response['mappings']['merchants'][$tokenMerchantId1]);

        $this->assertArraySelectiveEquals($merchantDetails2, $response['mappings']['merchants'][$tokenMerchantId2]);
    }

    public function testFetchAppTokensV2MultipleCardsSameMerchant()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $cardId = $this->createCardFixture();

        $tokenId = $this->createTokenFixture($cardId, '111111Razorpay');

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2MultipleCardsDifferentMerchantsSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals($tokenId, $response['cards'][0]['tokens'][0]['id']);

        $this->assertEquals('10000custgcard', $response['cards'][1]['tokens'][0]['id']);

        $merchantDetails = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $tokenMerchantId1 = $response['cards'][0]['tokens'][0]['merchant_id'];

        $tokenMerchantId2 = $response['cards'][1]['tokens'][0]['merchant_id'];

        $this->assertEquals($tokenMerchantId1, $tokenMerchantId2);

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId1]);
    }

    public function testFetchAppTokensV2OnlyFetchesCardTokens()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $cardId = $this->createCardFixture();

        $tokenId = $this->createTokenFixture($cardId, '111111Razorpay');

        $token = $this->fixtures->create('token', [
                'method'      => 'wallet',
                'card_id'     => null,
                'customer_id' => '10000gcustomer',
                'merchant_id' => '111111Razorpay',
                'used_at'     => Carbon::now()->getTimestamp(),
            ]
        );

        $walletTokenId = $token->id;

        $this->ba->publicAuth();

        $testData = $this->testData['testFetchAppTokensV2MultipleCardsDifferentMerchantsSuccessful'];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertEquals($tokenId, $response['cards'][0]['tokens'][0]['id']);

        $this->assertEquals('10000custgcard', $response['cards'][1]['tokens'][0]['id']);

        $merchantDetails = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $tokenMerchantId1 = $response['cards'][0]['tokens'][0]['merchant_id'];

        $tokenMerchantId2 = $response['cards'][1]['tokens'][0]['merchant_id'];

        $this->assertEquals($tokenMerchantId1, $tokenMerchantId2);

        $tokenIds = [];

        for ($i = 0, $iMax = count($response['cards'][0]['tokens']); $i < $iMax; $i++)
        {
            $tokenIds[] = $response['cards'][0]['tokens'][$i]['id'];
        }

        for ($i = 0, $iMax = count($response['cards'][1]['tokens']); $i < $iMax; $i++)
        {
            $tokenIds[] = $response['cards'][1]['tokens'][$i]['id'];
        }

        $this->assertNotContains($walletTokenId, $tokenIds);

        $this->assertArraySelectiveEquals($merchantDetails, $response['mappings']['merchants'][$tokenMerchantId1]);
    }

    public function testFetchAppTokensV2SingleCardMultipleTokensDifferentMerchantsSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $merchantId2 =  $this->createMerchantFixture();

        $tokenId = $this->createTokenFixture('100000000gcard', $merchantId2);

        $token = $this->getDbEntityById('token', $tokenId);

        $token->setAttribute('created_at', 1234567890);

        $token->saveOrFail();

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals('10000custgcard', $response['cards'][0]['tokens'][0]['id']);

        $this->assertEquals($tokenId, $response['cards'][0]['tokens'][1]['id']);

        $tokenMerchantId1 = $response['cards'][0]['tokens'][0]['merchant_id'];

        $tokenMerchantId2 = $response['cards'][0]['tokens'][1]['merchant_id'];

        $merchantDetails1 = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $merchantDetails2 = [
            'website_name'  => 'testing',
            'name'          => 'random merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/abcd/xyz_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails1, $response['mappings']['merchants'][$tokenMerchantId1]);

        $this->assertArraySelectiveEquals($merchantDetails2, $response['mappings']['merchants'][$tokenMerchantId2]);
    }

    public function testFetchAppTokensV2MultipleCardsMultipleTokensMultipleMerchantsSuccessful()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockSession();

        $this->attachDifferentMerchantWithSessionCustomer('test merchant', 'https://www.abcd.xyz.com', '/logos/random_image.png');

        $merchantId2 =  $this->createMerchantFixture();

        $tokenId1 = $this->createTokenFixture('100000000gcard', $merchantId2);

        $token1 = $this->getDbEntityById('token', $tokenId1);

        $token1->setAttribute('created_at', 1234567890);

        $token1->saveOrFail();

        $cardId = $this->createCardFixture();

        $tokenId2 = $this->createTokenFixture($cardId, $merchantId2);

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals($tokenId2, $response['cards'][0]['tokens'][0]['id']);

        $this->assertEquals('10000custgcard', $response['cards'][1]['tokens'][0]['id']);

        $this->assertEquals($tokenId1, $response['cards'][1]['tokens'][1]['id']);

        $tokenMerchantId1 = $response['cards'][0]['tokens'][0]['merchant_id'];

        $tokenMerchantId2 = $response['cards'][1]['tokens'][0]['merchant_id'];

        $tokenMerchantId3 = $response['cards'][1]['tokens'][1]['merchant_id'];

        $this->assertEquals($tokenMerchantId1, $tokenMerchantId3);

        $merchantDetails1 = [
            'website_name'  => 'xyz',
            'name'          => 'test merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/random_image_original.png'
        ];

        $merchantDetails2 = [
            'website_name'  => 'testing',
            'name'          => 'random merchant',
            'logo_url'      => 'https://dummycdn.razorpay.com/logos/abcd/xyz_original.png'
        ];

        $this->assertArraySelectiveEquals($merchantDetails1, $response['mappings']['merchants'][$tokenMerchantId2]);

        $this->assertArraySelectiveEquals($merchantDetails2, $response['mappings']['merchants'][$tokenMerchantId1]);
    }

    public function testFetchAppTokensV2CustomerNotAuthenticated()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppTokensV2Successful()
    {
        $this->mockSession();

        $card = $this->fixtures->create('card');

        $tokenId = $this->createTokenFixture($card->getId());

        $this->testData[__FUNCTION__]['request']['content']['tokens'][] = $tokenId;

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('success', $response);

        $this->assertArraySelectiveEquals([$tokenId], $response['success']);
    }

    public function testDeleteAppTokensV2CustomerNotAuthenticated()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppTokensV2SizeValidationFailure()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppTokensV2TypeValidationFailure()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppTokensV2CountNotEqualValidationFailure()
    {
        $this->mockSession();

        $card = $this->fixtures->create('card');

        $tokenId1 = $this->createTokenFixture($card->getId());

        $tokenId2 = $this->createTokenFixture($card->getId());

        $tokens = [$tokenId1, $tokenId2, '12345678901234'];

        $this->testData[__FUNCTION__]['request']['content']['tokens'] = $tokens;

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppTokensV2DeleteMultipleTokensSuccessful()
    {
        $this->mockSession();

        $card1 = $this->fixtures->create('card');

        $card2 = $this->fixtures->create('card');

        $tokenId1 = $this->createTokenFixture($card1->getId());

        $tokenId2 = $this->createTokenFixture($card1->getId());

        $tokenId3 = $this->createTokenFixture($card2->getId());

        $tokenId4 = $this->createTokenFixture($card2->getId());

        $tokens = [$tokenId1, $tokenId3, $tokenId4];

        $testData = $this->testData['testDeleteAppTokensV2Successful'];

        $testData['request']['content']['tokens'] = $tokens;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('success', $response);

        $this->assertArraySelectiveEquals($tokens, $response['success']);
    }

    public function testFetchCustomerTokensInternalForGlobalCustomer()
    {
        $this->mockSession();

        $globalCustomerId = '10000gcustomer';

        $cardId1 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'iin' => '526731',
            'vault' => 'mastercard',
            'network' => 'MasterCard',
            'last4' => '5449',
            'issuer' => 'KKBK',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId1 = 'KuClzN7vGGpga0';
        $this->createTokenFixture($cardId1, Account::TEST_ACCOUNT, [
            'id' => $tokenId1,
            'token' => 'KuClzN8Q5ttGR8',
            'customer_id' => $globalCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $cardId2 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'issuer' => 'HDFC',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId2 = 'KuClzOupNoxchg';
        $this->createTokenFixture($cardId2, Account::TEST_ACCOUNT, [
            'id' => $tokenId2,
            'token' => 'KuClzOvHeBP36p',
            'customer_id' => $globalCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testFetchCustomerTokensInternalForLocalCustomer()
    {
        $localCustomerId = '100001customer';

        $this->fixtures->create('customer', [
            'id' => $localCustomerId,
            'merchant_id' => Account::TEST_ACCOUNT,
        ]);

        $cardId1 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'iin' => '526731',
            'vault' => 'mastercard',
            'network' => 'MasterCard',
            'last4' => '5449',
            'issuer' => 'KKBK',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId1 = 'KuClzN7vGGpga0';
        $this->createTokenFixture($cardId1, Account::TEST_ACCOUNT, [
            'id' => $tokenId1,
            'token' => 'KuClzN8Q5ttGR8',
            'customer_id' => $localCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162,
        ]);

        $cardId2 = $this->createCardFixture([
            'merchant_id' => Account::TEST_ACCOUNT,
            'issuer' => 'HDFC',
            'token_expiry_month' => '01',
            'token_expiry_year' => '2030',
        ]);
        $tokenId2 = 'KuClzOupNoxchg';
        $this->createTokenFixture($cardId2, Account::TEST_ACCOUNT, [
            'id' => $tokenId2,
            'token' => 'KuClzOvHeBP36p',
            'customer_id' => $localCustomerId,
            'used_at' => 1671548162,
            'created_at' => 1671548162,
            'expired_at' => 1895064892,
            'updated_at' => 1671548162
        ]);

        $this->ba->checkoutServiceProxyAuth();

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

    protected function mockSharedAccountMerchant($name, $website = null, $logoUrl = null, $billingLabel = null, $businessName = null)
    {
        $merchant = $this->getDbEntityById('merchant', '100000Razorpay');

        $merchant->setAttribute('name', $name);

        $merchant->setAttribute('website', $website);

        $merchant->setAttribute('logo_url', $logoUrl);

        $merchant->setAttribute('billing_label', $billingLabel);

        $merchant->saveOrFail();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '100000Razorpay',
            'business_name' => $businessName
        ]);
    }

    protected function attachDifferentMerchantWithSessionCustomer($name, $website = null, $logoUrl = null, $billingLabel = null, $businessName = null)
    {
        $this->fixtures->create('merchant', [
            'id'            => '111111Razorpay',
            'website'       => $website,
            'name'          => $name,
            'logo_url'      => $logoUrl,
            'billing_label' => $billingLabel
        ]);


        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '111111Razorpay',
            'business_name' => $businessName
        ]);

        $token = $this->getDbEntityById('token', '10000custgcard');

        $token->setAttribute('merchant_id', '111111Razorpay');

        $token->saveOrFail();
    }

    protected function createTokenFixture($cardId, $merchantId = '100000Razorpay', array $attributes = [])
    {
        $token = $this->fixtures->create('token', array_merge([
                'method'      => 'card',
                'card_id'     => $cardId,
                'customer_id' => '10000gcustomer',
                'merchant_id' => $merchantId,
                'used_at'     => Carbon::now()->getTimestamp(),
                'used_count'  => 1,
                'status'      => 'active',
            ], $attributes
        ));

        return $token->getId();
    }

    protected function createCardFixture(array $attributes = [])
    {
        $card = $this->fixtures->create('card', array_merge([
                'country'       => 'IN',
                "last4"         => "1234",
                "network"       => "Visa",
                "type"          => "credit",
                "issuer"        => "sbi",
                "expiry_month"  => 12,
                "expiry_year"   => 2024,
                'vault'         => 'visa',
            ],
            $attributes
        ));

        return $card->getId();
    }

    protected function createMerchantFixture()
    {
        $merchant = $this->fixtures->create('merchant', [
            'website'  => 'https://www.testing.com',
            'name'     => 'random merchant',
            'logo_url' => '/logos/abcd/xyz.png',
        ]);

        return $merchant->getId();
    }

    protected function fixturesToCreateToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = '100000Razorpay',
        $customerId = '10000gcustomer',
        $inputFields = []
    )
    {
        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => $inputFields['vault'] ?? 'rzpvault',
                'vault_token'   => 'test_token',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'token'           => $inputFields['token'] ?? '1000lcardtoken',
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'status'          => (array_key_exists('status',$inputFields) === true ) ? $inputFields['status'] :'active',
                'internal_error_code'  => $inputFields['internal_error_code'] ?? null,
                'error_description'    => $inputFields['error_description'] ?? null,
            ]
        );
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }
}
