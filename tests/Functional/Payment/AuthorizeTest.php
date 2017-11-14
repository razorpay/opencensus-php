<?php

namespace RZP\Tests\Functional\Payment;

use Mail;

use RZP\Models\Bank\IFSC;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Mail\Payment\Failed as PaymentFailedMail;
use RZP\Models\Payment as PaymentModel;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Constants\Mode;

class AuthorizeTest extends TestCase
{
    use PaymentTrait;

    /**
     * The payment array
     * @var array
     */
    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AuthorizeTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testSession()
    {
        $response = $this->withSession(['foo' => 'bar'])
                         ->get('/');

        $response->assertSessionHas('foo', 'bar');
    }

    public function testInvalidEmailInPayment()
    {
        $this->startTest();
    }

    public function testJsonpPayment()
    {
        Mail::fake();

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        Mail::assertSent(AuthorizedMail::class);
    }

    public function testEmailMissing()
    {
        unset($this->payment['email']);
        $this->startTest();
    }

    public function testUppercaseEmail()
    {
        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['email'], 'uppercase@razorpay.com');
    }

    public function testContactTooShort()
    {
        $this->startTest();
    }

    public function testContactTooLong()
    {
        $this->startTest();
    }

    public function testNegativeAmount()
    {
        $this->startTest();
    }

    public function testContactInvalidCountryCode()
    {
        $this->startTest();
    }

    public function testInvalidContactPassingSyntaxCheck()
    {
        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('43634423', $payment['contact']);
    }

    public function testNonSupportedCurrency()
    {
        $this->startTest();
    }

    public function testCardMissing()
    {
        unset($this->payment['card']);

        $this->startTest();
    }

    /**
     * This test first makes a card payment successfully using a non-maestro number
     * without the cvv being set. We assert that a BadRequestValidationFailureException
     * is thrown. We then make a payment using a maestro number that goes through successfully
     */
    public function testCardWithoutCvv()
    {
        $payment = $this->payment;

        unset($payment['card']['cvv']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        // Converting to a maestro card number
        $payment['card']['number'] = '5081597022059105';

        // Payment goes through fine without any exceptions
        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testPaymentCardAsString()
    {
        $this->startTest();
    }

    public function testAmountBelowMin()
    {
        $this->startTest();
    }

    public function testAmountVeryHigh()
    {
        $this->startTest();
    }

    public function testAuthorizeTimestamp()
    {
        $lower = time() - 1;
        $this->defaultAuthPayment();
        $upper = time() + 1;

        $payment = $this->getLastEntity('payment', true);

        $authorizedAt = $payment['authorized_at'];

        $this->assertLessThanOrEqual($authorizedAt, $lower);

        $this->assertGreaterThanOrEqual($authorizedAt, $upper);
    }

    public function testAmountLessThan50ForNetbanking()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function testAmountNonNumeric()
    {
        $this->startTest();
    }

    public function testAmountMissing()
    {
        $this->startTest();
    }

    public function testPaymentWithBlankMethod()
    {
        $payment = [
            'amount'            => '50000',
            'currency'          => 'INR',
            'description'       => 'random description',
            'method'            => '',
            'bank'              => '',
            'email'             => 'adsf@gmail.com',
            'contact'           => '8383893939',
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testDescriptionAsArray()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['description']['key'] = 'value';

        $this->startTest();
    }

    public function testDescriptionTooLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeText = implode(',', range(1,1000,1));

        $testData['request']['content']['description'] = $largeText;

        $this->startTest();
    }

    public function testNotesStringNotArray()
    {
        $this->startTest();
    }

    public function testExcessValuesInNotes()
    {
        $testData = & $this->testData[__FUNCTION__];

        foreach (range(1, 16, 1) as $i)
        {
            $testData['request']['content']['notes'][$i] = 'value';
        }

        $this->startTest();
    }

    public function testArrayInNotesValue()
    {
        $this->startTest();
    }

    public function testArrayInNotesKey()
    {
        $this->startTest();
    }

    public function testNotesKeyLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeKey = implode(',', range(1,100,1));
        $testData['request']['content']['notes'][$largeKey] = 'value';

        $this->startTest();
    }

    public function testNotesValueLarge()
    {
        $testData = & $this->testData[__FUNCTION__];

        $largeValue = implode(',', range(1,100,1));
        $testData['request']['content']['notes']['key'] = $largeValue;

        $this->startTest();
    }

    public function testNotesEmptyString()
    {
        $this->startTest();
    }

    public function testNotesNull()
    {
        $this->startTest();
    }

    public function testNotesAsArray()
    {
        $this->startTest();
    }

    public function testInvalidUtf8InDescription()
    {
        $this->startTest();
    }

    public function testFixAuthorizedAt()
    {
        $time = time();

        $payment = $this->fixtures->create('payment', ['status' => 'failed', 'authorized_at' => time()]);

        $this->assertEquals($time, $payment['authorized_at']);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/fix_authorized_at';
        $this->testData[__FUNCTION__]['request']['content']['payment_ids'] = [$payment->getPublicId()];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testTimeoutOldPayment()
    {
        $payment = $this->fixtures->create('payment:status_created', ['created_at' => time() - (60 * 100)]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals($content['count'], 1);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment['public_id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
    }

    public function testTimeoutOldEmandatePayments()
    {
        // Should timeout
        $payment = $this->fixtures->create(
                    'payment:status_created',
                    [
                        'created_at' => time() - (60 * 100),
                    ]);

        $token = $this->fixtures->create(
                    'token',
                    [
                        'bank' => 'HDFC',
                        'recurring' => true,
                    ]);

        $tokenId = $token['id'];

        // Should timeout
        $payment2 = $this->fixtures->create('payment:status_created',
            [
                'created_at'        => time() - (60 * 100),
                'gateway'           => PaymentModel\Gateway::NETBANKING_HDFC,
                'bank'              => IFSC::HDFC,
                'method'            => PaymentModel\Method::NETBANKING,
                'token_id'          => $tokenId,
                'recurring'         => 1,
                'recurring_type'    => PaymentModel\RecurringType::INITIAL,
            ]);

        // Should not timeout as the created_at Payment\Entity::PAYMENT_TIMEOUT_FILE_BASED_DEBIT
        $payment3 = $this->fixtures->create('payment:status_created',
            [
                'created_at'        => time() - (60 * 100),
                'gateway'           => PaymentModel\Gateway::NETBANKING_HDFC,
                'bank'              => IFSC::HDFC,
                'method'            => PaymentModel\Method::NETBANKING,
                'token_id'          => $tokenId,
                'recurring'         => 1,
                'recurring_type'    => PaymentModel\RecurringType::AUTO,
            ]);

        // Should timeout
        $payment4 = $this->fixtures->create('payment:status_created',
            [
                'created_at'        => time() - (60 * 60 * 24 * 11),
                'gateway'           => PaymentModel\Gateway::NETBANKING_HDFC,
                'bank'              => IFSC::HDFC,
                'token_id'          => $tokenId,
                'method'            => PaymentModel\Method::NETBANKING,
                'recurring'         => 1,
                'recurring_type'    => PaymentModel\RecurringType::AUTO,
            ]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals(3, $content['count']);
    }

    public function testTimeoutOldPaymentWithErrorRetention()
    {
        $payment = $this->fixtures->create('payment:status_created', [
            'created_at'          => time() - (60 * 100),
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT
        ]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals($content['count'], 1);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment['public_id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
    }

    public function testFailPaymentWithPaymentFailureMailEnabled()
    {
        Mail::fake();

        $this->failPaymentOnBankPage = true;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('payment_failure_email');

        $payment = $this->getDefaultPaymentArray();

        $data = $this->testData['testFailPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        Mail::assertSent(PaymentFailedMail::class, function ($mail)
        {
            $this->assertArrayHasKey('error_description', $mail->viewData['payment']);

            $this->assertNotEmpty($mail->viewData['payment']['error_description']);

            return true;
        });
    }

    public function testFailPaymentWithPaymentFailureMailDisabled()
    {
        Mail::fake();

        $this->failPaymentOnBankPage = true;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $data = $this->testData['testFailPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        Mail::assertNotSent(PaymentFailedMail::class);
    }

    public function testFailTimeoutOldPayments()
    {
        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - (60 * 100), 'status' => 'authorized', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $content = $this->timeoutOldPayment();

        $this->assertEquals($content['count'], 0);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment['public_id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->markTestIncomplete();

        $payment = $this->fixtures->create(
            'payment:failed');

        $this->authorizeFailedPayment($payment['public_id']);
    }

    public function testContentTypeHtmlOnPaymentCreateRoute()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        $contentType = 'text/html; charset=UTF-8';
        $this->assertContentTypeForResponse($contentType, $this->response);
    }

    public function testPaymentViaWalletS2SWoAuth()
    {
        // No Auth
        $this->startTest();
    }

    public function testWalletS2SPaymentWoFeature()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testInvalidWalletS2SPayment()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->merchant->addFeatures(['s2swallet']);

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testWalletWithInternationalContact()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment);
    }

    public function testPayumoneyPaymentViaWalletS2S()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->merchant->addFeatures(['s2swallet']);

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->privateAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('url', $content['request']);
    }

    public function testMobikwikPaymentViaWalletS2S()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $this->fixtures->merchant->addFeatures(['s2swallet']);

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->ba->privateAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('url', $content['request']);
    }

    public function testPaymentTopupViaInvalidGateway()
    {
        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 10 * 60, 'status' => 'created', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doWalletTopupViaAjaxRoute($payment->getPublicId());
        });
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        return $this->runRequestResponseFlow($testData);
    }

    public function testMaestroPaymentWithFeatureDisabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $this->fixtures->merchant->addFeatures(['disable_maestro']);

        $payment['card']['number'] = '5081597022059105';

        $data = $this->testData['testCardNetworkDisabled'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $request = [
                'method'  => 'POST',
                'url'     => '/payments',
                'content' => $payment
            ];

            $this->ba->publicLiveAuth();

            $this->makeRequestAndGetContent($request);
        });
    }

    public function testRupayPaymentWithFeatureDisabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $this->fixtures->merchant->addFeatures(['disable_rupay']);

        $payment['card']['number'] = '6073849700004947';

        $data = $this->testData['testCardNetworkDisabled'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $request = [
                'method'  => 'POST',
                'url'     => '/payments',
                'content' => $payment
            ];

            $this->ba->publicLiveAuth();

            $this->makeRequestAndGetContent($request);
        });
    }

    public function testAuthCodeUpdateFromLateAuth()
    {
        $randomAuthCode = random_integer(6);

        $this->mockServerContentFunction(
            function(& $content, $action) use ($randomAuthCode)
            {
                if ($action === 'authorize')
                {
                    throw new GatewayErrorException('GATEWAY_ERROR_UNKNOWN_ERROR');
                }

                $content['auth'] = $randomAuthCode;
            },
            'hdfc');

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment();
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertNull($payment['reference2']);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($randomAuthCode, $payment['reference2']);
    }
}
