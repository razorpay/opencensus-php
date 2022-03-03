<?php

namespace RZP\Tests\Functional\PayoutOutbox;

use RZP\Tests\Functional\TestCase;

class PayoutOutboxTest extends TestCase
{
    protected function setUp(): void {
        $this->testDataFilePath = __DIR__ . '/PayoutOutboxTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testPayoutOutboxPartitionCron() {
        $this->ba->cronAuth();

        $this->markTestSkipped(); // marking as skipped, can be used locally to trigger request

        $this->startTest();
    }
}
