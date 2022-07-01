<?php

namespace RZP\Tests\Functional\Merchant;

use Event;

use RZP\Models\Card\SubType;
use RZP\Models\Feature;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Card\Network;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Methods\Entity as MerchantMethods;
use RZP\Models\Base\QueryCache\Constants as CacheConstants;

class MethodsTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MethodsTestData.php';

        parent::setUp();

    }

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'billdesk',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();

        $count = count($content['netbanking']);

        $this->assertEquals(90, $count);

        $this->assertArrayNotHasKey('recurring', $content);
    }

    public function testGetPaymentMethodsRouteWithNetbankingFalse()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->disableNetbanking('10000000000000');

        $content = $this->startTest();

        $count = count($content['netbanking']);
        $this->assertEquals(0, $count);
    }

    public function testNumOfBanksInTestMode()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->addFeatures('corporate_banks');
        $content = $this->getPaymentMethods();

        $count = count($content['netbanking']);

        $this->assertEquals(90, $count);
    }

    public function testBulkMethodUpdate()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->setDisabledBanks('10000000000000', ['HDFC']);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        $this->assertEquals($content['netbanking'], true);
        $this->assertEquals($content['mobikwik'], true);
        $this->assertTrue($content['cod']);

        $this->assertEquals($content['card_networks']['DICL'], false);
        $this->assertEquals($content['card_networks']['MAES'], true);
        $this->assertEquals($content['card_networks']['RUPAY'], false);
    }

    public function testBulkMethodUpdateCreditEmiEnable()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();

        $methods = $this->getDbEntityById('methods', '10000000000000')->toArray();

        $this->assertEquals(
            [
                'credit'
            ],
            $methods['emi']);

    }

    public function testBulkMethodUpdateCreditEmiEnableInvalidCategory()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '5944']);

        $this->ba->adminAuth();

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();

    }

    public function testBulkMethodUpdateDebitEmiEnableInvalidCategory()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '5944']);

        $this->ba->adminAuth();

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();

    }

    public function testBulkMethodUpdateEnableBanks()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->setDisabledBanks('10000000000000', ['HDFC', 'ICIC']);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        // Assert if HDFC removed from `disabled_banks` list
        $this->assertArraySelectiveEquals(['ICIC'], $content['disabled_banks']);
    }

    public function testBulkMethodUpdateDisableBanks()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->setDisabledBanks('10000000000000', ['HDFC']);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        // Assert if ICIC is added to`disabled_banks` list
        $this->assertArraySelectiveEquals(['HDFC', 'ICIC'], $content['disabled_banks']);
    }

    public function testBulkMethodUpdateInvalidMerchantId()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        $this->assertEquals($content['netbanking'], true);
        $this->assertEquals($content['mobikwik'], true);
    }

    public function testBulkMethodUpdateInvalidMethodsInput()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkMethodUpdateMissingInput()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRecurringCardsOnChargeAtWill()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }

    public function testRecurringCardsOnSubscriptions()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SUBSCRIPTIONS]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }

    public function testRecurringNetbankingOnChargeAtWillInTest()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->enableEmandate();

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $testData = $this->testData['testRecurringNetbankingOnChargeAtWill'];

        $content = $this->startTest($testData);

        $this->assertGreaterThanOrEqual(41, $content['recurring']['emandate']);
    }

    public function testRecurringNetbankingOnChargeAtWillInLive()
    {
        $this->fixtures->merchant->activate();

        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $testData = $this->testData['testRecurringNetbankingOnChargeAtWill'];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('netbanking', $content['recurring']);

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_icici',
            'card'                      => 0,
            'netbanking'                => 0,
            'emandate'                  => 1,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
            'type'                      => [
                Terminal\Type::RECURRING_3DS => '1',
                Terminal\Type::RECURRING_NON_3DS => '1'
            ],
            'enabled'                   => 1,
            'deleted_at'                => null,
        ];

        $this->fixtures->create('terminal', $attributes);

        $content = $this->startTest($testData);

        $this->assertArraySelectiveEquals(
            ['ICIC' => ['name' => 'ICICI Bank', 'auth_types' => ['netbanking']]],
            $content['recurring']['emandate']);
    }

    public function testRecurringNetbankingOnSubscriptions()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');
        $this->fixtures->merchant->enableEmandate('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SUBSCRIPTIONS]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        // No netbanking for subscriptions
        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }

    public function testFetchMethods()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOnecardMerchantMethods()
    {
        $this->fixtures->merchant->enableEmi('10000000000000');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchGooglePayForCardsMethod()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertFalse($response[MerchantMethods::GOOGLE_PAY_CARDS]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::GOOGLE_PAY_CARDS]);

        $response = $this->startTest();

        $this->assertTrue($response[MerchantMethods::GOOGLE_PAY_CARDS]);
    }

    public function testFetchGooglePayMethod()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertFalse($response[MerchantMethods::GPAY]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::GPAY]);

        $response = $this->startTest();

        $this->assertTrue($response[MerchantMethods::GPAY]);
    }

    public function testEnableCreditEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();
    }

    public function testEnableCreditEmiInvalidCategory()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '5944']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testEnableDebitEmiInvalidCategory()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '5944']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testEnableDebitEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();
    }

    public function testEnableCreditAndDebitEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->disableEmi('10000000000000');

        $this->startTest();
    }

    public function testDisableEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->enableEmi('10000000000000');

        $this->startTest();
    }

    public function testDisableCreditEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->enableEmi('10000000000000');

        $this->startTest();
    }

    // Ensure credit emi does not gets disabled when we disable debit emi
    public function testDisableDebitEmi()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->enableEmi('10000000000000');

        $this->startTest();
    }

    public function testEnableDisableCardnetworks()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'amex' => 1,
                'card_networks' => [
                    'dicl' => 0,
                    'jcb'  => 0,
                ]
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertFalse($merchantMethods->isCardNetworkEnabled(Network::JCB));

        $this->assertFalse($merchantMethods->isCardNetworkEnabled(Network::DICL));
    }

    public function testEnableCardnetworksAmexForBlacklistedMccs()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '4411']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEnablePaylaterForBlacklistedMccs()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->edit('10000000000000', ['category' => '5960']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEnableHdfcDebitEmiProvider()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'emi' => ['debit' => '1'],
                'debit_emi_providers' => [
                    'hdfc' => '1',
                ]
            ],
        ];

        $this->fixtures->pricing->createEmiPricingPlan();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertEquals(['HDFC' => 1], $merchantMethods->getDebitEmiProviders());
    }

    public function testBulkEnableHdfcDebitEmiProvider()
    {
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::ACTIVATED      => 1,
            MerchantEntity::ACTIVATED_AT   => 1614921159,
            MerchantEntity::CATEGORY       => 5193,

        ]);

        $merchantId = $merchant['id'];

        $this->fixtures->create('methods', [
            'merchant_id'    => $merchantId,
            'emi'            => [Merchant\Methods\EmiType::DEBIT => '1'],
            'disabled_banks' => [],
            'banks'          => '[]'
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // 5399 category, blacklist
        $this->assertEquals(['HDFC' => 0], $merchantMethods->getDebitEmiProviders());

        $merchantMethods = $this->getDbEntityById('merchant', $merchantId)->getMethods();

        // 5193
        $this->assertEquals(['HDFC' => 1], $merchantMethods->getDebitEmiProviders());
    }

    public function testQueryCacheHitForMethods()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        //
        // Asserts that key is not present initially in cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::METHODS) === true)
                {
                    $this->assertStringContainsString(E::METHODS, $tag);
                }
            }
            return true;
        });

        //
        // Asserts that key is inserted into cache
        //
        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::METHODS) === true)
                {
                    $this->assertStringContainsString(E::METHODS, $tag);
                }
            }
            return true;
        });


        $this->doAuthPayment($payment);

        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::METHODS) === true)
                {
                    $this->assertStringContainsString(
                        implode(':', [
                            CacheConstants::QUERY_CACHE_PREFIX,
                            CacheConstants::DEFAULT_QUERY_CACHE_VERSION,
                            E::METHODS]),
                        $e->key
                    );
                }
            }
            return true;
        });
    }

    public function testEnableDisableCardSubTypes()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'card_subtype' => [
                    'business'  => 0,
                ]
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertTrue($merchantMethods->isSubTypeEnabled(SubType::CONSUMER));

        $this->assertFalse($merchantMethods->isSubTypeEnabled(SubType::BUSINESS));

    }

    public function testMerchantPaybackInEmiOptions()
    {
        $this->ba->proxyAuth();

        $emiPlanEntity = $this->fixtures->emi_plan->create(
            [
                'bank'        => 'HDFC',
                'methods'     => 'card',
                'merchant_id' => '100000Razorpay',
                'subvention'  => 'customer',
                'duration'    => 9,
                'merchant_payback' => 1000
            ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::EDIT_METHODS]);

        $this->fixtures->merchant->enableEmi('10000000000000');

        $response = $this->startTest();

        $this->assertTrue(isset($response["emi_options"][$emiPlanEntity->getBank()][0]["merchant_payback"]));
    }

    public function testEnableTwidForMerchant()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'twid'  => 1,
                ],
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $merchantMethods = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $merchantMethods['apps']['twid']);
    }

    public function testEnableCredWithSubText(){
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'cred'  => 1,
                ],
                'custom_text' => [
                    'cred' => 'discount of 20% with CRED coins'
                ]
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request = [
            'url' => '/merchant/methods',
            'method' => 'get',
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue(isset($response['custom_text']['cred']));
        $this->assertEquals('discount of 20% with CRED coins', $response['custom_text']['cred']);
    }

    public function testEditMerchantSubTextForCred()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'cred'  => 1,
                ],
                'custom_text' => [
                    'cred' => 'discount of 20% with CRED coins'
                ]
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'custom_text' => [
                    'cred' => 'discount of 10% with CRED coins'
                ]
            ] ,
        ];

        $this->makeRequestAndGetContent($request);

        $request = [
            'url' => '/merchant/methods',
            'method' => 'get',
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue(isset($response['custom_text']['cred']));
        $this->assertEquals('discount of 10% with CRED coins', $response['custom_text']['cred']);
    }

    public function testEnableRazorpaywallet()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertTrue($merchantMethods->isRazorpaywalletEnabled());
    }

    public function testEnableItzcash()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'itzcash' => 1,
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // Skipping this, since we're not allowing enablement of AMEX
        // $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertTrue($merchantMethods->isItzcashEnabled());

    }

    public function testEnableOxigen()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'oxigen' => 1,
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // Skipping this, since we're not allowing enablement of AMEX
        // $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertTrue($merchantMethods->isOxigenEnabled());

    }

    public function testEnablePaycash()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'paycash' => 1,
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // Skipping this, since we're not allowing enablement of AMEX
        // $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertTrue($merchantMethods->isPaycashEnabled());

    }

    public function testEnableAmexeasyclick()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'amexeasyclick' => 1,
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // Skipping this, since we're not allowing enablement of AMEX
        // $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertTrue($merchantMethods->isAmexeasyclickEnabled());

    }

    public function testEnableCitibankrewards()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'citibankrewards' => 1,
            ],
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        // Skipping this, since we're not allowing enablement of AMEX
        // $this->assertTrue($merchantMethods->isCardNetworkEnabled(Network::AMEX));

        $this->assertTrue($merchantMethods->isCitibankrewardsEnabled());

    }

    public function testEnableTrustlyForMerchant()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'trustly'  => 1,
                ],
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $merchantMethods = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $merchantMethods['apps']['trustly']);
    }

    public function testEnablePoliForMerchant()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'poli'  => 1,
                ],
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $merchantMethods = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $merchantMethods['apps']['poli']);
    }
    public function testEnableSofortForMerchant()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'sofort'  => 1,
                ],
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $merchantMethods = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $merchantMethods['apps']['sofort']);
    }

    public function testEnableGiropayForMerchant()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'giropay'  => 1,
                ],
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $merchantMethods = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $merchantMethods['apps']['giropay']);
    }
    public function testEnableOfflineMethod()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testInternalGetPaymentInstruments()
    {
        $this->ba->terminalsAuth();
        $this->startTest();
    }
}
