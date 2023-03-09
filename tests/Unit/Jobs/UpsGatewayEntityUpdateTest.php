<?php

namespace Unit\Jobs;

use RZP\Tests\TestCase;
use RZP\Jobs\UpsRecon\UpsGatewayEntityUpdate;

class UpsGatewayEntityUpdateTest extends TestCase
{
    protected $job;

    protected $jobData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = 'live';

        $this->jobData = $this->buildUpsGatewayEntityUpdateData();
    }

    public function testInvalidGatewayEntityUpdate()
    {
        $this->app['rzp.mode'] = 'live';

        unset($this->jobData['gateway']);

        $this->job = $this->mockUpsGatewayEntityUpdate($this->jobData);

        $this->job->handle();

        // Asserting the attempts of the job ,when the job processing fails for request validation error
        $this->assertEquals($this->job->attempts(),1);
    }

    private function mockUpsGatewayEntityUpdate(array $data)
    {
        $job = \Mockery::mock(UpsGatewayEntityUpdate::class, [$this->app['rzp.mode'], $data])->makePartial();

        $job->shouldAllowMockingProtectedMethods();

        return $job;
    }

    private function buildUpsGatewayEntityUpdateData()
    {
        return [
            'payment_id'              => 'JvGbfizowfftSE',
            'gateway'                 => 'upi_sbi',
            'model'                   => 'authorize',
            'gateway_data'            => [
                'gateway_reference'        => '112234134566',
                'customer_reference'       => '312234134566',
                'npci_txn_id'              => '112234134566',
                'reconciled_at'            => '',
            ],
        ];
    }

}
