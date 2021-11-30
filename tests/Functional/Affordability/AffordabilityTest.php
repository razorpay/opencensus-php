<?php

namespace RZP\Tests\Functional\Affordability;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AffordabilityTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AffordabilityTestData.php';

        parent::setUp();

        $this->ba->affordabilityInternalAppAuth();

//        $this->fixtures->merchant->enablePayLater('10000000000000');
    }
}
