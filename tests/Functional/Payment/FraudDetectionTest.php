<?php

namespace RZP\Tests\Functional\Payment;
use Mail;
use Mockery;

use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Models\Feature;
use RZP\Models\Risk;
use RZP\Constants\Shield;
use RZP\Models\Payment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Mail\Payment\Fraud\DomainMismatch as DomainMismatchMail;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use function League\Uri\newInstance;

class FraudDetectionTest extends TestCase
{
    use PaymentTrait;
    use FreshdeskTrait;
    use EventsTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FraudDetectionTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $this->mockShieldEnqueueSQS([]);
    }

    public function testBlockedBin()
    {
        $this->fixtures->create(
            'iin',
            [
                'iin'     => 521729,
                'network' => 'MasterCard',
                'type'    => 'debit',
                'country' => null,
                'enabled' => 0
            ]);

        $payment                   = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5217294025032720';

        $data = $this->testData[__FUNCTION__];
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals('PAYMENT_FAILED_DUE_TO_BLOCKED_CARD', $riskEntity['reason']);

        // We are not storing riskScore if it is not tagged by maxmind source
        $this->assertEquals(-1, $riskEntity['risk_score']);

        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals('payment_analytics', $paymentAnalytic['entity']);
    }

    public function testFraudDetected()
    {
        $this->markTestSkipped('Maxmind code removed');

        $this->mockMaxmind();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            'PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND', $riskEntity['reason']);

        $this->assertNotNull($riskEntity['risk_score']);
    }

    public function testSkipMaxmindCheckForAmexPayments()
    {
        $this->mockRazorx();
        $this->mockShield();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create(
            'iin',
            [
                'iin'     => 514906,
                'network' => "American Express",
                'type'    => 'debit',
            ]);

        $payment['card']['number'] = '5149066434045615';
        $payment['card']['cvv']    = '1234';

        $response = $this->doAuthPayment($payment);

        unset($response['org_logo']);
        unset($response['org_name']);
        unset($response['checkout_logo']);
        unset($response['custom_branding']);

        $this->assertArrayKeysExist($response, ['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $paymentAnalytics = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals($paymentAnalytics['risk_score'], 35);

        $this->assertEquals($paymentAnalytics['risk_engine'], 'shield');
    }

    public function testFraudNotDetected()
    {
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5105105105105100';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testFraudNotDetectedForSecondRecurring()
    {
        $this->mockMaxmind();
        $this->mockCardVault();

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->ba->publicAuth();

        $payment                   = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '5105105105105100';

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        unset($payment['card']);
        unset($payment['bank']);

        $payment['token'] = $paymentEntity['token_id'];

        $this->ba->privateAuth();

        $this->doS2sRecurringPayment($payment);

        $this->ba->publicAuth();
    }

    public function testFraudDetectedWithInvalidEmailTld()
    {
        $this->markTestSkipped('Maxmind code removed');

        $this->fixtures->merchant->enableInternational();

        $payment                   = $this->getDefaultPaymentArray();
        $payment['email']          = 'test@razorpay.xtm';
        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testFraudDetectedByShield()
    {
        $this->mockShield();

        $this->mockRazorx();

        $payment = $this->getDefaultPaymentArray();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData['testFraudDetected'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD,
            $riskEntity['reason']
        );
    }

    public function testShopifyFraudDetectedByShield()
    {
        $this->mockRazorx();

        $this->createMerchantDetails();

        $payment = $this->getDefaultPaymentArray();

        $payment['_'] = array(
            'integration' => 'shopify',
            'integration_version' => 'shopify-payment-app',
        );

        $payment['notes'] = array(
            'merchant_order_id' => 'random order id',
            'cancelUrl'    => 'https://xyz.in/123/checkouts/abc',
            'domain'        => 'xyz77.myshopify.com',
            'referer_url'    => "https://xyz.in/123/checkouts/abcd"
        );

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData['testFraudDetected'];

        $expectedShieldPayload  = [
            'integration'          => "shopify",
            'integration_version'  => "shopify-payment-app",
            'rzp_checkout_library' => "direct",
            'order_domain'         => "xyz77.myshopify.com",
            'order_cancel_url'     => "https://xyz.in/123/checkouts/abc",
            'order_referer_url'    => "https://xyz.in/123/checkouts/abcd"
        ];
        $expectedShieldResponse = [
            "action"                => "block",
            "max_rule_weight"       => 0,
            "maxmind_score"         => null,
            "triggered_rule_weight" => 0,
            "triggered_rules"       => [
                "block"     => [
                    [
                        "id"               => 370,
                        "rule_code"        => "RULE_CODE_DEFAULT",
                        "rule_description" => "Test 1d rule",
                        "rule_id"          => "rule_Jhhek9mkIkpuTx",
                    ],
                ],
                "review"    => [],
                "whitelist" => []
            ]];

        $this->mockShieldClientRequest($expectedShieldPayload, $expectedShieldResponse);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD,
            $riskEntity['reason']
        );
    }

    protected function mockShieldClientRequest($expectedPayload = [], $expectedResponse = [])
    {
        $this->shieldMock = Mockery::mock('RZP\Services\ShieldClient', $this->app)->makePartial();

        $this->shieldMock->shouldAllowMockingProtectedMethods();

        $this->app['shield'] = $this->shieldMock;

        $this->shieldMock->shouldReceive('evaluateRules')->times(1)->andReturnUsing(function($payload) use ($expectedPayload, $expectedResponse) {
            $this->assertArraySelectiveEquals($expectedPayload, $payload['input']);

            return $expectedResponse;
        });
    }

    public function testShopifyFraudNotDetectedByShield()
    {
        $this->mockRazorx();

        $this->createMerchantDetails();

        $payment = $this->getDefaultPaymentArray();

        $payment['_'] = array(
            'integration' => 'shopify',
            'integration_version' => 'shopify-payment-app',
        );

        $payment['notes'] = array(
            'merchant_order_id' => 'random order id',
            'cancelUrl'    => 'https://xyz.in/123/checkouts/abc',
            'domain'        => 'xyz77.myshopify.com',
            'referer_url'    => "https://xyz.in/123/checkouts/abcd"
        );

        $payment['card']['number'] = '5105105105105100';

        $expectedShieldPayload  = [
            'integration'          => "shopify",
            'integration_version'  => "shopify-payment-app",
            'rzp_checkout_library' => "direct",
            'order_domain'         => "xyz77.myshopify.com",
            'order_cancel_url'     => "https://xyz.in/123/checkouts/abc",
            'order_referer_url'    => "https://xyz.in/123/checkouts/abcd"
        ];
        $expectedShieldResponse = [
            "action"                => "allow",
            "max_rule_weight"       => 0,
            "maxmind_score"         => null,
            "triggered_rule_weight" => 0,
            "triggered_rules"       => [
                "block"     => [],
                "review"    => [],
                "whitelist" => []
            ]];

        $this->mockShieldClientRequest($expectedShieldPayload, $expectedShieldResponse);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function test3dsFlagSendToShield()
    {
        $this->mockRazorx();

        $this->createMerchantDetails();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->edit('merchant', '10000000000000', [
            'signup_via_email' => 0,
        ]);

        $this->fixtures->create('config', [
                'type' => 'risk',
                'is_default' => true,
                'is_deleted' => false,
                'merchant_id' => '10000000000000',
                'config'     => '{
                    "secure_3d_international": "v1"
                }']);

        $payment['card']['number'] = '5105105105105100';

        $expectedShieldPayload  = [
            "secure_3d_international"   => "v1",
        ];
        $expectedShieldResponse = [
            "action"                => "allow",
            "max_rule_weight"       => 0,
            "maxmind_score"         => null,
            "triggered_rule_weight" => 0,
            "triggered_rules"       => [
                "block"     => [],
                "review"    => [],
                "whitelist" => []
            ]];

        $this->mockShieldClientRequest($expectedShieldPayload, $expectedShieldResponse);

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    private function createMerchantDetails($unregisteredBusiness = false)
    {
        $merchantDetailData = [
            'merchant_id'    => '10000000000000',
            'contact_mobile' => '9999999999',
        ];

        if ($unregisteredBusiness === true)
        {
            $merchantDetailData['business_type'] = "2";
        }

        $this->fixtures->create('merchant_detail', $merchantDetailData);
    }

    public function runFraudDetectedByShieldWebsiteMismatch($mobileSignUpTest = false, $unregisteredBusiness = false, $ruleID = 'rule_F1fgTZ9p7tj2es', $ruleCode= 'DEFAULT_RULE')
    {
        if (($mobileSignUpTest === false) and ($unregisteredBusiness === false))
        {
            $this->mockRaven();
        }

        $this->mockRazorx();

        $this->setUpFreshdeskClientMock();

        $merchant_phone = '+919999999999';
        $merchant_id = '10000000000000';

        $this->createMerchantDetails($unregisteredBusiness);

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);
        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient')->makePartial();

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) use ($ruleID, $ruleCode) {
                return [
                        "action"                => 'block',
                        "max_rule_weight"       => 0,
                        "maxmind_score"         => null,
                        "triggered_rule_weight" => 0,
                        "triggered_rules"       => [
                            "block"   => [
                                [
                                    "rule_id"     => $ruleID,
                                    "rule_code"   => $ruleCode
                                ],
                            ],
                        ]
                    ];
                });

        $this->app->instance('shield', $shieldClient);

        $testPayment = $this->getDefaultPaymentArray();

        $testDomain = 'anotherurl.com';
        $testPayment['referer'] = $testDomain . '/dummy/path';
        $testPayment['card']['number'] = '4012010000000007';

        $data = $this->testData['testFraudDetectedByShieldWebsiteMismatch'];

        $expectedContent = [
            'group_id'        => 82000147768,
            'tags'            => ['website_mismatch'],
            'priority'        => 1,
            'phone'           => '+919999999999',
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Website Mismatch',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                'cf_merchant_id'                => '10000000000000',
                'cf_merchant_activation_status' => 'undefined',
            ],
        ];

        if ($mobileSignUpTest === true)
        {
            $this->fixtures->edit('merchant', '10000000000000', [
                'signup_via_email' => 0,
            ]);

            $this->fixtures->create('merchant_email', [
                'type'  => 'chargeback',
                'email' => null,
            ]);


            $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                        $expectedContent,
                                                        [
                                                            'id' => '1234',
                                                        ]);
        }

        else if ($unregisteredBusiness === true)
        {
            $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                    [],
                    [], 0);
        }

        else
        {
            $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                        [],
                                                        [
                                                            'id' => '1234',
                                                        ]);
        }


        $response = $this->runRequestResponseFlow($data, function() use ($testPayment)
        {
            $this->doAuthPayment($testPayment);
        });

        $this->assertEquals("This business is not allowed to accept payments on this website. We suggest not going ahead with the payment.", $response['error']['description']);

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD,
            $riskEntity['reason']
        );

        if ($unregisteredBusiness === true) {
            $this->assertNoRavenRequest();
        }

        else if ($mobileSignUpTest === false)
        {
            $this->assertRavenRequest(function($input) use ($merchant_id, $merchant_phone, $testDomain)
            {
                $this->assertArraySubset([
                                             'receiver'  => $merchant_phone,
                                             'source'    => 'api.test.payment',
                                             'template'  => 'sms.risk.url_mismatch_email_signup',
                                             'params'    => [
                                                 'merchant_id'     => $merchant_id,
                                                 'referer_domain' => $testDomain
                                             ],
                                             'stork' => [
                                                 'context' => [
                                                     'org_id' => '100000razorpay',
                                                 ],
                                             ],
                                         ], $input);
            });
        }

        // Second round of payment, this time mail / sms should not be sent.
        $this->resetRavenMock();

        $response = $this->runRequestResponseFlow($data, function() use ($testPayment)
        {
            $this->doAuthPayment($testPayment);
        });

        $this->assertEquals("This business is not allowed to accept payments on this website. We suggest not going ahead with the payment.", $response['error']['description']);

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD,
            $riskEntity['reason']
        );
        if (($mobileSignUpTest === false) or ($unregisteredBusiness === true))
        {
            $this->assertNoRavenRequest();
        }
    }

    public function testFraudDetectedByShieldWebsiteMismatch()
    {
        $this->runFraudDetectedByShieldWebsiteMismatch();
    }

    public function testFraudDetectedByShieldWebsiteMismatchWithNewRuleId()
    {
        $this->runFraudDetectedByShieldWebsiteMismatch(false, false, 'rule_J2yeMfz5AxeSN6');
    }

    public function testFraudDetectedByShieldWebsiteMismatchMobileSignup()
    {
        $this->runFraudDetectedByShieldWebsiteMismatch(true, false,  'rule_random', "DOMAIN_MISMATCH_BLOCK_NOTIFY_MERCHANT");
    }

    # Test case for validating that no alerts are being sent in case of Unregistered Business Type for the merchant
    public function testFraudDetectedByShieldWebsiteMismatchUnregisteredBusiness()
    {
        $this->runFraudDetectedByShieldWebsiteMismatch(false, true);
    }

    public function testFraudNotDetectedByShield()
    {
        $this->mockShield();

        $this->mockRazorx();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5105105105105100';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testAllowByShieldWithHighRiskScore()
    {
        $this->mockShield();

        $this->mockRazorx();

        $this->fixtures->create(
            'iin',
            [
                'iin'     => 514906,
                'network' => 'Visa',
                'type'    => 'debit',
                'country' => 'US',
                'enabled' => '1'
            ]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5149067611060906';


        $data = $this->testData['testFraudDetected'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD,
            $riskEntity['reason']
        );
    }

    /*
     * In this test case, we simulate a failure to detect fraud on Shield(validateFraudDetectionV2).
     * In this case, we still want a fraud check to happen via Maxmind(validateFraudDetection)
     */
    public function testFraudDetectionFailedByShieldDetectedByMaxMind()
    {
        $this->markTestSkipped('Maxmind code removed');

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload){
                throw new IntegrationException(ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED,
                    ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED);
            });

        $this->app['shield'] = $shieldClient;

        $this->mockMaxmind();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create(
            'iin',
            [
                'iin'     => 514906,
                'network' => 'Visa',
                'type'    => 'debit',
                'country' => 'US',
                'enabled' => '1'
            ]);

        $payment['card']['number'] = '5149067611060906';

        $data = $this->testData['testFraudDetectionFailedByShieldDetectedByMaxMind'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);

        $this->assertEquals(
            Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND,
            $riskEntity['reason']
        );
    }

    public function testFraudDetectionFailedByShieldSkippedByMaxmind()
    {
        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload){
                throw new IntegrationException(ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED,
                    ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED);
            });

        $this->app['shield'] = $shieldClient;

        $this->mockMaxmind();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('payment_id', $response);
    }

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
             ->method('getTreatment')
             ->will($this->returnCallback(function ($mid, $feature, $mode)
                    {
                        if ($feature === 'shield_risk_evaluation')
                        {
                            return 'shield_on';
                        }

                        if ($feature === 'save_txn_app_urls')
                        {
                            return 'on';
                        }

                        return 'shield_off';
                    }));
    }

    protected function runPlatformTest($platform, $value)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '341111111111111';

        $payment['card']['cvv'] = '1234';

        // Bootstrap to call shield code
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        if ($platform !== null)
        {
            $payment['_'] = ['platform' => $platform];
        }

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) use ($value) {
                return [
                    "action" => (($payload['input']['platform'] === $value) ? 'allow': 'block'),
                    "max_rule_weight" => 0,
                    "maxmind_score" => null,
                    "triggered_rule_weight" => 0,
                ];
            });

        $this->app->instance('shield', $shieldClient);

        $this->doAuthPayment($payment);

    }

    /*
     * In these 6 tests we are trying to check if the parameter platform is getting passed
     * to the shield service correctly or not.
     *
     * For that we have created a payment and mocked razorx and shield client.
     *
     * Then for each value of platform we would block the payment if the platform value
     * in the received payload doesn't match the actual platform value which we passed.
     *
     * This ensures the correct testing of this parameter in the actual payment flow
     * upto and after the shield client has been used.
     *
     * The only reason for separate tests is that binding mock services to app can occur only
     * once in a test, and we had to create different mock services for different parameters.
     */

    public function testFraudDetectionWithPlatformNull()
    {
        $this->runPlatformTest(null, null);
    }

    public function testFraudDetectionWithPlatformBrowser()
    {
        $this->runPlatformTest('browser', 'browser');
    }

    public function testFraudDetectionWithPlatformMobileSdk()
    {
        $this->runPlatformTest('mobile_sdk', 'mobile_sdk');
    }

    public function testFraudDetectionWithPlatformCordova()
    {
        $this->runPlatformTest('cordova', 'cordova');
    }

    public function testFraudDetectionWithPlatformServer()
    {
        $this->runPlatformTest('server', 'server');
    }

    public function testFraudDetectionWithPlatformRandom()
    {
        $this->runPlatformTest('xyz', 'others');
    }

    protected function runPayloadTest($payment, $comparatorFunc)
    {
        $this->mockRazorx();

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing($comparatorFunc);

        $this->app->instance('shield', $shieldClient);

        $this->doAuthPayment($payment);

    }

    public function testFraudDetectionForUpiFlowIntent()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';

        $payment['_'] = ['flow' => 'intent'];

        $comparatorFunc = function ($payload) {
            return [
                "action" => (($payload['input']['upi_type'] === 'intent') ? 'allow': 'block'),
                "max_rule_weight" => 0,
                "maxmind_score" => null,
                "triggered_rule_weight" => 0,
            ];
        };

        $this->mockShieldEnqueueSQS([
                                        "amount"      => 50000,
                                        "base_amount" => 50000,
                                        "method"      => "upi",
                                        "vpa"         => "success@razorpay",
                                        "contact"     => "+919918899029",
                                        "email"       => "a@b.com"
                                    ]);

        $this->runPayloadTest($payment, $comparatorFunc);
    }

    protected function mockShieldEnqueueSQS($paymentData)
    {
        $expectedPayload = $this->getShieldSQSExpectedPayload($paymentData);
        $expectedResponse = [];

        $shieldServiceMock = Mockery::mock('RZP\Services\Shield', $this->app)->makePartial();

        $this->app['shield.mock_service'] = $shieldServiceMock;

        $shieldServiceMock->shouldReceive('enqueueShieldEvent')->andReturnUsing(function($payload) use ($expectedPayload, $expectedResponse) {
            $this->assertArraySelectiveEquals($expectedPayload, $payload);

            return $expectedResponse;
        });
    }


    protected function getShieldSQSExpectedPayload($payment = [])
    {
        return [
            "event_type"=> "payment-events",
            "event_version" => "v2",
            "event_group"   => "authorization",
            "event"         => "payment.authorization.processed",
            "properties"    => [
                "payment_analytics" => [
                    "ip"=> "10.0.123.123",
                ],
                "merchant" => [
                    "id" => "10000000000000",
                    "name" => "Test Merchant",
                    "mcc" => "5399",
                    "category" =>null
                ],
                "payment" => $payment,
            ],
        ];
    }

    public function testFraudDetectionForUpiFlowCollect()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiPaymentArray();

        $comparatorFunc = function ($payload) {
            return [
                "action" => (($payload['input']['upi_type'] === 'collect') ? 'allow': 'block'),
                "max_rule_weight" => 0,
                "maxmind_score" => null,
                "triggered_rule_weight" => 0,
            ];
        };

        $this->mockShieldEnqueueSQS([
                                        "amount"      => 50000,
                                        "base_amount" => 50000,
                                        "method"      => "upi",
                                        "vpa"         => "vishnu@icici",
                                        "contact"     => "+919918899029",
                                        "email"       => "a@b.com"
                                    ]);

        $this->runPayloadTest($payment, $comparatorFunc);
    }

    /*
    * In this test case, we simulate a failure to detect fraud on Shield(validateFraudDetectionV2).
    * In this case, if it's an international card then throw an error and fail the payment
    */
    public function testInternationalCardFraudFailedByShield()
    {
        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload){
                throw new IntegrationException(ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED,
                    ErrorCode::SERVER_ERROR_SHIELD_FRAUD_DETECTION_FAILED);
            });

        $this->app['shield'] = $shieldClient;

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->mockRazorx();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create(
            'iin',
            [
                'iin'     => 514906,
                'network' => 'Visa',
                'type'    => 'debit',
                'country' => 'US',
                'enabled' => '1'
            ]);

        $payment['card']['number'] = '5149067611060906';

        $data = $this->testData['testInternationalCardFraudFailedByShield'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(Payment\Status::FAILED, $payment['status']);
        $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $payment['error_code']);
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD, $payment['internal_error_code']);
    }

    protected function runFraudDetectionTestWithPackageName($value)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '341111111111111';

        $payment['card']['cvv'] = '1234';

        $payment['_'] = ['package_name' => $value];

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) use ($value) {
                $action = 'block';

                if ((empty($value) === true) and
                    (isset($payload['input']['package_name']) === false))
                {
                    $action = 'allow';
                }

                if ((empty($value) === false) and
                    (isset($payload['input']['package_name']) === true) and
                    ($payload['input']['package_name'] === $value))
                {
                    $action = 'allow';
                }

                return [
                    "action" => $action,
                    "max_rule_weight" => 0,
                    "maxmind_score" => null,
                    "triggered_rule_weight" => 0,
                ];
            });

        $this->app->instance('shield', $shieldClient);

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testFraudDetectionWithNonEmptyPackageName()
    {
        $this->runFraudDetectionTestWithPackageName('com.licious');
    }

    public function testFraudDetectionWithNullPackageName()
    {
        $this->runFraudDetectionTestWithPackageName(null);
    }

    public function testFraudDetectionWithEmptyPackageName()
    {
        $this->runFraudDetectionTestWithPackageName('');
    }

    protected function runEarlySettlementFlagPassedToShieldTest($featureEnabled)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '341111111111111';

        $payment['card']['cvv'] = '1234';

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) use ($featureEnabled) {
                $action = 'allow';

                if (isset($payload['input']['early_settlement_enabled']) === false)
                {
                    $action = 'block';
                }
                else if (is_bool($payload['input']['early_settlement_enabled']) === false)
                {
                    $action = 'block';
                }
                else if ($payload['input']['early_settlement_enabled'] !== $featureEnabled)
                {
                    $action = 'block';
                }

                return [
                    "action" => $action,
                    "max_rule_weight" => 0,
                    "maxmind_score" => null,
                    "triggered_rule_weight" => 0,
                ];
            });

        $this->app->instance('shield', $shieldClient);

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testEarlySettlementFlagPassedToShieldWithEsOnDemand()
    {
        // Test: ES flag enabled by enabling feature flag ES_ON_DEMAND

        $this->fixtures->merchant->addFeatures([Feature\Constants::ES_ON_DEMAND]);

        $this->runEarlySettlementFlagPassedToShieldTest(true);

        $this->fixtures->merchant->removeFeatures([Feature\Constants::ES_ON_DEMAND]);
    }

    public function testEarlySettlementFlagPassedToShieldWithEsOnDemandRestricted()
    {
        // Test: ES flag enabled by enabling feature flag ES_ON_DEMAND_RESTRICTED

        $this->fixtures->merchant->addFeatures([Feature\Constants::ES_ON_DEMAND_RESTRICTED]);

        $this->runEarlySettlementFlagPassedToShieldTest(true);

        $this->fixtures->merchant->removeFeatures([Feature\Constants::ES_ON_DEMAND_RESTRICTED]);
    }

    public function testEarlySettlementFlagPassedToShieldWithEsAutomatic()
    {
        // Test: ES flag enabled by enabling feature flag ES_AUTOMATIC

        $this->fixtures->merchant->addFeatures([Feature\Constants::ES_AUTOMATIC]);

        $this->runEarlySettlementFlagPassedToShieldTest(true);

        $this->fixtures->merchant->removeFeatures([Feature\Constants::ES_AUTOMATIC]);
    }

    public function testEarlySettlementFlagPassedToShieldWithEsAutomaticThreePm()
    {
        // Test: ES flag enabled by enabling feature flag ES_AUTOMATIC_THREE_PM

        $this->fixtures->merchant->addFeatures([Feature\Constants::ES_AUTOMATIC_THREE_PM]);

        $this->runEarlySettlementFlagPassedToShieldTest(true);

        $this->fixtures->merchant->removeFeatures([Feature\Constants::ES_AUTOMATIC_THREE_PM]);
    }

    public function testEarlySettlementFlagPassedToShieldWithNoEsFlags()
    {
        // Test: ES flag disabled

        $this->runEarlySettlementFlagPassedToShieldTest(false);
    }

    public function testFraudDetectedNotificationToOps()
    {
        $this->mockRazorx();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
        ]);

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient')->makePartial();

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) {
                return [
                        "action"                => 'block',
                        "max_rule_weight"       => 0,
                        "maxmind_score"         => null,
                        "triggered_rule_weight" => 0,
                        "triggered_rules"       => [
                            "block"   => [
                                [
                                    "id"               => '123',
                                    "rule_id"          => 'rule_1234',
                                    "rule_code"        => 'power_bank_rules_1',
                                    "rule_description" => 'test_description',
                                ],
                            ],
                            "review"   => [
                                [
                                    "id"               => '223',
                                    "rule_id"          => 'rule_2234',
                                    "rule_code"        => 'power_Bank_Rules',
                                    "rule_description" => 'test_description',
                                ],
                            ],
                            "whitelist"   => [
                                [
                                    "id"               => '323',
                                    "rule_id"          => 'rule_3234',
                                    "rule_code"        => 'power_Bank_Rules',
                                    "rule_description" => 'test_description',
                                ],
                            ],
                        ],
                    ];
                });

        $this->app->instance('shield', $shieldClient);

        $slackMessage = "*POWER_BANK_RULES (Rules) Triggered*\n\n*MID*: `<https://dashboard.razorpay.com/admin#/app/merchants/10000000000000/detail | 10000000000000>` flagged\n\n*Shield Id*: `<https://dashboard.razorpay.com/admin/entity/shield.rules/live/223 | 223>`\n*Shield Description*: test_description\n\ncc: <@SPJJUJN4D> <@S0375TGETD0> <@U042S5040AF>";

        $slackPayload = [
            'channel' => \Config::get('slack.channels.risk'),
            'text'    => $slackMessage,
        ];

        $shieldSlackClient = Mockery::mock('RZP\Services\Mock\ShieldSlackClient');

        $shieldSlackClient->shouldReceive('sendRequest')->once()
            ->withArgs(function ($payload) use ($slackPayload) {
                return $payload === $slackPayload;
            })
            ->andReturnUsing(function ($content) {
                return [
                    'status_code' => 200,
                    'body' => [
                        'ok'      => true,
                        'channel' => $content['channel'],
                        'ts'      => '1626322523.000100',
                        'message' => [
                            'bot_id' => 'B123123VCLS',
                            'type'   => 'message',
                            'text'   => $content['text'],
                            'user'   => 'U1231236A11',
                            'ts'     => '1626322523.000100',
                            'team'   => 'T1231236F',
                            'bot_profile' => [
                                'id'      => 'B123123VCLS',
                                'deleted' => false,
                                'name'    => 'risk_alerts',
                                'updated' => 1626273159,
                                'app_id'  => 'A123123A732',
                                'team_id' => 'T1231236F',
                            ],
                        ],
                    ],
                ];
            });

        $this->app->instance('shield.slack', $shieldSlackClient);

        \Config::set('applications.shield.slack.cc_user_ids', 'SPJJUJN4D,S0375TGETD0,U042S5040AF');

        \Config::set('applications.shield.slack.eligible_rule_codes', 'power_bank_rules,international_ddos_rule');

        $testPayment = $this->getDefaultPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($testPayment)
        {
            $this->doAuthPayment($testPayment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);
    }

    protected function runFraudDetectionTestWithVirtualDeviceId($value)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '341111111111111';

        $payment['card']['cvv'] = '1234';

        $payment['_'] = ['device_id' => $value];

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockRazorx();

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient');

        $shieldClient->shouldReceive('evaluateRules')
            ->andReturnUsing(function ($payload) use ($value) {
                $action = 'block';

                if ((empty($value) === true) and
                    (isset($payload['input']['virtual_device_id']) === false))
                {
                    $action = 'allow';
                }

                if ((empty($value) === false) and
                    (isset($payload['input']['virtual_device_id']) === true) and
                    ($payload['input']['virtual_device_id'] === $value))
                {
                    $action = 'allow';
                }

                return [
                    "action" => $action,
                    "max_rule_weight" => 0,
                    "maxmind_score" => null,
                    "triggered_rule_weight" => 0,
                ];
            });

        $this->app->instance('shield', $shieldClient);

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testFraudDetectionVirtualDeviceIdWithNonEmptyValue()
    {
        $deviceId = '1.4fdd46b919c4fda5d0cbc4559f01effc678c57d2.1601468626490.93465236';

        $this->runFraudDetectionTestWithVirtualDeviceId($deviceId);
    }

    public function testFraudDetectionVirtualDeviceIdWithNullValue()
    {
        $this->runFraudDetectionTestWithVirtualDeviceId(null);
    }

    public function testFraudDetectionVirtualDeviceIdWithEmptyValue()
    {
        $this->runFraudDetectionTestWithVirtualDeviceId('');
    }



    protected function runFraudDetectionForAppUrl($appUrls, $packageName = null, $platform = null, $os = null, $action = 'review')
    {
        $this->mockRazorx('save_txn_url');

        $merchant_id = '10000000000000';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'    => '10000000000000',
        ]);

        $merchantBusinessDetail = (new BusinessDetail\Service())->saveBusinessDetailsForMerchant($merchant_id, [
            'app_urls'  => $appUrls,
        ]);

        $shieldClient = Mockery::mock('RZP\Services\Mock\ShieldClient')->makePartial();

        $shieldClient->shouldReceive('evaluateRules')
                     ->andReturn([
                                     "action"                => $action,
                                     "max_rule_weight"       => 0,
                                     "maxmind_score"         => null,
                                     "triggered_rule_weight" => 0,
                                 ]);

        $this->app->instance('shield', $shieldClient);

        $testPayment = $this->getDefaultPaymentArray();


        $testPayment['card']['number'] = '341111111111111';

        $testPayment['card']['cvv'] = '1234';

        $testPayment['_'] = [
            'package_name' => $packageName,
            'platform'     => $platform,
            'os'           => $os,
        ];

        $this->doAuthPayment($testPayment);

        $response = (new BusinessDetail\Service())->fetchBusinessDetailsForMerchant($merchantBusinessDetail->getMerchantId());

        return $response;
    }

    public function testFraudDetectionTxnAppUrlsWithAllNullValues()
    {
        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl([]);

        $appUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'];

        $this->assertEquals([], $appUrls);
    }

    public function testFraudDetectionTxnAppUrlsWithIncorrectPlatform()
    {
        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl([], 'b', 'mac', 'android', 'review');

        $appUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'];

        $this->assertEquals([], $appUrls);
    }

    public function testFraudDetectionTxnAppUrlsWithValidValues()
    {
        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl([], 'b', 'mobile_sdk', 'android', 'review');

        $expectedTxnUrls = [
            sprintf('%s%s', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX, 'b'),
        ];

        $txnUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'][BusinessDetail\Constants::TXN_PLAYSTORE_URLS];

        $this->assertEquals($expectedTxnUrls, $txnUrls);
    }

    public function testFraudDetectionTxnAppUrlsWithTxnUrlAlreadyPresentAndUrlsLimit()
    {
        $inputUrls = [];

        for ($i = 0; $i < BusinessDetail\Constants::TXN_PLAYSTORE_URL_COUNT_LIMIT; $i++)
        {
            array_push($inputUrls, sprintf('%sx.%d', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX, $i));
        }

        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl([
            BusinessDetail\Constants::TXN_PLAYSTORE_URLS    => $inputUrls
        ], 'x.2', 'mobile_sdk', 'android', 'review');

        $expectedLastUrl = sprintf('%sx.2', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX);

        $txnUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'][BusinessDetail\Constants::TXN_PLAYSTORE_URLS];

        $this->assertEquals($expectedLastUrl, end($txnUrls));
    }

    public function testFraudDetectionTxnAppUrlsWithTxnUrlNotPresentAndUrlsLimit()
    {
        $inputUrls = [];

        for ($i = 0; $i < BusinessDetail\Constants::TXN_PLAYSTORE_URL_COUNT_LIMIT; $i++)
        {
            array_push($inputUrls, sprintf('%sx.%d', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX, $i));
        }

        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl([
            BusinessDetail\Constants::TXN_PLAYSTORE_URLS    => $inputUrls
        ], 'x.99', 'mobile_sdk', 'android', 'review');

        $expectedFirstUrl = sprintf('%sx.1', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX);

        $expectedLastUrl = sprintf('%sx.99', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX);

        $txnUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'][BusinessDetail\Constants::TXN_PLAYSTORE_URLS];

        $this->assertEquals($expectedLastUrl, end($txnUrls));

        $this->assertEquals($expectedFirstUrl, $txnUrls[0]);
    }

    public function testFraudDetectionTxnAppUrlsWithPlaystoreUrlPresent()
    {
        $inputUrls = [
            BusinessDetail\Constants::PLAYSTORE_URL => sprintf('%s%s', BusinessDetail\Constants::PLAYSTORE_URL_PREFIX, 'c.s'),
            BusinessDetail\Constants::APPSTORE_URL => null,
        ];
        $merchantBusinessDetail = $this->runFraudDetectionForAppUrl($inputUrls, 'b', 'mobile_sdk', 'android', 'review');

        $appUrls = $merchantBusinessDetail->toArrayPublic()['app_urls'];

        $this->assertArrayNotHasKey(BusinessDetail\Constants::TXN_PLAYSTORE_URLS, $appUrls);

        $this->assertEquals($inputUrls, $appUrls);
    }
}
