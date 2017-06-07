<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class MerchantPromotionsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantPromotionsTest.php';

        parent::setUp();

        // All API calls to Credits have to be through admin account.
        $this->ba->appAuth();
    }
}
