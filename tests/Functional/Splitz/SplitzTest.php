<?php

namespace RZP\Tests\Functional\Splitz;

use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class SplitzTest extends TestCase
{
    use MocksSplitz;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/SplitzTestData.php';

        parent::setUp();
    }

    public function testSplitzResponse()
    {
        $input = [
            "experiment_id" => "randomExperiment",
        ];

        $output = [
            "experiment" => [
                "id" => "randomExperiment123"
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->startTest();
    }
}
