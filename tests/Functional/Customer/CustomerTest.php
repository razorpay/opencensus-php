<?php

namespace RZP\Tests\Functional\Customer;

use Carbon\Carbon;
use JetBrains\PhpStorm\NoReturn;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Models\Merchant;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

use Mockery;
use RZP\Models\Customer\Account\Constants as AccountConstants;

class customerTest extends TestCase
{
    use AttemptTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerWithValidNames()
    {
        $this->ba->privateAuth();

        //
        // To create different unique customers. Uniqueness is asserted in code
        // by email & contact.
        //
        $validNameEmailMap = [
            'Sample name'        => 'test1@test.razorpay.com',
            'ABC Corp Pvt. Ltd.' => 'test2@test.razorpay.com',
            'ABC Corp (Pvt)'     => 'test3@test.razorpay.com',
            'Sample\'d name'     => 'test4@test.razorpay.com',
            'A & B pvt ltd'      => 'test5@test.razorpay.com',
            'A-B pvt Ltd'        => 'test6@test.razorpay.com',
            'A-B pvt (test) Ltd' => 'test7@test.razorpay.com',
            'M-dash–Name'        => 'test8@test.razorpay.com',                 //Names with m-dash should be valid (–)
            'Underscore_ABC'     => 'test9@test.razorpay.com',
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($validNameEmailMap as $name => $email)
        {
            $testData['request']['content']['name'] = $testData['response']['content']['name'] = $name;
            $testData['request']['content']['email'] = $testData['response']['content']['email'] = $email;

            $this->startTest();
        }
    }

    public function testCreateCustomerWithNameNull()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateCustomerWithLeadingOrTrailingSpaces()
    {
        $this->ba->privateAuth();

        $validNameEmailMap = [
            '   Sample name'        => 'test1@test.razorpay.com',
            'Sample name   '        => 'test2@test.razorpay.com'
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($validNameEmailMap as $name => $email)
        {
            $testData['request']['content']['name']  = $name;
            $testData['response']['content']['name'] = trim($name); // In db it should get mutated(trimmed) before persistence

            $testData['request']['content']['email'] = $testData['response']['content']['email'] =  $email;

            $this->startTest();
        }

    }

    public function testCreateCustomerWithInvalidNames()
    {
        $this->ba->privateAuth();

        $invalidNameErrorMap = [
            'Sample"s name'                                       => 'The name format is invalid.',
            'A very big big big name off some big big big person' => 'The name may not be greater than 50 characters.',
            'A weird? name'                                       => 'The name format is invalid.',
            '-AB weird name'                                     => 'The name format is invalid.',
            '  -AB weird name'                                   => 'The name format is invalid.', // Validation must happens on trimmed value
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($invalidNameErrorMap as $name => $error)
        {
            $testData['request']['content']['name']                  = $name;
            $testData['response']['content']['error']['description'] = $error;

            $this->startTest();
        }
    }

    public function testCreateCustomerInvalidGstin()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateCustomerEmailOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerUppercaseEmailOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);
    }

    public function testCreateCustomerContactOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicatePhone()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicateEmail()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicate()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateCustomerDuplicateDontFail()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateGlobalCustomerWithValidEmail(): void
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $input = $this->testData[__FUNCTION__];

        $response = $this->editGlobalCustomer($input['request']['content']);

        $this->assertEquals([], $response);
    }

    public function testUpdateGlobalCustomerWithInvalidEmail(): void
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $input = $this->testData[__FUNCTION__];

        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('The email must be a valid email address.');

