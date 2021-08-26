<?php


namespace RZP\Tests\Traits;


use Mockery;
use RZP\Services\GrowthService;

trait MocksGrowth
{

    protected $growthMock;

    protected function mockGrowthTreatment($input = [], $output = [])
    {
        if ($this->growthMock === null)
        {
            $this->growthMock = Mockery::mock(GrowthService::class)->makePartial();

            $this->app->instance('growthService', $this->growthMock);
        }

        $this->growthMock
            ->shouldReceive('getAssetDetails')
            ->atLeast()
            ->once()
            ->with($input)
            ->andReturn($output);
    }

}
