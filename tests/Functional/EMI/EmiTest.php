<?php

namespace RZP\Tests\Functional\EMI;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class EmiTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
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

    public function testAddBOBEmiPlansWithMerchant()
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
        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchAllEmiPlansWithSbiOnPublicAuth()
    {
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
}
