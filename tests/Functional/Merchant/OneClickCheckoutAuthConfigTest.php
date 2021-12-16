<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OneClickCheckoutAuthConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();
    }

    public function testUpdateMerchant1ccShopifyConfig()
    {
        $this->ba->privateAuth('rzp_test', getenv("THIRDWATCH_COD_SCORE_SERVICE_SECRET"));

        $this->startTest();
    }

    public function testUpdateMerchant1ccShopifyConfigInvalidBody()
    {
        $this->ba->privateAuth('rzp_test', getenv("THIRDWATCH_COD_SCORE_SERVICE_SECRET"));

        $this->startTest();
    }

}
