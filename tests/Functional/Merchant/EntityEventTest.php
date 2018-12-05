<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;


class EntityEventTest extends TestCase
{
    protected $testData;

    public function __construct()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/EntityEventTestData.php';

        parent::__construct();
    }

    public function testEntityEvent()
    {
        $merchant = $this->fixtures->create('merchant:event_account');

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $merchant->toArrayEvent());
    }
}
