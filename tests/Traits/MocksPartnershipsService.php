<?php


namespace RZP\Tests\Traits;


use Mockery;
use RZP\Services\Partnerships\PartnershipsService;

trait MocksPartnershipsService
{

    protected $partnershipsServiceMock;

    protected function mockPartnershipsServiceTreatment($input = [], $output = [], $methodName)
    {
        if ($this->partnershipsServiceMock === null)
        {
            $this->partnershipsServiceMock = Mockery::mock(PartnershipsService::class)->makePartial();

            $this->app->instance('partnershipsService', $this->partnershipsServiceMock);
        }

        $mock = $this->partnershipsServiceMock
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
