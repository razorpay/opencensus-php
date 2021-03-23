<?php

namespace RZP\Tests\Functional\Typeform;

use Request;
use ApiResponse;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TypeformTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use EntityActionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TypeformTestData.php';

        parent::setUp();

    }

    public function testFailureTypeformWebhookConsumptionSecurity()
    {
        $this->startTest();
    }

    public function testInvalidDataTypeformWebhookConsumption()
    {
        $this->startTest();
    }

    public function testSuccessTypeformWebhookConsumptionSecurity()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();
    }

    public function testApprovalTypeformWebhookConsumption()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('1000', $merchant->getProductInternational());
    }

    public function testProd2ApprovalTypeformWebhookConsumption()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '0222',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('0111', $merchant->getProductInternational());
    }

    public function testOldWorkflowsExecution()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '0000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', ['merchant_id'     => $merchant->getId(),
                                                                'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('1111', $merchant->getProductInternational());
    }

    public function testApprovalTypeformWebhookConsumptionInvalidWebsite()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->create('merchant',
                                            ['id'                    => 'EV7j5qM0qca1U3',
                                             'product_international' => '0222',
                                             'website'               => '',
                                             'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->create('merchant_detail', ['merchant_id'                   => $merchant->getId(),
                                                    'international_activation_flow' => 'whitelist']);

        $this->startTest();

    }

    public function testWorkflowCreationTypeformWebhook()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertFalse($merchant->getInternationalAttribute());

        $this->assertEquals('0000', $merchant->getProductInternational());
    }
}
