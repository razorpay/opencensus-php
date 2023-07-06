<?php

namespace RZP\Tests\Traits;

use Mockery;
use RZP\Services\SplitzService;

trait MocksSplitz
{

    protected $splitzMock;

    protected function mockSplitzTreatment($input = [], $output = [])
    {
        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->atLeast()
                    ->once()
                    ->with($input)
                    ->andReturn($output);
    }

    protected function mockAllSplitzTreatment($output = [
        "response" => [
            "variant" => [
                "name" => 'enable',
            ]
        ]
    ])
    {
        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->andReturn($output);
    }

    protected function mockSplitzExperiment($output)
    {
        $this->getSplitzMock()
            ->shouldReceive('evaluateRequest')
            ->byDefault()
            ->andReturn($output);
    }

    protected function mockAllSplitzResponseDisable($output = [
        "response" => [
            "variant" => [
                "name" => 'disable',
            ]
        ]
    ])
    {
        return $this->getSplitzMock()
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function getSplitzMock()
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = Mockery::mock(SplitzService::class, [$this->app])->makePartial();

            $this->app->instance('splitzService', $this->splitzMock);
        }

        return $this->splitzMock;
    }
}
