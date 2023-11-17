<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantOneCcConfigForCheckoutTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantOneCcConfigForCheckoutTestData.php';

        parent::setUp();
    }

    public function testGetOneCcMerchantConfigsForCheckout()
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);

        $this->startTest();
    }
}
