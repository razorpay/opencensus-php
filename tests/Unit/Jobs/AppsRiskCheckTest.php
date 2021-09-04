<?php

namespace Unit\Jobs;

use RZP\Tests\TestCase;
use RZP\Jobs\AppsRiskCheck;

class AppsRiskCheckTest extends TestCase
{
    /**
     * @group profanity_check
     * @dataProvider dataProvider
     * @param array $methods
     * @param array $checks
     */
    public function testShouldTriggerMethod(array $methods, array $checks)
    {
        $job = $this->mockAppRiskCheck("live", [
            "checks" => $checks,
            "entity_id" => "1"
        ]);

        foreach ($methods as $method) {
            $job->shouldReceive($method)
                ->once()
                ->andReturn([]);
        }

        $job->handle();
    }

    public function dataProvider(): array
    {
        return [
            [['handleRiskFactor'], ['risk_factor']],
            [['handleProfanityCheck'], ['profanity_check']],
            [['handleRiskFactor', 'handleProfanityCheck'], ['profanity_check', 'risk_factor']],
        ];
    }

    private function mockAppRiskCheck($mode, $params)
    {
        $job = \Mockery::mock(AppsRiskCheck::class, [$mode, $params])->makePartial();

        $job->shouldAllowMockingProtectedMethods();

        return $job;
    }
}
