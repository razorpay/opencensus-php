<?php

namespace RZP\Tests\Traits;

use Mockery;
use RZP\Services\SplitzService;

trait MocksSplitz
{

    protected $splitzMock;

    protected function mockSplitzTreatment($input = [], $output = [])
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

            $this->app->instance('splitzService', $this->splitzMock);
        }

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->atLeast()
            ->once()
            ->with($input)
            ->andReturn($output);
    }
}
