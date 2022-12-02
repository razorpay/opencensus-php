<?php

namespace Functional\OneClickCheckout;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

class ShopifyTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/ShopifyTestData.php';

        parent::setUp();

        $this->ba->magicConsumerAppAuth();
    }

    public function testCreateOrderAndGetPreferences()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $response = $this->startTest();
        $order = $this->getDbLastOrder();
        $this->assertEquals($response['order_id'], $order->getPublicId());
    }
}