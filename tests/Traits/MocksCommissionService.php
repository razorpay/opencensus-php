<?php


namespace RZP\Tests\Traits;


use Mockery;
use RZP\Services\CommissionService\CommissionService;

trait MocksCommissionService
{

    protected $commissionServiceMock;

    protected function mockCommissionServiceTreatment($input = [], $output = [], $methodName)
    {
        if ($this->commissionServiceMock === null)
        {
            $this->commissionServiceMock = Mockery::mock(CommissionService::class)->makePartial();

            $this->app->instance('commissionService', $this->CommissionServiceMock);
        }

        $mock = $this->commissionServiceMock
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
