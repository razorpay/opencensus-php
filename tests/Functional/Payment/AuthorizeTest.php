<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Support\Str;
use Mail;
use Cache;
use Redis;
use RZP\Mail\Payment\CustomerFailed;
use RZP\Models;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment as PaymentModel;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Mail\Payment\Failed as PaymentFailedMail;
use RZP\Mail\Payment\FailedToAuthorized as FailedToAuthorizedMail;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;


class AuthorizeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * The payment array
     * @var array
     */
    protected $payment;

    protected function setUp(): void
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

    public function testJsonpPayment()
    {
        Mail::fake();

        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

         $this->app->razorx->method('getTreatment')
                           ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                if ($feature === 'save_all_cards')
                                {
                                    return 'off';
                                }
                                return 'on';
                            }));

        $this->app->razorx->method('getTreatment')
             ->willReturn('On');

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.payment');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            return true;
        });
    }

    public function testAuthorisedMailWithMerchantSupportEmail()
    {
        Mail::fake();

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.payment');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertNotEquals('no-reply@razorpay.com', $mail->replyTo[0]['address']);

            return true;
        });

    }

    public function testAuthorisedMailWithoutMerchantSupportEmail()
    {
        Mail::fake();

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.payment');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@razorpay.com', $mail->replyTo[0]['address']);

            return true;
        });

    }

    public function testAuthorisedMailForCurlecCustomer()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');
        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());

        $this->fixtures->merchant->edit('10000000000000', [
            'org_id'    => $org->getId()
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.payment');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@curlec.com', $mail->replyTo[0]['address']);

            $this->assertEquals('no-reply@curlec.com', $mail->from[0]['address']);

            return true;
        });
    }

    protected function failAuthorizePayment(array $replace = array())
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content) use ($replace)
            {
                foreach ($replace as $key => $value)
                {
                    $content[$key] = $value;
                }

                $content['vpc_TxnResponseCode'] = '5';
            })->mock();

        $this->setMockServer($server);

        $this->makeRequestAndCatchException(function ()
        {
            $content = $this->doAuthPayment();
        });
    }

    public function testFailedMailWithMerchantSupportEmail()
    {
        Mail::fake();

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';

        $this->failAuthorizePayment();

        Mail::assertQueued(CustomerFailed::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.failure');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertNotEquals('no-reply@razorpay.com', $mail->replyTo[0]['address']);

            return true;
        });
    }

    public function testFailedMailCurlecCustomer()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');
        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING], $org->getId());

        $this->fixtures->merchant->edit('10000000000000', [
            'org_id'    => $org->getId()
        ]);

        $this->fixtures->merchant->addFeatures([FeatureConstants::PAYMENT_FAILURE_EMAIL], '10000000000000');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';

        $this->failAuthorizePayment();

        Mail::assertQueued(CustomerFailed::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.failure');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@curlec.com', $mail->replyTo[0]['address']);

            return true;
        });

        Mail::assertQueued(PaymentFailedMail::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.payment.merchant_failure');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@curlec.com', $mail->replyTo[0]['address']);

            return true;
        });
    }

    public function testFailedToAuthorizedCurlecOrg()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING], $org->getId());

        $this->fixtures->merchant->edit('10000000000000', [
            'org_id'    => $org->getId()
        ]);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';

        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->resetMockServer();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        Mail::assertQueued(FailedToAuthorizedMail::class, function ($mail)
        {
            $this->assertContains($mail->view, ['emails.payment.failed_to_authorized', 'emails.mjml.customer.payment']);

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@curlec.com', $mail->replyTo[0]['address']);

            return true;
        });
    }

    public function testFailedMailWithoutMerchantSupportEmail()
    {
        Mail::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';

        $this->failAuthorizePayment();

        Mail::assertQueued(CustomerFailed::class, function ($mail)
        {
            $this->assertEquals($mail->view, 'emails.mjml.customer.failure');

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('no-reply@razorpay.com', $mail->replyTo[0]['address']);

            return true;
        });

    }

    public function testMagicKeyFalseMerchantDisabled()
    {
        $this->markTestSkipped();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->edit('iin', 401200, ['flows' => ['magic' => '1']]);

        $this->startTest();
    }

    public function testMagicKeySet()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMagic();

        $this->fixtures->edit('iin', 401200, ['flows' => ['magic' => '1']]);

        $this->startTest();
    }

    public function testMagicKeyFalseDisabledIin()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMagic();

        $this->startTest();
    }

    public function testMagicKeyFalseDisabledGlobally()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMagic();

        $this->fixtures->edit('iin', 401200, ['flows' => ['magic' => '1']]);

        $store = Cache::store();

        // TODO remove this after session migration
        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        Cache::shouldReceive('store')
            ->withAnyArgs()
            ->andReturn($store);

        Cache::shouldReceive('get')
            ->once()
            ->with(ConfigKey::DISABLE_MAGIC)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->andReturnUsing(function($key)
            {
                if (Str::contains($key, 'EVENT_PAYMENT_CREATED_FIRED'))
                {
                    return true;
                }
            });

        Cache::shouldReceive('put')
            ->andReturnUsing(function($key)
            {
                return true;
            });

        $this->startTest();
    }

    public function testInvalidEmailInPayment()
    {
        $this->startTest();
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

    public function testMaestroCardWithoutCvvAndExpiry()
    {
        $payment = $this->payment;

        // Converting to a maestro card number
        $payment['card']['number'] = '5081597022059105';

        unset($payment['card']['cvv']);

        unset($payment['card']['expiry_month']);

        unset($payment['card']['expiry_year']);

        // Payment goes through fine without any exceptions
        $this->doAuthPayment($payment);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('12', $card['expiry_month']);

        $this->assertEquals('2049', $card['expiry_year']);
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
        $merchantId = "10000000000000";

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID             => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

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

        $largeValue = implode(',', range(1,200,1));
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

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testTimeoutOldPayment()
    {
        $payment = $this->fixtures->create('payment:status_created', ['created_at' => time() - (60 * 100)]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals(1, $content['count']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment['public_id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
    }

    private function getDefaultAndNewMerchantIdForPayment() : array
    {
        $newMerchant = $this->fixtures->create('merchant');

        $tempPayment = $this->fixtures->create('payment:status_created');

        return [
            "default"   => $tempPayment[PaymentModel\Entity::MERCHANT_ID],
            "new"       => $newMerchant[Merchant::ID]
        ];
    }

    private function createPaymentsForTimeoutGetParams(int $totalPaymentsCount,
                                                       int $excludeIncludePaymentsCount,
                                                       string $defaultMerchantId,
                                                       string $secondaryMerchantId,
                                                       bool $exclude): array
    {
        $paymentsTimeoutAssertions  = [];

        $excludeIncludeKey  = ($exclude === true) ? 'exclude_merchants' : 'include_merchants';
        $excludeIncludeMid  = ($exclude === true) ? $secondaryMerchantId : $defaultMerchantId;
        $paymentsToTimeout  = ($exclude === true) ? $totalPaymentsCount - $excludeIncludePaymentsCount  :
                                                    $excludeIncludePaymentsCount;

        $defaultTimeoutPaymentPresets = [
            'created_at'    => time() - (60 * 100)
        ];

        /**
         * Create N payments to fetch from
         * N => $totalPaymentsCount => Total number of payments to create
         * Total_Payments = Part_1 + Part_2
         * N = X + Y
         */
        for ($i = 0; $i < $totalPaymentsCount; $i++)
        {
            $shouldTimeout = true;

            $paymentPresetsForCurrentIteration = $defaultTimeoutPaymentPresets;

            /**
             * The first X out N 'Part_1' payments will be created with
             * the secondary MID if excluded
             * the default MID if included
             *
             * X => $excludeIncludePaymentsCount => number of payments to exclude or include i.e Part_1
             *
             * The remaining Y out N payments will be created (vice versa) i.e. with
             * the secondary MID if included
             * the default MID if excluded
             *
             * Y => $total - $excludeIncludePaymentsCount => number of payments remaining i.e Part_2
             */
            if ((($i < $excludeIncludePaymentsCount) and ($exclude === true)) or // Part_1
                (($i >= $excludeIncludePaymentsCount) and ($exclude === false))) // Part_2
            {
                $paymentPresetsForCurrentIteration = array_merge($defaultTimeoutPaymentPresets,
                                                                 ['merchant_id' => $secondaryMerchantId]);
                $shouldTimeout = false;
            }

            $tempPayment = $this->fixtures->create('payment:status_created',
                                                   $paymentPresetsForCurrentIteration);

            $paymentsTimeoutAssertions[$tempPayment['public_id']] = $shouldTimeout;
        }

        return [
            'paymentsToTimeout'         => $paymentsToTimeout,
            'paymentsTimeoutAssertions' => $paymentsTimeoutAssertions,
            'merchantFilterMergeable'   => [
                $excludeIncludeKey => [ $excludeIncludeMid ]
            ]
        ];
    }

    /**
     * This function performs the timeout action
     * Asserts if the payments expected to be timed out throw a BAD_REQUEST_PAYMENT_TIMED_OUT error
     *
     * @param int   $paymentsToTimeout
     * @param array $merchantFilterMergeable
     * @param array $paymentsTimeoutAssertions
     */
    private function testTimeoutOldPaymentWithMerchantFilter(int $paymentsToTimeout,
                                                             array $merchantFilterMergeable,
                                                             array $paymentsTimeoutAssertions)
    {
        $content = $this->timeoutOldPayment($merchantFilterMergeable);

        $this->assertEquals($paymentsToTimeout, $content['count']);

        $this->ba->privateAuth();

        $testData = $this->testData['testTimeoutOldPayment'];

        foreach ($paymentsTimeoutAssertions as $publicId => $expectedToTimeout)
        {
            if ($expectedToTimeout === false)
            {
                continue;
            }

            $testData['request']['url'] = '/payments/' . $publicId;

            $this->runRequestResponseFlow($testData);
        }
    }

    public function testTimeoutOldPaymentWithMerchantFilterExclude()
    {
        $merchantIds = $this->getDefaultAndNewMerchantIdForPayment();

        $params = $this->createPaymentsForTimeoutGetParams(6,
                                                           2,
                                                           $merchantIds['default'],
                                                           $merchantIds['new'],
                                                           true);
        extract($params);

        $this->testTimeoutOldPaymentWithMerchantFilter($paymentsToTimeout,
                                                       $merchantFilterMergeable,
                                                       $paymentsTimeoutAssertions);
    }

    public function testTimeoutOldPaymentWithMerchantFilterInclude()
    {
        $merchantIds = $this->getDefaultAndNewMerchantIdForPayment();

        $params = $this->createPaymentsForTimeoutGetParams(6,
                                                           4,
                                                           $merchantIds['default'],
                                                           $merchantIds['new'],
                                                           false);
        extract($params);

        $this->testTimeoutOldPaymentWithMerchantFilter($paymentsToTimeout,
                                                       $merchantFilterMergeable,
                                                       $paymentsTimeoutAssertions);
    }

    public function testTimeoutAuthenticatedPayment()
    {
        $payment = $this->fixtures->create('payment:status_authenticated', ['authenticated_at' => time() - (60 * 100)]);

        $content = $this->timeoutAuthenticatedPayment();

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

        // TODO: Figure out how to write the test cases here!

        // Should timeout
        $payment2 = $this->fixtures->create('payment:status_created',
            [
                'created_at'        => time() - (60 * 100),
                'gateway'           => PaymentModel\Gateway::NETBANKING_HDFC,
                'bank'              => IFSC::HDFC,
                'method'            => PaymentModel\Method::EMANDATE,
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
                'method'            => PaymentModel\Method::EMANDATE,
                'token_id'          => $tokenId,
                'recurring'         => 1,
                'recurring_type'    => PaymentModel\RecurringType::AUTO,
            ]);

        // Should timeout
        $payment4 = $this->fixtures->create('payment:status_created',
            [
                'created_at'        => time() - (PaymentModel\Entity::PAYMENT_TIMEOUT_FILE_BASED_DEBIT + 24 * 60 * 6),
                'gateway'           => PaymentModel\Gateway::NETBANKING_HDFC,
                'bank'              => IFSC::HDFC,
                'token_id'          => $tokenId,
                'method'            => PaymentModel\Method::EMANDATE,
                'recurring'         => 1,
                'recurring_type'    => PaymentModel\RecurringType::AUTO,
            ]);

        $content = $this->timeoutOldEmandatePayment(PaymentModel\RecurringType::AUTO);

        $this->assertEquals(1, $content['count']);

        $content = $this->timeoutOldEmandatePayment(PaymentModel\RecurringType::INITIAL);

        $this->assertEquals(1, $content['count']);
    }

    public function testTimeoutOldGooglePayPayment()
    {
        $payment = $this->fixtures->create('payment:status_created',
            [
                'created_at' => time() - (60 * 100),
                'authentication_gateway' => 'google_pay',
                'method' => 'unselected'
            ]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals($content['count'], 1);

        $testData = $this->testData['testTimeoutOldPayment'];

        $testData['request']['url'] = '/payments/' . $payment['public_id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
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

        Mail::assertQueued(PaymentFailedMail::class, function ($mail)
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

    public function testTimeoutOldPaymentsCustomTimeout()
    {
        /*
         *  Test setup
         *  Default timeout for all merchants defined is 9 minutes as defined in  constant PAYMENT_TIMEOUT_DEFAULT_OLD
         * there are exceptions to this as defined in Payment\Entity\getTimeoutWindow, but for the purpose of this test
         * they dont matter.
         *
         * Here we create a merchant whose custom timeout is 27 minutes.
         *
         * We create payment1 18 minutes ago and it should not be failed
         * We create payment2 36 minutes ago and it should failed
         */

        $this->ba->adminAuth();

        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();

        $payment1 = $this->fixtures->create('payment:status_created', ['created_at' => time() - (18 * 60)]);
        $payment2 = $this->fixtures->create('payment:status_created', ['created_at' => time() - (36 * 60)]);

        $content = $this->timeoutOldPayment();

        $this->assertEquals(1, $content['count']);

        $this->assertEquals(
            Models\Payment\Status::CREATED,
            $this->getEntityById('payment', $payment1->getId(), true)['status']);

        $this->assertEquals(
            Models\Payment\Status::FAILED,
            $this->getEntityById('payment', $payment2->getId(), true)['status']);

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

    public function testIntentPaymentWithVpa()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'dontencrypt@icici';

        unset($payment['description']);

        $payment['_']['flow'] = 'intent';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testIntentPayment()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->getDefaultUpiIntentPaymentArray();

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->disableUpiIntent('10000000000000');

        unset($payment['description']);

        $payment['_']['flow'] = 'intent';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testCollectPayment()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->disableUpiCollect('10000000000000');

        unset($payment['description']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testOmnichannelPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('google_pay_omnichannel');

        $payment = $this->getDefaultUpiIntentPaymentArray();

        $this->fixtures->merchant->disableUpiIntent('10000000000000');

        $payment['upi_provider'] = 'google_pay';

        $payment['_']['flow'] = 'intent';

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->disableUpiCollect('10000000000000');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
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

    public function testAtmPinAuthenticationPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'CBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';
        $payment['bank'] = 'CBIN';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        // Adding otpelf features to ensure that it doesn't break the integration
        $this->fixtures->merchant->addFeatures(['otp_auth_default']);
        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('pin', $payment['auth_type']);
        self::assertEquals('card_fss', $payment['gateway']);
        self::assertEquals('SharedFssTrmnl', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPaymentWoFeatureFallback()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertNull($payment['auth_type']);
        $this->assertEquals('hdfc', $payment['gateway']);
        $this->assertEquals('1n25f6uN5S1Z5a', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPaymentWithoutTerminal()
    {
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertNull($payment['auth_type']);
        $this->assertEquals('hdfc', $payment['gateway']);
        $this->assertEquals('1n25f6uN5S1Z5a', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPaymentWithCardNotSupported()
    {
        config(['app.data_store.mock' => false]);

        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function () use ($conn)
             {
                return $conn;
             });

        Redis::shouldReceive('zrevrange')
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'card_fss'    => '60',
                    'hdfc'        => '50',
                ];
            });

        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertNull($payment['auth_type']);
        $this->assertEquals('hdfc', $payment['gateway']);
        $this->assertEquals('1n25f6uN5S1Z5a', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPaymentWith3dsAtPriority()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'CBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];
        $payment['bank']           = 'CBIN';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('pin', $payment['auth_type']);
        $this->assertEquals('card_fss', $payment['gateway']);
        $this->assertEquals('SharedFssTrmnl', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPayment()
    {
        config(['app.data_store.mock' => false]);
        // Mocking mutex since we are mocking redis and partial mock
        // is difficult to mock (read as doesn't work) in laravel
        config(['services.mutex.mock' => true]);

        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function () use ($conn)
             {
                return $conn;
             });

        Redis::shouldReceive('zrevrange')
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'card_fss'    => '60',
                    'hdfc'        => '50',
                ];
            });

        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'CBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = ['pin'];
        $payment['bank']           = 'CBIN';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('pin', $payment['auth_type']);
        $this->assertEquals('card_fss', $payment['gateway']);
        $this->assertEquals('SharedFssTrmnl', $payment['terminal_id']);
    }

    public function testPinPreferredAuthPaymentWithString()
    {
        config(['app.data_store.mock' => false]);
        // Mocking mutex since we are mocking redis and partial mock
        // is difficult to mock (read as doesn't work) in laravel
        config(['services.mutex.mock' => true]);

        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function () use ($conn)
             {
                return $conn;
             });

        Redis::shouldReceive('zrevrange')
            ->with('gateway_priority:card', 0, -1, 'WITHSCORES')
            ->andReturnUsing(function ()
            {
                return [
                    'card_fss'    => '60',
                    'hdfc'        => '50',
                ];
            });

        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'merchant_id' => '10000000000000',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'CBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['preferred_auth'] = 'pin';
        $payment['bank']           = 'CBIN';

        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('pin', $payment['auth_type']);
        $this->assertEquals('card_fss', $payment['gateway']);
        $this->assertEquals('SharedFssTrmnl', $payment['terminal_id']);
    }

    public function testAtmPinAuthenticationWithNoTerminal()
    {
        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

         $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testAtmPinAuthenticationNotSupported()
    {
        $this->fixtures->merchant->addFeatures(['atm_pin_auth']);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';
        $payment['auth_type'] = 'pin';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function test3dsPaymentWithPinTerminal()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'id' => 'SharedFssTrmnl',
            'gateway_acquirer' => 'fss',
            'type' => [
                'pin' => '1',
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4143667057540458';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });
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
        $this->markTestSkipped();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->merchant->addFeatures(['s2swallet']);

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->privateAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('url', $content['request']);
    }

    public function testIntentPaymentViaUpiS2S()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $this->fixtures->merchant->addFeatures(['s2supi']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->ba->privateAuth();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
        }, 'upi_icici');

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $this->assertArrayHasKey('link', $content);
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

    public function testInvalidUpiGatewayCallback()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableUpi();
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);
        $paymentId = $response['payment_id'];

        $this->assertEquals('async', $response['type']);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', ['gateway_merchant_id' => 'HDFC000000000']);

        $server = $this->mockServerContentFunction(function (&$content, $action = '')
        {
            if ($action === 'callback')
            {
                $content[1] = 'randomid';
            }
        }, 'upi_mindgate');

        $content = $server->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertTrue($response['success']);

        $newPayment = $this->getLastEntity('payment', true);
        $this->assertNotEquals($newPayment['id'], $payment['id']);

        $this->assertEquals('upi_mindgate', $newPayment['gateway']);
        $this->assertNotEquals($newPayment['id'], $payment['id']);
        $this->assertNull($newPayment['captured_at']);

        $newUpiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('randomid', $newUpiEntity['merchant_reference']);

        $this->assertNotEquals($newUpiEntity['payment_id'], $newUpiEntity['merchant_reference']);
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

        $this->fixtures->merchant->disableCardNetworks('10000000000000', ['maes']);
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

        $this->fixtures->merchant->disableCardNetworks('10000000000000', ['rupay']);
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

    public function testAuthorizeWithCardnetworkDisabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $cards = [
            ['6073849700004947', '880'], // Rupay
            ['341111111111111', '8808'], // Amex
            ['30569309025904', '880'],   // Diners
            ['2030400000121212', '880'], // Bajaj Finserv
        ];

        $this->fixtures->merchant->disableCardNetworks('10000000000000', ['amex', 'rupay', 'dicl', 'bajaj']);

        $data = $this->testData['testCardNetworkDisabled'];

        foreach($cards as $card)
        {
            $payment['card']['number'] = $card[0];
            $payment['card']['cvv']    = $card[1];

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
    }

    public function testAuthorizeWithCardnetworkEnabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $cards = [
            ['6073849700004947', '880'], // Rupay
            ['30569309025904', '880'], // Diners
        ];

        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['rupay', 'dicl']);

        foreach($cards as $card)
        {
            $payment['card']['number'] = $card[0];
            $payment['card']['cvv']    = $card[1];

            $request = [
                'method'  => 'POST',
                'url'     => '/payments',
                'content' => $payment
            ];

            $this->ba->publicLiveAuth();

            $response = $this->makeRequestAndGetContent($request);

            $this->assertArrayHasKey('razorpay_payment_id', $response);
        }
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

    public function testAuthCodeUpdateFromLateAuthSilentRefundFeatureFlag()
    {
        Mail::fake();

        $randomAuthCode = random_integer(6);

        $this->fixtures->merchant->addFeatures(featureConstants::SILENT_REFUND_LATE_AUTH);

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

        Mail::assertNotQueued(AuthorizedMail::class);
    }

    public function testPaymentWithSkipAuthWithMotoFeatureDisabled()
    {
        $payment = $this->payment;

        $payment['auth_type'] = 'skip';

        $payment['card']['number'] = '5257834104683413';

        unset($payment['card']['cvv']);

        $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testPaymentWithSkipAuthWithNotSupportedCard()
    {
        $payment = $this->payment;

        $payment['auth_type'] = 'skip';

        $payment['card']['number'] = '5893163050216758';

        unset($payment['card']['cvv']);

        $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testAuthorizeWithSubTypeDisabled()
    {

        $this->fixtures->merchant->edit('10000000000000', [
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'sub_type'=> 'business',
        ]);
        $this->ba->publicLiveAuth();
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->fixtures->merchant->disableCardSubType('10000000000000', 'business');

        $data = $this->testData['testAuthorizeWithSubTypeDisabled'];

        $payment['card']['number'] = '555555555555558';
        $payment['card']['cvv']    = '880';

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

    public function testLBPCurrency()
    {
        $this->startTest();
    }

}
