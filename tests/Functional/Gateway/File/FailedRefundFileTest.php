<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\GatewayFile as GatewayFileJob;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/FailedRefundFileTestData.php';

        parent::setUp();
    }

    public function testWithInvalidTarget()
    {
        $this->ba->appAuth();

        $this->startTest();
    }
}
