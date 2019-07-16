<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Exception\BadRequestException;
use RZP\Services\RazorXClient;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PaymentCreateTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreatePaymentWithoutOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSuccessCreatePaymentForMultipleCurrencies()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' =>  true]);

        $payment = $this->getDefaultPaymentArray();

        foreach ($data as $sucecssPayment)
        {
            $payment['currency'] = $sucecssPayment['currency'];

            $payment['amount'] = $sucecssPayment['amount'];

            $this->doAuthPayment($payment);
        }
    }

    public function testFailedCreatePaymentForMultipleCurrencies()
    {
        $data = $this->testData[__FUNCTION__]['requestData'];

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' =>  true]);

        $payment = $this->getDefaultPaymentArray();

        foreach ($data as $failedPayment)
        {
            $payment['currency'] = $failedPayment['currency'];

            $payment['amount'] = $failedPayment['amount'];

            $responseData = $this->testData[__FUNCTION__]['responseData'];;

            $responseData['response']['content']['error']['description'] = 'The amount must be atleast ' .
                $payment['currency'] . ' '. amount_format_IN(Currency::getMinAmount($payment['currency']));

            $this->runRequestResponseFlow($responseData, function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
        }
    }

    public function testCreatePaymentWithValidOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentWithValidOrderIdWithTrace()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory', 'log_response']);

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentWithInvalidMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'invalid';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    // Test to check if payment fails on disabled methods
    public function testCreatePaymentWithDisabledMethod()
    {
        $this->fixtures->merchant->disableNetbanking();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    // Test to check if payment is success on enabled methods
    public function testCreatePaymentWithEnabledMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentWithoutMethod()
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['method']);

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentWithoutCardNumber()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['card']['number']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithoutContact()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['contact']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecure()
    {
        $this->payment['card']['number'] = '555555555555558';

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecureError()
    {
        $this->payment['card']['number'] = '555555555555559';

        $this->changeEnvToNonTest();

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertEquals($content['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_VALIDATION_FAILURE');
    }

    public function testPaymentWithUnderscoreArray()
    {
        $this->payment['_'] = array('1' => 2, '3' => 4);

        $content = $this->doAuthPayment($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCallbackOnAuthorizedPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->doAuthPayment();
        $id = $payment['razorpay_payment_id'];
    }

    public function testWalletPostFormViaPaymentCreate()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testWalletPostFormWithDummyEmailForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        $payment['contact'] = '+919999999998';
        unset($payment['email']);

        $payment = $this->doAuthPayment($payment, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $this->getLastEntity('payment', true);
    }

    public function testWalletPostFormWithDummyEmailAndPhoneForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        unset($payment['contact'], $payment['email'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testWalletPostFormEmailNotOptionalForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        $payment['contact'] = '+919999999998';

        unset($payment['email'], $payment['notes']);

        $data = $this->testData['testWalletPostFormEmailNotOptionalForAmazonPay'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        });
    }

    public function testCoprotoForMissingBankAccountDetailsForFirstRecurring()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('ICIC');

        unset($payment['notes']);

        $response = $this->getFormViaCreateRoute($payment, 'emandate.form');

        $content = $response['content'];
        unset($content['bank_account[name]'],
            $content['bank_account[account_number]'],
            $content['bank_account[ifsc]'],
            $content['aadhaar[number]']);

        $content['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766'
        ];
        // TODO: Figure out why auth_type is not coming in the form response even though it's present in the input!!
        $content['auth_type'] = 'netbanking';

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $content['amount']]);
        $content['order_id'] = $order->getPublicId();

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testCoprotoForMissingBankAccountDetailsForSecondRecurring()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('ICIC');

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766'
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        $payment['amount'] = 3000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //
        $response = $this->doS2SRecurringPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('netbanking_icici', $paymentEntity['gateway']);
    }

    public function testRecurringTokenForEmandate()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766'
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 2000,
            'expire_by' => $expireBy,
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals(2000, $token['max_amount']);
        $this->assertEquals($expireBy, $token['expired_at']);
    }

    public function testRecurringTokenForSecondRecurringForEmandate()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766'
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 3000,
            'expire_by' => $expireBy,
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        unset($payment['bank_account'], $payment['auth_type']);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();
        // basically, recurring_token max_amount should not matter
        // here because this is second recurring payment
        $payment['recurring_token']['max_amount'] = 1000;

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);
        $paymentEntity = $this->getLastEntity('payment', true);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals(3000, $token['max_amount']);
    }

    public function testSecondRecurringWithMissingBankAccountDetailsAndAuthType()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766'
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        unset($payment['bank_account'], $payment['auth_type']);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //
        $paymentId = $this->doS2SRecurringPayment($payment)['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        // Setting created at to 8 am. Payments for debit are picked from 9 to 9 cycle
        $this->fixtures->edit(
            'payment',
            $paymentId,
            ['created_at' => Carbon::today(Timezone::IST)->addHours(8)->getTimestamp()]
        );

        $this->ba->adminAuth();

        $data = $this->testData[__FUNCTION__];
        $this->startTest($data);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($token['account_number']);
        $this->assertNotNull($token['beneficiary_name']);
        $this->assertNotNull($token['ifsc']);
    }

    public function testEmandatePaymentCreateFailIfBankMissing()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('ICIC');

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766'
        ];

        unset($payment['bank']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testInternationalPayment()
    {
        $this->fixtures->merchant->enableInternational();
        $this->payment['card']['number'] = '4012010000000007';
        $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testPaymentEmails()
    {
        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $dummyOrg['id']]);

        $this->fixtures->merchant->addFeatures(['dummy']);

        Mail::fake();

        Mail::setFakeConfig();

        $this->doAuthCaptureAndRefundPayment($this->payment);

        Mail::assertQueued(CapturedMail::class);

        Mail::assertNotQueued(AuthorizedMail::class);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testIntlPaymentWhenNotAllowed()
    {
        $this->fixtures->merchant->disableInternational();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED');
    }

    public function testCreatePaymentInEs()
    {
        $esMock = $this->createEsMock(['bulkUpdate']);

        $expected = $this->testData[__FUNCTION__];

        // Ref to InvoiceTest.testCreateInvoiceAndAssertEsSync() test on why
        // this is being asserted differently.

        $expectedNotes = [
            'merchant_order_id' => 'random order id',
        ];

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected, $expectedNotes)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                            $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                            $this->assertNotEmpty($actual['body'][1]['id']);

                            return true;
                        }));

        $this->doAuthPaymentViaCheckoutRoute($this->payment);
    }

    public function testPaymentRoutedThroughCps()
    {
        $this->markTestSkipped();

        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->ba->adminAuth();

        $request = $this->testData[__FUNCTION__]['request'];

        $data = $this->makeRequestAndGetContent($request);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('cps');

        $this->ba->publicAuth();

        $payment = $this->doAuthPayment();

        $pay = $this->getLastEntity('payment', true);

        $this->assertTrue($pay['cps_route']);

        $this->ba->adminAuth();

        $request['content']['cps_service_enabled'] = 0;

        $data = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        $payment = $this->doAuthPayment();

        $pay = $this->getLastEntity('payment', true);

        $this->assertFalse($pay['cps_route']);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForSuccess()
    {
        $payment = $this->doAuthPayment();

        $callbackUrl = $this->recorder->callbackUrl;

        $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($payment, $content);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForError()
    {
        // This will have raised an insufficient balance error.
        $this->makeRequestAndCatchException(function()
        {
            $this->payment['card']['number'] = '5010101010101015';
            $content = $this->doAuthPayment($this->payment);
            $this->doAuthPayment();
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $callbackUrl = $this->recorder->callbackUrl;

            $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);
        });
    }

    public function testPaymentS2SOnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->config['app.throw_exception_in_testing'] = false;

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['cvv'] = '1';

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $error = $response['error'];
        $this->assertEquals($error['field'], 'cvv');
        $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($error['description'], 'The cvv must be between 3 and 4 digits.');
    }

    /**
     * Tests S2S on partner auth with application feature(S2S)
     */
    public function testPaymentS2SOnPartnerAuth()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $response['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'authorized');
    }

    /**
     * Tests S2S failure on partner auth with application feature(S2S) missing
     */
    public function testPaymentS2SOnPartnerAuthWrongApp()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'notAllowedPApp'
            ]);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'notAllowedPApp', 'name' => 's2s']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $error = $response['error'];
        $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($error['description'], 'The requested URL was not found on the server.');
    }

    public function testNotEnrolledCardPaymentS2SOnPrivateAuth()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testPaymentWithEmptyAcquirerData()
    {
        $this->fixtures->merchant->addFeatures(['expose_arn_payment']);

        $paymentData = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($paymentData);

        $request = [
            'method'    => 'GET',
            'url'       => '/payments/' . $payment['razorpay_payment_id']
        ];

        $this->ba->privateAuth();

        // Get raw response
        $response = $this->sendRequest($request)->getContent();

        $this->assertRegexp('/' . preg_quote('"acquirer_data":{"auth_code":"') . '[0-9]{6}' . preg_quote('"}') . '/' , $response);
    }

    public function testPaymentWithAcquirerData()
    {
        $this->fixtures->merchant->addFeatures(['expose_arn_payment']);

        $paymentData = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthPayment($paymentData);

        $payment = $this->getLastEntity('payment');

        $this->assertArrayHasKey('acquirer_data', $payment);

        $this->assertArrayHasKey('bank_transaction_id', $payment['acquirer_data']);
    }

    public function testPreferredRecurringPaymentInputValidation()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $payment['recurring'] = 'xyz';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->getFormViaCreateRoute($payment);
            });
    }

    public function testPreferredRecurringPaymentInputValidationInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');
        $payment['recurring'] = true;

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testPreferredRecurringPaymentRecurringInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['recurring'] = 'preferred';

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['razorpay_payment_id'], $paymentEntity['id']);
        $this->assertEquals(false, $paymentEntity['recurring']);
    }

    public function testPreferredRecurringPaymentInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['razorpay_payment_id'], $paymentEntity['id']);
        $this->assertEquals(false, $paymentEntity['recurring']);
    }

    public function testPreferredRecurringPaymentCard()
    {
        $this->mockCardVault();

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = true;
        $payment['recurring'] = 'preferred';

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $paymentEntity['recurring']);
    }

    public function testDirectSettlementPayment()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testPaymentSettledBy()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('Razorpay', $payment['settled_by']);
    }

    public function testPaymentS2SRedirectPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthMaestro()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5081597022059105';

        unset($payment['card']['cvv']);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthRazorx()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthInvalidTrackId()
    {
        $request = [
            'request' => [
                'url' => '/payments/1234/redirect',
                'method' => 'get',
                'content' => [],
            ],
            'response' => []
        ];

        $this->ba->directAuth();

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $this->runRequestResponseFlow($request);
        },
        \RZP\Exception\BadRequestException::class,
        'Payment failed');
    }

    protected function setupEmandateAndGetPaymentRequest($bank = 'HDFC', $amount = 2000)
    {
        $this->mockCardVault();
        $this->fixtures->create('terminal:shared_emandate_icici_terminal');
        $this->fixtures->create('terminal:shared_emandate_axis_terminal');
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray($bank, $amount);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        return $payment;
    }

    public function testPaymentEditNotes()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPaymentFailedEditNotesMoreThan15Entries()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPaymentFailedEditNotesArrayValue()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }


    public function testPaymentByUpiTpvForSpecificBanks()
    {
        // mocking the gateway call to check the bank account in input
        $this->mockGateway();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableTpv();

        $order = $this->fixtures->create('order', ['bank' => IFSC::KKBK, 'account_number' => '923729373']);

        $payment['amount'] = 1000000;

        $payment['bank'] = IFSC::KKBK;

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    protected function mockGateway()
    {
        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
            Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway,$action,$input,$mode)
            {
                $length = strlen($input['order']['account_number']);
                $this->assertEquals(14, $length);
            });

        $this->app->instance('gateway', $gateway);
    }
    public function testPaymentFailOnDinersAndDisableMerchant()
    {
        $this->changeEnvToNonTest();

        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        // enabling the diners cards
        $this->fixtures->merchant->enableCardNetworks('10000000000000',['dicl']);

        // disabling the terminal as we want to test for "No terminal found"
        $this->fixtures->on('live')->terminal->edit('1n25f6uN5S1Z5a', ['enabled' =>  0]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '30569309025904';

        $this->doAuthPayment($payment);

        $entity = $this->getDbLastEntity('methods', 'live');

        // checking whether diners card got disabled or not for the merchant
        $this->assertEquals(false, $entity->isCardNetworkEnabled('DICL'));

    }

    public function testForRuPayPaymentOnHitachiTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'RuPay',
        ]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertNull($paymentObj['gateway_captured'] );
    }

    public function testForMasterCardPaymentOnHitachiTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
        ]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertTrue($paymentObj['gateway_captured'] );
    }

    public function testPaymentOnNetbankingEbsTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal',['mode'=>2]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::EBS_RAZORPAY_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertTrue($paymentObj['gateway_captured'] );
    }

    public function testCreatePaymentCardTypePrepaid()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCardTypePrepaidWithPrepaidRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'      => 'card',
            'merchant_id' => '10000000000000',
            'step'        => 'authorization',
            'gateway'     => 'axis_genius',
            'type'        => 'filter',
            'method_type' => 'prepaid',
            'filter_type' => 'select',
            'group'       => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('axis_genius', $paymentObj['gateway']);
    }

    public function testCreatePaymentCardTypePrepaidWithDefaultRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'      => 'card',
            'merchant_id' => '10000000000000',
            'step'        => 'authorization',
            'gateway'     => 'hdfc',
            'type'        => 'filter',
            'filter_type' => 'select',
            'group'       => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $paymentObj['gateway']);
    }


    public function testPaymentFailOnNetBankingAndDisableMerchant()
    {
        $this->changeEnvToNonTest();

        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        // disabling the terminal as we want to test for "No terminal found"
        $this->fixtures->on('live')->terminal->edit('1n25f6uN5S1Z5a', ['enabled' =>  0]);

        $res = $this->doAuthPayment($payment);

        $this->assertEquals($res['error']['internal_error_code'], ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
    }
}
