<?php

namespace RZP\Tests\Functional\Store;

use RZP\Tests\Functional\TestCase;

class NcaStoreTest extends TestCase
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/NcaStoreTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testRequestPassingFromApiToNca() {


    }
}
