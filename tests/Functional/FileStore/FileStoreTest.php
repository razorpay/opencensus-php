<?php

namespace RZP\Tests\Functional\FileStore;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FileStoreTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/FileStoreTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }
}
