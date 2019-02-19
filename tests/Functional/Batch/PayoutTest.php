<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;

class PayoutTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PayoutTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfPayoutType()
    {
        // TODO: Besides basis tests in BatchTest for this tpye, specific tests cases to be added in next pr.
    }
}
