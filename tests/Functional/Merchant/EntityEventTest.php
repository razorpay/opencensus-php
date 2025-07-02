<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;


class EntityEventTest extends TestCase
{
    protected $testData;

    protected function setUp():void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/EntityEventTestData.php';

//        parent::__construct("EntityEventTest");
//        parent::__construct("testEntityEvent");
    }

    public function testEntityEvent()
    {
        $merchant = $this->fixtures->create('merchant:event_account');

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $merchant->toArrayEvent());
    }
}
