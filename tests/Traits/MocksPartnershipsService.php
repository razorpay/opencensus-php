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
            $this->partnershipsServiceMock = Mockery::mock('RZP\Services\Partnerships\PartnershipsService', $this->app)->makePartial();

            $this->app['partnerships'] = $this->partnershipsServiceMock;
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
