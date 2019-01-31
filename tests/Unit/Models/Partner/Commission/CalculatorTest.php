<?php

namespace Unit\Models\Partner\Commission;

use RZP\Tests\Functional\TestCase;

class CalculatorTest extends TestCase
{
    public function setUp()
    {
//        $this->testDataFilePath = __DIR__ . '/Helpers/CoreTestData.php';

        parent::setUp();

        $this->ruleEngine = new Engine;
    }

}
