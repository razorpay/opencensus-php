<?php

namespace RZP\Tests\Functional\Merchant\Detail;

use RZP\Tests\Functional\TestCase;


class DetailEntityEventTest extends TestCase
{
    protected $testData;

    public function __construct()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DetailEntityEventTestData.php';

        parent::__construct();
    }

    public function testDetailEntityEvent()
    {
        $merchant = $this->fixtures->create('merchant_detail:event_account');

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $merchant->toArrayEvent());
    }
}
