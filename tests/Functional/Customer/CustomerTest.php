<?php

namespace RZP\Tests\Functional\Customer;

use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

use Mockery;

class CustomerTest extends TestCase
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

    public function testOtpWorkFlowWithEmailRequired()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        $this->sendOtp('9988776655');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->verifyOtp('9988776655', null, '233323');
        });

        $this->runRequestResponseFlow($data, function()
        {
            $this->verifyOtp('9988776655', '', '233323');
        });

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

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }
    
    public function testCardCountryDetailsInOtpFlow()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);

        $this->assertNotEquals($content['tokens'], null);

        $this->assertNotEquals($content['tokens']['items'][0]['card']['country'], null);
    }
}
