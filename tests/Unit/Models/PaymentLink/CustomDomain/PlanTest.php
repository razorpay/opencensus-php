<?php

namespace Unit\Models\PaymentLink\CustomDomain;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaymentLink\CustomDomain;
use RZP\Models\PaymentLink\CustomDomain\Plans;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Models\Schedule;

class PlanTest extends BaseTest
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testStaticPlanStructure()
    {
        $plans = CustomDomain\Plans\Plans::PLAN;

        $keys = [
            Plans\Constants::ALIAS,
            Plans\Constants::NAME,
            Plans\Constants::METADATA
        ];

        $metadataKeys = [
            Plans\Constants::MONTHLY_AMOUNT,
            Plans\Constants::PLAN_AMOUNT,
            Plans\Constants::DISCOUNT
        ];

        foreach($plans as $plan)
        {
            $this->assertArrayKeysExist($plan, $keys);

            $this->assertArrayKeysExist($plan[Plans\Constants::METADATA], $metadataKeys);
        }
    }

    public function testPlanName()
    {
        $plans = CustomDomain\Plans\Plans::PLAN;

        foreach ($plans as $plan)
        {
            $name = $plan[Plans\Constants::ALIAS];

            $nameArray = explode('_', $name);

            $price = $nameArray[count($nameArray) - 1];

            $this->assertTrue(is_numeric($price));

            $this->assertEquals($price, $plan[Plans\Constants::METADATA][Plans\Constants::PLAN_AMOUNT]);

        }
    }

    public function testBillingDateGenerationForPlan()
    {
//        $plans = $this->createPlans();
//
//        $current = Carbon::now(Timezone::IST);
//
//        foreach($plans as $plan)
//        {
//            $newBillingTimestamp = (new Plans\Core())->getNextBillingDate($plan, $current->getTimestamp());
//
//            $interval = $plan->getInterval();
//
//            $this->assertContains(Carbon::createFromTimestamp($newBillingTimestamp)->day, array($current->day, $current->subDay()->day));
//
//            $this->assertEquals(Carbon::createFromTimestamp($newBillingTimestamp)->month, $current->addMonths($interval)->month);
//        }
    }

    protected function createPlans()
    {
        $request = [
            'method'    => 'POST',
            'url'       => '/payment_pages/cds/plans',
            'content'   => [
                'plans' => [
                    [
                        'alias'    => Plans\Aliases::MONTHLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '1'
                    ],
                    [
                        'alias'    => Plans\Aliases::QUARTERLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '3',
                    ],
                    [
                        'alias'    => Plans\Aliases::BIYEARLY_ALIAS,
                        'period'   => 'monthly',
                        'interval' => '6'
                    ],
                ],
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('plans', $response);

        $plans = $this->getDbEntities('schedule', [
            Schedule\Entity::TYPE => Schedule\Type::CDS_PRICING
        ]);

        $this->assertEquals(3, count($plans));

        return $plans;
    }
}
