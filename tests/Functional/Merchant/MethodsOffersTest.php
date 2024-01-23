<?php

namespace RZP\Tests\Functional\Merchant;

use Mockery;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Admin\ConfigKey as AdminConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\InternationalIntegration\Entity as InternationalIntegrationEntity;
use RZP\Models\Merchant\Methods\EmiType;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Order\ProductType;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MethodsOffersTest extends TestCase
{
    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MethodsOffersTestData.php';

        parent::setUp();
    }

    public function testGetPaymentMethodsAndOffersForCheckoutWithoutOrder(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableAdditionalWallets([Wallet::MCASH, Wallet::GRABPAY, Wallet::TOUCHNGO, Wallet::BOOST]);

        $this->fixtures->merchant->enablePaytm();

        $this->startTest();
    }

    public function testGetPaymentMethodsAndOffersForCheckoutWithOrder(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer3 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'], 'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
            $offer3,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['order' => $order->toArray()];

        $response = $this->startTest($testData);

        $this->assertEquals($offer1->getPublicId(), $response['offers'][0]['id']);
        $this->assertEquals($offer2->getPublicId(), $response['offers'][1]['id']);
        $this->assertEquals($offer3->getPublicId(), $response['offers'][2]['id']);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutForB2BExportForPaymentLinkWithOrder(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->enablePaytm();
        $this->fixtures->merchant->enableIntlBankTransfer();
        $this->createMerchantInternationalIntegrationFixtures();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ],[
            'amount' => '200000',
            'currency' => 'USD',
            'product_type' => ProductType::PAYMENT_LINK_V2
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['order' => $order->toArray()];

        $this->startTest($testData);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithOrderAmountGreaterThanMaxAmount(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->enablePaytm();
        $this->fixtures->merchant->enableIntlBankTransfer();
        $this->createMerchantInternationalIntegrationFixtures();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ],[
            'amount' => '9000000',
            'currency' => 'USD',
            'product_type' => ProductType::PAYMENT_LINK_V2
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['order' => $order->toArray()];

        $this->startTest($testData);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithOrderAmountLessThanMinAmount(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->enablePaytm();
        $this->fixtures->merchant->enableIntlBankTransfer();
        $this->createMerchantInternationalIntegrationFixtures();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ],[
            'amount' => '100000',
            'currency' => 'USD',
            'product_type' => ProductType::PAYMENT_LINK_V2
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['order' => $order->toArray()];

        $this->startTest($testData);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutForB2BExportWithNonPaymentLinkOrder(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->enablePaytm();
        $this->fixtures->merchant->enableIntlBankTransfer();
        $this->createMerchantInternationalIntegrationFixtures();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['order' => $order->toArray()];

        $response = $this->startTest($testData);
        $intlBankTransferMethods = $response['methods']['intl_bank_transfer'];
        $this->assertEmpty($intlBankTransferMethods);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutWithInvoiceId(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $invoice = $this->fixtures->create('invoice', ["order_id" => $order->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['invoice_id'] = $invoice->getPublicId();

        $response = $this->startTest($testData);

        $this->assertEquals($offer1->getPublicId(), $response['offers'][0]['id']);
        $this->assertEquals($offer2->getPublicId(), $response['offers'][1]['id']);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutWithSubscriptionId(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $subscriptionId = UniqueIdEntity::generateUniqueId();

        $this->fixtures->create('invoice', [
            'order_id' => $order->getId(),
            'subscription_id' => $subscriptionId,
            'status' => 'issued',
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['subscription_id'] = $subscriptionId;

        $response = $this->startTest($testData);

        $this->assertEquals($offer1->getPublicId(), $response['offers'][0]['id']);
        $this->assertEquals($offer2->getPublicId(), $response['offers'][1]['id']);
    }

    public function testGetCacheableMethodsDataForCheckout(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableAdditionalWallets([Wallet::MCASH, Wallet::GRABPAY, Wallet::TOUCHNGO, Wallet::BOOST]);

        $this->fixtures->merchant->enablePaytm();

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['SBIN' => 1, 'CITI' => 1]);

        $this->fixtures->terminal->create([
            'merchant_id' => '10000000000000',
            'gateway'     => 'emi_sbi',
        ]);


        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101810',
                'merchant_id' => '10000000000000',
                'bank'        => 'CITI',
                'type'        => 'credit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);
        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101910',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1650,
                'min_amount'  => 100000,
                'duration'    => 3,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101912',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1500,
                'min_amount'  => 100000,
                'duration'    => 6,
            ]);

        $this->startTest();
    }

    public function testGetCacheableMethodsForInAppBankAccountAndCreditCardEnabledMerchant()
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $methods = [
            'upi'           => 1,
            'addon_methods' => [
                'upi' => [
                    'in_app' => 1,
                    'credit_card' => 1
                ]
            ]
        ];

        $this->fixtures->edit('methods', '10000000000000', $methods);

        $this->startTest();
    }

    public function testGetCacheableMethodsForInAppBankAccountEnabledAndCreditCardDisabledMerchant()
    {
        $this->testGetCacheableMethodsForInAppBankAccountAndCreditCardEnabledMerchant();

        $methods = [
            'upi'           => 1,
            'addon_methods' => [
                'upi' => [
                    'in_app' => 1,
                    'credit_card' => 0
                ]
            ]
        ];

        $this->fixtures->edit('methods', '10000000000000', $methods);

        $testData = $this->testData['testGetCacheableMethodsForInAppBankAccountAndCreditCardEnabledMerchant'];

        $testData['response']['content']['upi_config']['in_app']['payer_account_type']['credit_card'] = false;

        $this->startTest($testData);
    }

    public function testGetCacheableMethodsForOnlyInAppEnabledMerchant()
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $methods = [
            'upi'           => 1,
            'addon_methods' => [
                'upi' => [
                    'in_app' => 1,
                ]
            ]
        ];

        $this->fixtures->edit('methods', '10000000000000', $methods);

        $testData = $this->testData['testGetCacheableMethodsForInAppBankAccountAndCreditCardEnabledMerchant'];

        $testData['response']['content']['upi_config']['in_app']['payer_account_type']['credit_card'] = false;

        $this->startTest();
    }

    public function testGetOffersDataForCheckoutWithOrder(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        // offer3 should not come in final response since it fails min amount validation
        $offer3 = $this->fixtures->create('offer:live_card', [
            'min_amount' => 10000000,
        ]);
        $offer4 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'], 'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
            $offer3,
            $offer4,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            'request_type' => 1,
            'order' => $order->toArray(),
            'order_id' => $order->getPublicId(),
        ];

        $response = $this->startTest($testData);

        $this->assertEquals($offer1->getPublicId(), $response['offers'][0]['id']);
        $this->assertEquals($offer2->getPublicId(), $response['offers'][1]['id']);
        $this->assertEquals($offer4->getPublicId(), $response['offers'][2]['id']);
        $this->assertArrayNotHasKey( 'force_offer', $response);
    }

    public function testGetEmiDataForCheckoutWithForcedEmiSubventionOfferWithMerchantSpecificEmi(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'emi_durations'   => [6],
            'payment_network' => null,
            'payment_method_type' => 'credit',
            'min_amount' => 100000
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            'request_type' => 1,
            'order' => $order->toArray(),
            'order_id' => $order->getPublicId(),
        ];

        $this->startTest($testData);
    }

    public function testGetEmiDataForCheckoutWithEmiSubventionOfferWithMerchantSpecificEmi(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'emi_durations'   => [6],
            'payment_network' => null,
            'payment_method_type' => 'credit',
            'min_amount' => 100000
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            'request_type' => 1,
            'order' => $order->toArray(),
            'order_id' => $order->getPublicId(),
        ];

        $this->startTest($testData);
    }

    public function testGetEmiDataForCheckoutWithMultipleSubEmiOffers(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1,'AMEX' => 1]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer1 = $this->fixtures->create('offer:emi_subvention', [
            'payment_method_type'=>'credit'
        ]);

        $offer2 = $this->fixtures->create('offer:emi_subvention', [
            'payment_method_type'=>'credit',
            'emi_durations' => [6,9]
        ]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1, $offer2
        ], ['amount' => 400000]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            'request_type' => 1,
            'order' => $order->toArray(),
            'order_id' => $order->getPublicId(),
        ];

        $this->startTest($testData);
    }

    public function testGetEmiDataForCheckoutForDebitEmiWithExistingCreditEmi(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();
        $this->fixtures->merchant->enableDebitEmiProviders();
        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101310',
                'merchant_id' => '100000Razorpay',
                'bank'        => 'HDFC',
                'type'        => 'credit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101312',
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $this->startTest();
    }

    private function mockCredEligibilityResponse($gatewayResponse = null, \Throwable $gatewayException = null, array $pRequest = null)
    {
        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableApp('10000000000000', 'cred');

        $this->fixtures->merchant->addFeatures(['cred_merchant_consent']);

        $this->fixtures->create('terminal:direct_cred_terminal');

        $order = $this->fixtures->order->create(['receipt' => 'check123', 'amount' => '100', 'app_offer' => true]);

        (new AdminService())->setConfigKeys([AdminConfigKey::ENABLE_CRED_ELIGIBILITY_CALL => true]);

        $defaultRequest = [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type'  => 2,
                'customer_id'   => 'cust_1000ggcustomer',
                'order_id'      => $order->getPublicId(),
                'order'         => $order->toArray(),
            ],
        ];

        $request = $pRequest ?? $defaultRequest;

        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
                Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway, $action, $input, $mode, $terminal) use ($gatewayResponse, $gatewayException)
            {
                if (is_null($gatewayException) === false)
                {
                    throw $gatewayException;
                }

                return $gatewayResponse;
            });

        $this->app->instance('gateway', $gateway);

        $this->ba->checkoutServiceProxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function enableRazorXTreatmentForFeature($featureUnderTest, $value = 'on'): void
    {
        $mock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $mock->method('getTreatment')
            ->willReturnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value) {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            );

        $this->app->instance('razorx', $mock);

    }

    public function testGetCheckoutAppMetaAfterCredEligibility(): void
    {
        $response = $this->mockCredEligibilityResponse(
            null,
            new GatewayErrorException(ErrorCode::BAD_REQUEST_CRED_CUSTOMER_NOT_ELIGIBLE)
        );

        $this->assertEquals(false, $response['app_meta']['cred']['hit_eligibility']);
        $this->assertEquals(false, $response['app_meta']['cred']['user_eligible']);
    }

    public function testGetCheckoutAppMetaAfterCredEligibilityTimeout(): void
    {
        $response = $this->mockCredEligibilityResponse(
            null,
            new GatewayTimeoutException(ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT)
        );

        $this->assertEquals(true, $response['app_meta']['cred']['hit_eligibility']);
        $this->assertArrayNotHasKey('offer', $response['app_meta']['cred']);
        $this->assertArrayNotHasKey('user_eligible', $response['app_meta']['cred']);
    }

    public function testGetCheckoutAppMetaAfterCredEligibilityOffersSubtext(): void
    {
        $this->enableRazorXTreatmentForFeature(
            RazorxTreatment::CRED_OFFER_SUBTEXT,
            'sub_text'
        );

        $credOffer = 'pay seamlessly using your CRED coins. #killthebill';

        $gatewayResponse = [
            'data'    => [
                'state'       => 'ELIGIBLE',
                'tracking_id' => 'rand10001',
                'layout'      => [
                    'sub_text' => $credOffer,
                ],
            ]
        ];

        $response = $this->mockCredEligibilityResponse($gatewayResponse);

        $this->assertEquals('sub_text', $response['app_meta']['cred']['experiment']);
        $this->assertEquals(false, $response['app_meta']['cred']['hit_eligibility']);
        $this->assertEquals($credOffer, $response['app_meta']['cred']['offer']['description']);
        $this->assertEquals(true, $response['app_meta']['cred']['user_eligible']);
    }

    public function testGetCheckoutAppMetaAfterCredEligibilityStickyOffersSubtext(): void
    {
        $credOffer = 'pay seamlessly using your CRED coins. #killthebill';

        $gatewayResponse = [
            'data'    => [
                'state'       => 'ELIGIBLE',
                'tracking_id' => 'rand10001',
                'layout'      => [
                    'sub_text' => $credOffer,
                ],
            ]
        ];

        $request = [
            'url' => '/internal/methods_offers/checkout',
            'method' => 'POST',
            'content' => [
                'request_type' => 2,
                'customer_id' => 'cust_1000ggcustomer',
                'cred_offer_experiment' => 'subtext',
            ],
        ];

        $response = $this->mockCredEligibilityResponse($gatewayResponse, null, $request);

        $this->assertEquals('subtext', $response['app_meta']['cred']['experiment']);
        $this->assertEquals($credOffer, $response['app_meta']['cred']['offer']['description']);
        $this->assertEquals(false, $response['app_meta']['cred']['hit_eligibility']);
        $this->assertEquals(true, $response['app_meta']['cred']['user_eligible']);
    }

    public function testGetPaymentMethodsAndOffersForCheckoutWithOrderProcessingFee(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['SBIN' => 1, 'CITI' => 1]);

        $this->fixtures->terminal->create([
            'merchant_id' => '10000000000000',
            'gateway'     => 'emi_sbi',
        ]);


        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101810',
                'merchant_id' => '10000000000000',
                'bank'        => 'CITI',
                'type'        => 'credit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);
        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101910',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1650,
                'min_amount'  => 100000,
                'duration'    => 3,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101912',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1500,
                'min_amount'  => 100000,
                'duration'    => 6,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101913',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1500,
                'min_amount'  => 100000,
                'duration'    => 9,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101914',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1500,
                'min_amount'  => 100000,
                'duration'    => 12,
            ]);

        $this->fixtures->merchant->enableAdditionalWallets([Wallet::MCASH, Wallet::GRABPAY, Wallet::TOUCHNGO, Wallet::BOOST]);

        $this->fixtures->merchant->enablePaytm();

        $testData = $this->testData[__FUNCTION__];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('processing_fee_plan', $content['methods']['emi_options']['SBIN'][1]);
    }

    public function
    testGetEmiDataForCheckoutWithEmiSubventionOfferWithMerchantSpecificEmiProcessingFee(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['SBIN' => 1]);

        $this->fixtures->terminal->create([
            'merchant_id' => '10000000000000',
            'gateway'     => 'emi_sbi',
        ]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101912',
                'merchant_id' => '10000000000000',
                'bank'        => 'SBIN',
                'type'        => 'credit',
                'rate'        => 1500,
                'min_amount'  => 100000,
                'duration'    => 6,
            ]);

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention',
            [
                'issuer'          => 'SBIN',
                'emi_durations'   => [6],
                'payment_network' => null,
                'payment_method_type' => 'credit',
                'min_amount' => 100000
            ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            'request_type' => 1,
            'order' => $order->toArray(),
            'order_id' => $order->getPublicId(),
            'amount'        => 1000000,
        ];
        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('processing_fee_plan', $content['emi_options']['SBIN'][0]);
    }

    protected function createMerchantInternationalIntegrationFixtures(
        string $merchantId = Account::TEST_ACCOUNT,
        string $status = '',
    ): void {
        $notes = [];

        if ($status !== '') {
            $notes['status'] = $status;
        }

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegrationEntity::MERCHANT_ID => $merchantId,
            InternationalIntegrationEntity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegrationEntity::INTEGRATION_KEY => '1029329285-19298',
            InternationalIntegrationEntity::NOTES => $notes,
            InternationalIntegrationEntity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);
    }

    private function getBankAccountMockData(string $vaCurrency = 'USD'): string
    {
        return match ($vaCurrency) {
            'USD' => '[{"bank_name":"Community Federal Savings Bank","va_currency":"USD","bank_address":"810 Seventh Avenue, New York, NY 10019, US","account_number":"0335086498","routing_details":[{"routing_code":"026073150","routing_type":"ach_routing_number"},{"routing_code":"026073008","routing_type":"wire_routing_number"}],"beneficiary_name":"ALPHA CORP"}]',
            'GBP' => '[{"bank_name":"Community Federal Savings Bank","va_currency":"GBP","bank_address":"12 Steward Street, The Steward Building, London, E1 6FQ, GB","account_number":"92979037","routing_details":[{"routing_code":"123456","routing_type":"sort_code"}],"beneficiary_name":"ALPHA CORP"}]',
            default => '[{"bank_name":"Community Federal Savings Bank","va_currency":"SWIFT","bank_address":"12 Steward Street, The Steward Building, London, E1 6FQ, GB","account_number":"GB51TCCL12345692979037","routing_details":[{"routing_code":"TCCLGB123","routing_type":"bic_swift"}],"beneficiary_name":"ALPHA CORP"}]',
        };
    }
}
