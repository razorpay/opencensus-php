<?php

namespace RZP\Tests\Functional\Merchant;

use Event;

use RZP\Exception;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class RefundPricingTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;

    protected $authToken = null;

    protected $org = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundPricingTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    protected function createPricingPlan($pricingPlan = [])
    {
        $defaultPricingPlan = [
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function createPricingPlanWithPercentRate($pricingPlan = [])
    {
        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'DICL',
            'payment_issuer'      => 'HDFC',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function createMethodIndependentInstantRefundPricingPlan()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule1)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule3);

        return $planId;
    }

    protected function createMethodIndependentInstantRefundPricingPlanWithPercentRate()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $planId = $this->createPricingPlanWithPercentRate($instantRefundsPricingPlanRule1)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule3);

        return $planId;
    }

    protected function createMethodIndependentInstantRefundPricingPlanWithAll()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule4 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule5 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule6 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule7 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule8 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule9 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule10 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule11 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule12 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule1)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule3);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule4);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule5);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule6);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule7);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule8);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule9);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule10);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule11);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule12);

        return $planId;
    }

    protected function createAmountRangeActiveFalsePricing()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => null,
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '0',
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule1)['id'];

        return $planId;
    }

    protected function createAmountRangeActiveFalsePricingWithMethods()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '0',
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '0',
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '0',
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule1)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule3);

        return $planId;
    }

    protected function createMethodIndependentInstantRefundPricingPlanWithMethods()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule4 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule5 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule6 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule7 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule8 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule9 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule3)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule1);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule5);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule6);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule4);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule9);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule7);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule8);

        return $planId;
    }

    protected function createMethodIndependentInstantRefundPricingPlanWithMethodsMoreThanSixSlabs()
    {
        $instantRefundsPricingPlanRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 500,
        ];

        $instantRefundsPricingPlanRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 500,
            'amount_range_max'      => 5000,
        ];

        $instantRefundsPricingPlanRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 5000,
            'amount_range_max'      => 20000,
        ];

        $instantRefundsPricingPlanRule4 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 20000,
            'amount_range_max'      => 60000,
        ];

        $instantRefundsPricingPlanRule5 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 60000,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule6 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule7 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule8 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 500,
        ];

        $instantRefundsPricingPlanRule9 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 500,
            'amount_range_max'      => 5000,
        ];

        $instantRefundsPricingPlanRule10 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 5000,
            'amount_range_max'      => 20000,
        ];

        $instantRefundsPricingPlanRule11 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 20000,
            'amount_range_max'      => 60000,
        ];

        $instantRefundsPricingPlanRule12 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 60000,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule13 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule14 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanRule15 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 500,
        ];

        $instantRefundsPricingPlanRule16 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 567,
            'amount_range_active'   => '1',
            'amount_range_min'      => 500,
            'amount_range_max'      => 5000,
        ];

        $instantRefundsPricingPlanRule17 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 5000,
            'amount_range_max'      => 20000,
        ];

        $instantRefundsPricingPlanRule18 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 20000,
            'amount_range_max'      => 60000,
        ];

        $instantRefundsPricingPlanRule19 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 60000,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanRule20 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 678,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanRule21 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $planId = $this->createPricingPlan($instantRefundsPricingPlanRule1)['id'];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule3);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule4);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule5);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule6);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule7);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule8);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule9);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule10);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule11);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule12);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule13);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule14);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule15);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule16);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule17);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule18);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule19);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule20);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanRule21);

        return $planId;
    }

    protected function addModeLevelPricingRules($planId)
    {
        $instantRefundsPricingPlanModeRule1 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanModeRule2 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanModeRule3 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'card',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanModeRule4 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => 'UPI',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanModeRule5 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => 'UPI',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanModeRule6 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'upi',
            'payment_method_type'   => 'UPI',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $instantRefundsPricingPlanModeRule7 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 0,
            'amount_range_max'      => 100000,
        ];

        $instantRefundsPricingPlanModeRule8 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 100000,
            'amount_range_max'      => 2500000,
        ];

        $instantRefundsPricingPlanModeRule9 = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => 'NEFT',
            'fixed_rate'            => 200,
            'amount_range_active'   => '1',
            'amount_range_min'      => 2500000,
            'amount_range_max'      => 4294967295,
        ];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule1);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule2);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule3);

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule4);
        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule5);
        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule6);
        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule7);
        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule8);
        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanModeRule9);

    }

    protected function addPricingPlanRule($id, $rule = [])
    {
        $request = array(
            'method' => 'POST',
            'url' => '/pricing/'.$id.'/rule',
            'content' => $rule);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function testCreateAndFetchDefaultPricingPlanAndNoMerchantSpecificPlan()
    {
        $this->createPricingPlan();

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        //
        // Instant Refunds v2 pricing is now default - not behind a razorx anymore
        // Instant Refunds v1 Pricing is behind razorx for merchants in transition phase
        //
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'instant_refunds_default_pricing_v1')
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAndFetchDefaultPricingV2PlanAndNoMerchantSpecificPlan()
    {
        //
        // Instant Refunds v2 pricing is now default - not behind a razorx anymore
        //

        $this->createPricingPlan();

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAndFetchMerchantSpecificMethodIndependentPlan()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlan();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAndFetchMerchantSpecificMethodIndependentPlanWithMethods()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithMethods();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(3, count($response['rules']));
    }

    public function testCreateAndFetchMerchantSpecificMethodLevelPlanWithMethods()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithMethods();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        //
        // Adding an extra rule to make pricing plan method level
        //
        $instantRefundsPricingPlanExtraRule = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 10,
            'amount_range_max'      => 1000,
        ];

        $this->addPricingPlanRule($planId, $instantRefundsPricingPlanExtraRule);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(0, count($response['rules']));
    }

    public function testCreateAndFetchMerchantSpecificModeLevelPlanWithMethods()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithMethods();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->addModeLevelPricingRules($planId);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(0, count($response['rules']));
    }

    public function testCreateAndFetchMerchantSpecificMethodIndependentPlanWithMethodsMoreThanSixSlabs()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithMethodsMoreThanSixSlabs();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(0, count($response['rules']));
    }

    public function testCreatePercentRateForRefunds()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithMethods();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        //
        // Adding an extra rule to make pricing plan method level
        //
        $instantRefundsPricingPlanExtraRule = [
            'plan_name'             => 'TestPlan1',
            'product'               => 'primary',
            'feature'               => 'refund',
            'type'                  => 'pricing',
            'payment_method'        => 'netbanking',
            'payment_method_type'   => null,
            'percent_rate'          => 500,
            'fixed_rate'            => 789,
            'amount_range_active'   => '1',
            'amount_range_min'      => 10,
            'amount_range_max'      => 1000,
        ];

        try
        {
            $this->addPricingPlanRule($planId, $instantRefundsPricingPlanExtraRule);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\BadRequestValidationFailureException::class);
        }
    }

    public function testFetchPercentRateForRefunds()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithPercentRate();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAndFetchAllAndMethodIndependentPricingForRefunds()
    {
        $planId = $this->createMethodIndependentInstantRefundPricingPlanWithAll();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAmountRangeActiveFalse()
    {
        $planId = $this->createAmountRangeActiveFalsePricing();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateAmountRangeActiveFalseWithMethods()
    {
        $planId = $this->createAmountRangeActiveFalsePricingWithMethods();

        $this->fixtures->merchant->editPricingPlanId($planId);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
