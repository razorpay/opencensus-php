<?php

namespace RZP\Tests\Traits;

use Mockery;

use RZP\Services\RazorXClient;

trait MocksRazorx
{
    /**
     * Mock of \RZP\Services\RazorXClient.
     * @var Mockery\MockInterface
     */
    protected $razorxMock;

    /**
     * TODO: There exists multiple copy of this function across files that should consider merging.
     *
     * @param  string $forFeature
     * @param  string $returnTreatment
     * @return void
     */
    protected function mockRazorxTreatmentV2(string $forFeature, string $returnTreatment = 'control')
    {
        if ($this->razorxMock === null)
        {
            $this->razorxMock = Mockery::mock(RazorXClient::class)->makePartial();

            $this->app->instance('razorx', $this->razorxMock);
        }

        $this->razorxMock
            ->shouldReceive('getTreatment')
            ->atLeast()
            ->once()
            ->with(Mockery::any(), $forFeature ?: Mockery::any(), Mockery::any())
            ->andReturn($returnTreatment);

        // Also sets default fallback expectation to avoid brittle tests.
        $this->razorxMock
            ->shouldReceive('getTreatment')
            ->zeroOrMoreTimes()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn('control');
    }
}
