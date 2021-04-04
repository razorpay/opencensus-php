<?php
namespace RZP\Tests\Unit\Models\Pricing;

use RZP\Models\Pricing\Repository;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;

class RepositoryTest extends TestCase
{
    /** @var  Repository */
    protected $pricingRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingRepo = new Repository;
    }

    /**
     * This unit test case is needed to test the part where default
     * org id is picked in the pricing repo class if the context
     * of the org is not present in the auth (batch cases where auth is null)
     */
    function testPricingPlan()
    {
        $pricing = $this->pricingRepo->getPricingPlanById(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->assertNotNull($pricing);

        $this->assertNotEmpty($pricing->all());
    }
}
