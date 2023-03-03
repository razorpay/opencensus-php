<?php

namespace RZP\Tests\Functional\EMI;

use RZP\Models\Admin;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Emi\Entity as EmiEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class EmiTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/EMITestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testAddEmiPlansWithoutMerchant()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testDuplicateAddEmiPlans()
    {
        $this->ba->adminAuth();

        $this->fixtures->create(
        'emi_plan',
        [
            'bank'        => 'HDFC',
            'methods'     => 'card',
            'type'        => 'credit',
            'merchant_id' => '100000Razorpay',
            'subvention'  => 'customer',
            'duration'    => 9,
        ]);

        $this->startTest();
    }

    public function testAddEmiPlansWithMerchant()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddEmiPlansWithMerchantPayback()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddBOBEmiPlansWithMerchant()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddBOBEmiPlanWithoutType()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEnableMerchantSubvention()
    {
        $emiPlan = $this->fixtures->create('emi_plan');

        $emiPlanId = $emiPlan['id'];

        $this->testData[__FUNCTION__]['request']['url'] = '/merchant/10000000000000/emi_plan/' . $emiPlanId;

        $this->startTest();
    }

    /**
     * By default all tests are run on app auth. On public auth
     * there is one route where we return massaged data in different format
     * for checkout to use.
     */
    public function testFetchAllEmiPlansOnPublicAuth()
    {
        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [Merchant\Methods\EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth();

        $this->startTest();

        $this->disableCpsEmiFetch();

        $this->disbaleCpsConfig();

        // If we don't reset fetched keys, it'll get the cps config from this instead of from cache for
        // further tests
        Admin\ConfigKey::resetFetchedKeys();
    }

    public function testFetchAllEmiPlansOnPublicAuthViaCps()
    {
        $this->enableCpsConfig();

        $this->enableCpsEmiFetch();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('query')
            ->andReturnUsing(function (string $entityName, $query)
            {
                if ($query['merchant_id'] === Merchant\Account::TEST_ACCOUNT)
                {
                    return [
                        'success'   => true,
                        'emi_plans' => [
                            [
                                EmiEntity::ID               => 'emipln12345679',
                                EmiEntity::MERCHANT_ID      => Merchant\Account::TEST_ACCOUNT,
                                EmiEntity::BANK             => 'HDFC',
                                EmiEntity::TYPE             => 'credit',
                                EmiEntity::RATE             => 1200,
                                EmiEntity::DURATION         => 9,
                                EmiEntity::METHODS          => 'card',
                                EmiEntity::MIN_AMOUNT       => 500000,
                                EmiEntity::ISSUER_PLAN_ID   => null,
                                EmiEntity::SUBVENTION       => 'customer',
                                EmiEntity::MERCHANT_PAYBACK => 123,
                                EmiEntity::CREATED_AT       => 0,
                                EmiEntity::UPDATED_AT       => 0,
                                EmiEntity::DELETED_AT       => 0,
                            ],
                        ],
                    ];
                }
                else if ($query['merchant_id'] === Merchant\Account::SHARED_ACCOUNT)
                {
                    return [
                        'success'   => true,
                        'emi_plans' => [
                            [
                                EmiEntity::ID               => 'emipln33345679',
                                EmiEntity::MERCHANT_ID      => Merchant\Account::SHARED_ACCOUNT,
                                EmiEntity::BANK             => 'HDFC',
                                EmiEntity::TYPE             => 'credit',
                                EmiEntity::RATE             => 1200,
                                EmiEntity::DURATION         => 9,
                                EmiEntity::METHODS          => 'card',
                                EmiEntity::MIN_AMOUNT       => 500000,
                                EmiEntity::ISSUER_PLAN_ID   => null,
                                EmiEntity::SUBVENTION       => 'customer',
                                EmiEntity::MERCHANT_PAYBACK => 123,
                                EmiEntity::CREATED_AT       => 0,
                                EmiEntity::UPDATED_AT       => 0,
                                EmiEntity::DELETED_AT       => 0,
                            ],
                        ],
                    ];
                }
                return null;
            });

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [
                    Merchant\Methods\EmiType::CREDIT => '1'
                ],
            ]);

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth();

        $this->startTest();

        $this->disableCpsEmiFetch();

        $this->disbaleCpsConfig();

        // If we don't reset fetched keys, it'll get the cps config from this instead of from cache for
        // further tests
        Admin\ConfigKey::resetFetchedKeys();
    }

    public function testFetchAllEmiPlansWithSbiOnPublicAuth()
    {
        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [Merchant\Methods\EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1 , 'SBIN' => 1]);

        $this->fixtures->emiPlan->createDefaultEmiPlans();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $request = [
            'content' => [
                ],
            'url'    => '/emi',
            'method' => 'get',
        ];

        $this->ba->publicAuth();

        $emiPlans = $this->makeRequestAndGetContent($request);

        $this->assertNotContains('SBIN', array_keys($emiPlans));

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id'           => '10000000000000',
                'gateway'               => 'emi_sbi',
                'gateway_merchant_id'   => '250000002',
                'enabled'               => 0,
            ]);

        $terminalId = $terminal->getId();

        $emiPlans = $this->makeRequestAndGetContent($request);

        $this->assertNotContains('SBIN', array_keys($emiPlans));

        $this->assertContains('HDFC', array_keys($emiPlans));

        // After a few days, when the merchant has been onboarded.
        $this->fixtures->edit('terminal', $terminalId, ['enabled' => 1]);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchEmiPlanUsingPlanId()
    {
        $this->fixtures->create('emi_plan');

        $this->startTest();
    }

    public function testFetchEmiPlanUsingPlanIdAndAssertIssuerNameForNetwork()
    {
        $this->fixtures->create(
            'emi_plan',
            [
                'bank'    => null,
                'network' => 'AMEX',
            ]);

        $this->startTest();
    }

    public function testDeleteEmiPlan()
    {
        $this->fixtures->create('emi_plan');

        $this->startTest();
    }

    public function testAddCobrandingPartnerEmiPlan()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddFDRLEmiPlansWithMerchant()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddMultipleEmiPlansWithSameDuration()
    {
        $this->ba->adminAuth();

        $request = [
            'content' => [
                'cobranding_partner' => 'onecard',
                'duration'           => 3,
                'rate'               => 1045,
                'methods'            => 'card',
                'min_amount'         => 400000,
                'merchant_id'        => '100000Razorpay',
                'type'               => 'credit',
            ],
            'url'    => '/emi',
            'method' => 'post',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $plan = $this->getDbLastEntityToArray('emi_plan');
        $this->assertEquals($plan['id'], $content['id']);

        unset($request['content']['cobranding_partner']);
        $request['content']['bank'] = 'KKBK';

        $content = $this->makeRequestAndGetContent($request);

        $plan = $this->getDbLastEntityToArray('emi_plan');
        $this->assertEquals($plan['id'], $content['id']);

        unset($request['content']['bank']);
        $request['content']['network'] = 'AMEX';

        $content = $this->makeRequestAndGetContent($request);

        $plan = $this->getDbLastEntityToArray('emi_plan');
        $this->assertEquals($plan['id'], $content['id']);
    }
}