        $this->editGlobalCustomer($input['request']['content']);
    }

    public function testUpdateGlobalCustomerWithInvalidInput(): void
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $input = $this->testData[__FUNCTION__];

        $this->expectException(Exception\ExtraFieldsException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED);
        $this->expectExceptionMessage('other is/are not required and should not be sent');

        $this->editGlobalCustomer($input['request']['content']);
    }

    public function testUpdateGlobalCustomerWithInvalidSession(): void
    {
        // we havent mocked session.

        $this->ba->publicAuth();

        $input = $this->testData[__FUNCTION__];

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        $this->expectExceptionMessage('The user is not authenticated');

        $this->editGlobalCustomer($input['request']['content']);
    }

    public function testUpdateCustomerEmail()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateCustomerName()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetMultipleCustomersViaEs()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('customer', ['id' => '100001customer']);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testOtpFlowForEmailOptionalMerchants()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        $this->sendOtp('9988776655');

        $this->fixtures->merchant->addFeatures(['email_optional']);

        $responseWhenEmailNull = $this->verifyOtp('9988776655', null, '233323');

        $this->assertEquals($responseWhenEmailNull['success'], 1);

        $responseWhenEmailBlank = $this->verifyOtp('9988776655', ' ', '233323');

        $this->assertEquals($responseWhenEmailBlank['success'], 1);

        $responseWithValidEmail = $this->verifyOtp('9988776655', 'test@razorpay.com', '233323');

        $this->assertEquals($responseWithValidEmail['success'], 1);
    }

    public function testOtpFlowWithoutDeviceToken()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233323');

        $this->assertEquals($content['success'], 1);
    }

    public function testOtpFlowWithDeviceToken()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertEquals($content['success'], 1);
    }

    public function testOtpFlowForAndroidSdk()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'active']);

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);

        $this->assertNotEquals($content['tokens'], null);
    }

    public function testOtpFlowWithInvalidNumber()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->sendOtp('4637346743722');
        });
    }

    public function testOtpFlowForCardlessEmiProviders()
    {
        $this->ba->publicAuth();

        $this->mockSNS();

        $response = $this->sendOtpForCardlessEmiProviders('9988776655', 'cardless_emi', 'zestmoney');

        $status_code = $response->getStatusCode();

        $this->assertEquals(200, $status_code);

        $content = $response->getContent();

        $content = json_decode($content, true);

        $this->assertEquals(true, $content['success']);
    }

    protected function sendOtp($contact)
    {
        $request = array(
            'url' => '/otp/create',
            'method' => 'post',
            'content' => [
                'contact' => $contact
            ],
        );

        $response = $this->sendRequest($request);

        return $response;
    }

    protected function sendOtpForCardlessEmiProviders($contact, $method, $provider)
    {
        $request = array(
            'url' => '/otp/create',
            'method' => 'post',
            'content' => [
                'contact'  => $contact,
                'method'   => $method,
                'provider' => $provider
            ],
        );

        $response = $this->sendRequest($request);

        return $response;
    }


    protected function verifyOtp($contact, $email, $otp, $deviceToken = null, $metadata = false)
    {
        $content = [
            'contact' => $contact,
            'email' => $email,
            'otp' => '0007',
        ];

        if ($deviceToken !== null)
        {
            $content['device_token'] = $deviceToken;
        }

        if ($metadata)
        {
            $content['_']['platform'] = 'android';
            $content['_']['library'] = 'checkoutjs';
            $content['_']['version'] = '1.0.0';
        }

        $request = array(
            'url' => '/otp/verify',
            'method' => 'post',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testCustomerWalletPayoutInsufficientWalletBalance()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->ba->privateAuth();

        $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 200]);

        $this->startTest();
    }

    /**
     * Merchant will not have sufficient balance to debit the incurred fees.
     */
    public function testCustomerWalletPayoutInsufficientMerchantBalance()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->ba->privateAuth();

        $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 1000]);
        $this->fixtures->edit('balance', '10000000000000', ['balance' => 100]);

        $this->startTest();
    }

    public function testCustomerWalletPayout()
    {
        $this->ba->privateAuth();

        $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 1000]);
        $this->fixtures->edit('balance', '10000000000000', ['balance' => 1000]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $payout = $this->startTest();

        $payout = $this->getDbEntityById('payout', $payout['id']);

        // Assert Customer transactions.
        $customerTransaction = $payout->transaction;

        $this->assertEquals(800, $customerTransaction->getAmount());

        $this->assertEquals(800, $customerTransaction->getDebit());

        $this->assertEquals(200, $customerTransaction->getBalance());

        // Assert Fund Transfer Attempt.
        $fundTransferAttempt = $this->getDbEntities('fund_transfer_attempt', ['source_id' => $payout->getId()])->first();

        $this->assertEquals($fundTransferAttempt->getChannel(), $payout->getChannel());

        $this->assertEquals(true, $fundTransferAttempt->isStatusCreated());

        // Merchant Adjustments for fee.
        $adjustment = $this->getDbEntities('adjustment', ['entity_id' => $payout->getId(),
                                                                 'entity_type' => 'payout',
                                                                 'merchant_id' => '10000000000000'])->first();
        $this->assertNotEmpty($adjustment);

        $this->assertEquals($adjustment->getAmount(), -600);

        $merchantFeeDebitTransaction = $adjustment->transaction;

        $this->assertNotEmpty($merchantFeeDebitTransaction);

        $this->assertEquals($merchantFeeDebitTransaction->getAmount(), 600);
        $this->assertEquals($merchantFeeDebitTransaction->getBalance(), 400);

        // Recon
        $result = $this->initiateTransfer(Channel::YESBANK, 'refund', 'payout');

//        $this->assertEquals(1, $result['yesbank']['success']);

        // This is not required as the job is dispatched to mark the payout status
        // in sync this will be done as part of `initiateTransfers`
//        $result = $this->reconcileEntitiesForChannel('yesbank');
//
//        $this->assertEquals(1, $result['total_count']);
//        $this->assertEquals('yesbank', $result['channel']);

        $customerTransaction->reload();

        // After recon we update the reconiledat value.
//        $this->assertNotNull($customerTransaction->getReconciledAt());

        return $payout;
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
                    {
                        $response = array(
                            'success' => true,
                        );

                        return $response;
                    });

        $this->app->instance('raven', $raven);
    }

    public function mockSns()
    {
        $sns = Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($input)
            {
                $json_decoded_input = json_decode($input, true);

                $this->assertEquals('sms.otp_cardless', $json_decoded_input['template']);

                $this->assertEquals('zestmoney', $json_decoded_input['params']['provider']);

                return $input;
            });

        $this->app->instance('sns', $sns);
    }

    public function testCustomerWalletPayoutReversal()
    {
        $payout = $this->testCustomerWalletPayout();

        $payout->setStatus(Payout\Status::INITIATED);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'     => 'failed',
            'failure_reason' => '',
        ]);

        $payout->reload();

        $input = [
            'entity_type' => 'payout',
            'entity_id' => $payout->getId()
        ];

        $reversals = (new Reversal\Repository)->fetch($input, $payout->merchant->getId());

        $reversal = $reversals->first();

        $this->assertEquals('reversed',$payout->getStatus());

        $this->assertEquals(800, $reversal->getAmount());

        // Assert Reversal and customer balance

        // Assert Customer transactions.
        $customerTransaction = $reversal->transaction;

        $this->assertEquals(800, $customerTransaction->getAmount());

        $this->assertEquals(800, $customerTransaction->getCredit());

        $this->assertEquals(1000, $customerTransaction->getBalance());

        $this->assertEquals('reversal', $customerTransaction->type);

        // Assert Merchant balance and adjustment.

        // Merchant Adjustments for fee.
        $adjustment = $this->getDbEntities('adjustment', ['entity_id'   => $reversal->getId(),
                                                          'entity_type' => 'reversal',
                                                          'merchant_id' => '10000000000000'])->first();

        $this->assertNotEmpty($adjustment);

        $this->assertEquals($adjustment->getAmount(), 600);

        // Asserts Merchant transaction and balance.
        $merchantFeeCreditTransaction = $adjustment->transaction;

        $this->assertNotEmpty($merchantFeeCreditTransaction);

        $this->assertEquals($merchantFeeCreditTransaction->getAmount(), 600);
        $this->assertEquals($merchantFeeCreditTransaction->getBalance(), 1000);
    }

    public function testCreateGlobalAddress()
    {
        $this->ba->publicAuth();
        $this->mockSession();
        $this->startTest();
    }

    public function testEditGlobalAddress()
    {
        $this->testCreateGlobalAddress();
        $res = $this->getDbLastEntity('address');
        $this->testData["testEditGlobalAddress"]["request"]["content"]["shipping_address"]["id"] = $res->getId();
        $this->ba->publicAuth();
        $this->mockSession();
        $this->startTest();
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    public function testCardCountryDetailsInOtpFlow()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);

        $this->assertNotEquals($content['tokens'], null);

        $this->assertNotEquals($content['tokens']['items'][0]['card']['country'], null);
    }

    public function test1ccDemoOtpFlow()
    {
        $this->ba->publicAuth();
//      We don't mock Raven here as raven shouldn't be triggered in this flow.
//      If Raven throws an error, the logic is incorrect

        $content = $this->verifyOtp(AccountConstants::DEMO_1CC_CONTACT, 'abc@razorpay.com', AccountConstants::DEMO_1CC_OTP, '123', true);
        $this->assertEquals(1, $content['success']);
        $this->assertNotNull($content['session_id']);
    }

    public function testDudupeLocalOverGlobalTokensWhenGlobalTokenExpectsToReturnGlobalToken()
    {
        $this->markTestSkipped('This test case is not applicable as we are not supporting global tokens');

        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixtureToCreateIin();
        $this->fixturesToCreateToken('100022xtokeng1', '100000003card2', '411140');

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);
        $this->assertNotEquals($content['tokens'], null);

        $tokenIds = $this->getTokenIds($content['tokens']['items']);

        $this->assertContains('token_100022xtokeng1', $tokenIds);
    }

    public function testDudupeLocalOverGlobalTokensWhenGlobalAndLocalTokenOfSameCardExpectsToReturnLocalToken()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixtureToCreateIin();
        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');
        $this->fixturesToCreateToken('100022xtokeng1', '100000003card2', '411140');

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);
        $this->assertNotEquals($content['tokens'], null);

        $tokenIds = $this->getTokenIds($content['tokens']['items']);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertNotContains('token_100022xtokeng1', $tokenIds);
    }

    public function testDudupeLocalOverGlobalTokensWhenGlobalAndLocalTokenOfSameCardOfDiffMerchantExpectsToReturnLocalTokenOfLoggedInMercahant()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixtureToCreateIin();
        $this->fixtures->merchant->createAccount('10000000000001');
        $this->fixturesToCreateToken('100022xtokenl2', '100000003card3', '411140', '10000000000001');
        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');
        $this->fixturesToCreateToken('100022xtokeng1', '100000003card2', '411140');

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);
        $this->assertNotEquals($content['tokens'], null);

        $tokenIds = $this->getTokenIds($content['tokens']['items']);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertNotContains('token_100022xtokeng1', $tokenIds);
        $this->assertNotContains('token_100022xtokenl2', $tokenIds);
    }

    public function testOtpVerifyResponseDoesNotContainStatusInactiveCardTokens(): void
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockRaven();

        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'rzpvault']);
        $this->fixturesToCreateToken('100022xtokenl2', '100000003card2', '411141', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'active']);
        $this->fixturesToCreateToken('100022xtokenl3', '100000003card3', '411142', '10000000000000', '10000gcustomer', ['vault' => 'visa']);
        $this->fixturesToCreateToken('100022xtokenl4', '100000003card4', '411143', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'deactivated']);
        $this->fixturesToCreateToken('100022xtokenl5', '100000003card5', '411144', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'deleted']);

        // send OTP
        $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);
        $this->assertNotEquals($content['tokens'], null);

        $tokenIds = $this->getTokenIds($content['tokens']['items']);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertContains('token_100022xtokenl2', $tokenIds);
        $this->assertNotContains('token_100022xtokenl3', $tokenIds);
        $this->assertNotContains('token_100022xtokenl4', $tokenIds);
        $this->assertNotContains('token_100022xtokenl5', $tokenIds);
    }

    public function testFetchTokensResponseContainsStatusInactiveCardTokens(): void
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->privateAuth();

        $payload = $this->testData['testGetCustomerTokens'];

        $this->fixturesToCreateToken('100022xtokenl1', '100000003card1', '411140', '10000000000000', '100000customer', ['vault' => 'rzpvault']);
        $this->fixturesToCreateToken('100022xtokenl2', '100000003card2', '411141', '10000000000000', '100000customer', ['vault' => 'visa', 'status' => 'active']);
        $this->fixturesToCreateToken('100022xtokenl3', '100000003card3', '411142', '10000000000000', '100000customer', ['vault' => 'visa']);
        $this->fixturesToCreateToken('100022xtokenl4', '100000003card4', '411143', '10000000000000', '100000customer', ['vault' => 'visa', 'status' => 'deactivated']);
        $this->fixturesToCreateToken('100022xtokenl5', '100000003card5', '411144', '10000000000000', '100000customer', ['vault' => 'visa', 'status' => 'deleted']);

        $payload['response']['content'] = [];

        $response = $this->startTest($payload);

        $tokenIds = $this->getTokenIds($response['items']);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertContains('token_100022xtokenl2', $tokenIds);
        $this->assertContains('token_100022xtokenl3', $tokenIds);
        $this->assertContains('token_100022xtokenl4', $tokenIds);
        $this->assertContains('token_100022xtokenl5', $tokenIds);
    }

    protected function getTokenIds($tokens): array
    {
        $tokenIds = [];

        foreach ($tokens as $token)
        {
            $tokenIds[] = $token['id'];
        }

        return $tokenIds;
    }

    protected function fixtureToCreateIin(): void
    {
        $this->fixtures->iin->create(
            [
                'iin'     => '411140',
                'country' => 'IN',
                'issuer'  => 'HDFC',
                'network' => 'Visa',
                'flows'   => [
                    '3ds'          => '1',
                    'headless_otp' => '1',
                ],
            ]
        );
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
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'status'          => $inputFields['status'] ?? NULL,
            ]
        );
    }

    public function testCreateGlobalCustomerMagicClub()
    {
        $this->ba->appAuthTest($this->config['applications.consumer_app.secret']);

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testGetGlobalCustomerMagicClub()
    {
        $this->testCreateGlobalCustomerMagicClub();

        $customer = $this->getLastEntity('customer', true);

        $this->testData['testGetGlobalCustomerMagicClub']['request']['content']['id'] = $customer['id'] ;

        $this->ba->appAuthTest($this->config['applications.consumer_app.secret']);

        $this->startTest();

        $this->assertNotNull($customer);
    }

    public function testGetOrCreateGlobalCustomerMagicClubInvalidInput()
    {
        $this->ba->appAuthTest($this->config['applications.consumer_app.secret']);

        $this->startTest();
    }

    public function testGetGlobalCustomerByID()
    {
        $this->fixtures->create('customer', [
            'id'          => 'magic1customer',
            'merchant_id' => '100000Razorpay',
            'contact'     => '9988771111']);
        $customer = $this->getLastEntity('customer', true);
        $this->ba->appAuthTest($this->config['applications.consumer_app.secret']);
        $res = $this->startTest();
    }

    public function testSupportPageOTPVerifyWhenValidInputIsPassedExpectsOTPVerificationAndCustomerPayments()
    {
        $this->ba->directAuth();

        $this->mockRaven();

        $contact = '+919988776666';

        // send OTP
        $response = $this->sendOtp($contact);

        $content = [
            'contact' => $contact,
            'mode' => 'test',
            'otp' => '0007',
        ];

        $this->fixtures->create('merchant', ['id' => '10000000000001']);
        $this->fixtures->create('merchant', ['id' => Merchant\Account::DEMO_PAGE_ACCOUNT]);

        $request = array(
            'url' => '/support/otp/verify',
            'method' => 'post',
            'content' => $content
        );

        // Current Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776666',
            'merchant_id' => '10000000000000',
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'contact'    => '9988776666',
            'merchant_id' => '10000000000001',
        ]);

        // Other Customer payments
        $payment3 = $this->fixtures->create('payment', [
            'contact'    => '+918888888888',
            'merchant_id' => '10000000000000',
        ]);

        $payment4 = $this->fixtures->create('payment', [
            'contact'    => '8888888888',
            'merchant_id' => '10000000000001',
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['success']);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        $this->assertContains($payment1->getPublicId(), $paymentIds);

        $this->assertContains($payment2->getPublicId(), $paymentIds);

        $this->assertNotContains($payment3->getPublicId(), $paymentIds);

        $this->assertNotContains($payment4->getPublicId(), $paymentIds);
    }

    public function testSupportPageOTPVerifyWhenInvalidInputIsPassedExpectsOTPVerificationFailureWithIncorrectOTPException()
    {
        $this->ba->directAuth();

        $contact = '+919988776666';

        // send OTP
        $response = $this->sendOtp($contact);

        $content = [
            'contact' => $contact,
            'mode' => 'test',
            'otp' => '0008',
        ];

        $this->fixtures->create('merchant', ['id' => Merchant\Account::DEMO_PAGE_ACCOUNT]);

        $request = array(
            'url' => '/support/otp/verify',
            'method' => 'post',
            'content' => $content
        );

        // Current Customer payment
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776666',
        ]);

        $this->makeRequestAndCatchException(
            function () use ($request) {
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'Verification failed because of incorrect OTP.'
        );
    }

    public function testFetchPaymentByContactOnSupportPageWhenUserLoggedInExpectsPaymentsWithCustomerContact()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test',
            'method' => 'get',
        );

        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        // Current Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'     => '+919988776655',
            'merchant_id' => '10000000000000',
            'status'      => 'captured',
            'method'      => 'card',
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'contact'     => '9988776655',
            'merchant_id' => '10000000000001',
            'status'      => 'pending',
        ]);

        $payment3 = $this->fixtures->create('payment', [
            'contact'     => '9988776655',
            'merchant_id' => '10000000000001',
            'status'      => 'failed',
        ]);

        // Other Customer payments
        $payment4 = $this->fixtures->create('payment', [
            'contact'     => '+918888888888',
            'merchant_id' => '10000000000000',
        ]);

        $payment5 = $this->fixtures->create('payment', [
            'contact'     => '8888888888',
            'merchant_id' => '10000000000001',
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        $paymentDetails = $this->getPaymentDetailsFromSupportPageFetchPaymentResponse($response);

        $this->assertContains($payment1->getPublicId(), $paymentIds);

        $this->assertContains($payment2->getPublicId(), $paymentIds);

        $this->assertContains($payment3->getPublicId(), $paymentIds);

        $this->assertNotContains($payment4->getPublicId(), $paymentIds);

        $this->assertNotContains($payment5->getPublicId(), $paymentIds);

        $this->assertEquals('card', $paymentDetails[$payment1->getPublicId()]['method']);

        $this->assertEquals('captured', $paymentDetails[$payment1->getPublicId()]['status']);

        $this->assertEquals('pending', $paymentDetails[$payment2->getPublicId()]['status']);

        $this->assertEquals('failed', $paymentDetails[$payment3->getPublicId()]['status']);
    }

    public function testFetchPaymentByContactOnSupportPageWhenUserNotLoggedInExpectsFailureWithUnauthorizedException()
    {
        $this->ba->directAuth();

        $request = array(
            'url' => '/apps/payments?mode=test',
            'method' => 'get',
        );

        // Current Customer payments
        $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
        ]);

        $this->makeRequestAndCatchException(
            function () use ($request) {
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'The user is not authenticated'
        );
    }

    public function testFetchPaymentByContactOnSupportPageWhenUserLoggedInAndLoggedOutExpectsFailureWithUnauthorizedException()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test',
            'method' => 'get',
        );

        // Current Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
        ]);

        $this->makeRequestAndGetContent($request);

        $logoutRequest = array(
            'url' => '/apps/logout',
            'method' => 'delete',
            'content' => [
                'logout' => 'app',
                'app_token' => 'capp_1000000custapp',
                'device_token' => '1000custdevice',
            ],
        );

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($logoutRequest);

        $this->ba->directAuth();

        $this->makeRequestAndCatchException(
            function () use ($request) {
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'The user is not authenticated'
        );
    }

    public function testFetchPaymentByContactOnSupportPageWhenUserLoggedInExpectsCustomerPaymentsWhichAreCreatedLessThan6Months()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test',
            'method' => 'get',
        );

        // Logged-in Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
        ]);

        $nowMinus7Months = Carbon::now()->subMonths(7)->getTimestamp();

        $payment2 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => $nowMinus7Months,
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        $this->assertContains($payment1->getPublicId(), $paymentIds);

        $this->assertNotContains($payment2->getPublicId(), $paymentIds);
    }

    public function testFetchPaymentByContactOnSupportPageWhenUserLoggedInExpectsPaymentsOrderByCreatedAt()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test',
            'method' => 'get',
        );

        // Logged-in Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp(),
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(2)->getTimestamp(),
        ]);

        $payment3 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(3)->getTimestamp(),
        ]);

        $payment4 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(4)->getTimestamp(),
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        // Payments ordered by created_at
        $this->assertEquals(
            [$payment1->getPublicId(), $payment2->getPublicId(), $payment3->getPublicId(), $payment4->getPublicId()],
            $paymentIds
        );

        // Payments not ordered by created_at
        $this->assertNotEquals(
            [$payment2->getPublicId(), $payment1->getPublicId(), $payment3->getPublicId(), $payment4->getPublicId()],
            $paymentIds
        );
    }

    public function testFetchPaymentByContactOnSupportPageWhenInputSkipAndCountIsPassedExpectsPaymentsRespectingCountAndSkip()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test&skip=1&count=2',
            'method' => 'get',
        );

        // Logged-in Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp(),
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(2)->getTimestamp(),
        ]);

        $payment3 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(3)->getTimestamp(),
        ]);

        $payment4 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(4)->getTimestamp(),
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        $this->assertEquals(2, count($paymentIds));

        $this->assertEquals(true, $response['has_more']);

        $this->assertNotContains($payment1->getPublicId(), $paymentIds);

        $this->assertContains($payment2->getPublicId(), $paymentIds);

        $this->assertContains($payment3->getPublicId(), $paymentIds);

        $this->assertNotContains($payment4->getPublicId(), $paymentIds);
    }

    public function testFetchPaymentByContactOnSupportPageWhenAllThePaymentAreFetchedExpectsPaymentsAndHasMoreAsFalse()
    {
        $this->ba->directAuth();

        $this->mockSession();

        $request = array(
            'url' => '/apps/payments?mode=test&skip=1&count=2',
            'method' => 'get',
        );

        // Logged-in Customer payments
        $payment1 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp(),
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'contact'    => '+919988776655',
            'created_at' => Carbon::now()->subDays(2)->getTimestamp(),
        ]);

        $response = $this->makeRequestAndGetContent($request);

        $paymentIds = $this->getPaymentIdsFromSupportPageFetchPaymentResponse($response);

        $this->assertEquals(false, $response['has_more']);

        $this->assertNotContains($payment1->getPublicId(), $paymentIds);

        $this->assertContains($payment2->getPublicId(), $paymentIds);
    }

    public function testGetGlobalCustomerDetailsForCheckoutService(): void
    {
        $this->mockSession();

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testGetLocalCustomerDetailsForCheckoutService(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $customerId = 'zMRVsGfjoQyc9w';

        $this->fixtures->create('customer', [
            'id' => $customerId,
            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
            'contact' => '+919876543210',
            'email' => 'testlocalcustomer@razorpay.com',
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id'   => Merchant\Account::TEST_ACCOUNT,
                'issuer'        => 'HDFC',
                'network'       => 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => 'visa',
                'vault_token'   => 'test_token',
            ]
        );

        $this->fixtures->create('token', [
                'customer_id'     => $customerId,
                'token'           => '1000lcardtoken',
                'method'          => 'card',
                'card_id'         => $card->getId(),
                'used_at'         => Carbon::now()->getTimestamp(),
                'merchant_id'     => Merchant\Account::TEST_ACCOUNT,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => '9999999999',
                'status'          => 'active',
            ]
        );

        $this->startTest();
    }

    protected function getPaymentIdsFromSupportPageFetchPaymentResponse(array $response): array
    {
        $payments = $response['payments'];

        $paymentIds = [];

        foreach ($payments as $payment)
        {
            $paymentIds[] = $payment['payment']['id'];
        }

        return $paymentIds;
    }

    public function getPaymentDetailsFromSupportPageFetchPaymentResponse(array $response): array
    {
        $payments = $response['payments'];

        $paymentDetails = [];

        foreach ($payments as $payment)
        {
            $paymentDetails[$payment['payment']['id']] = $payment['payment'];
        }

        return $paymentDetails;
    }

    protected function editGlobalCustomer(array $content)
    {
        $request = array(
            'url' => '/customers',
            'method' => 'patch',
            'content' => $content,
        );

        return $this->makeRequestAndGetContent($request);
    }
}
