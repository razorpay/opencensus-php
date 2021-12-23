<?php


namespace RZP\Tests\Traits;


use Mockery;
use RZP\Services\GrowthService;

trait MocksGrowth
{

    protected $growthMock;

    protected function mockGrowthTreatment($input = [], $output = [], $methodName)
    {
        if ($this->growthMock === null)
        {
            $this->growthMock = Mockery::mock(GrowthService::class)->makePartial();

            $this->app->instance('growthService', $this->growthMock);
        }
        
        $mock = $this->growthMock
            ->shouldReceive($methodName)
            ->atLeast()
            ->once();
        if(!empty($input))
        {
            $mock->with($input);
        }

        $mock->andReturn($output);
    }

}
