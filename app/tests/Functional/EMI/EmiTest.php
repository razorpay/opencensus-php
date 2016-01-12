<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;

class EmiTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EMITestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

}