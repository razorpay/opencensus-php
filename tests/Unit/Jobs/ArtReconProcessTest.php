<?php

namespace Unit\Jobs;

use RZP\Tests\TestCase;
use RZP\Jobs\ArtReconProcess;
use RZP\Services\Mock\Scrooge;

class ArtReconProcessTest extends TestCase
{
    protected $job;

    protected $jobData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobData = $this->buildRefundReconData();

        $this->job = $this->mockArtReconProcess($this->jobData);
    }

    public function testRefundEntityUpdate()
    {
        $this->app['rzp.mode'] = 'live';

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefundRecon'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app['scrooge']->method('initiateRefundRecon')
            ->will($this->returnCallback(
                function ($input) {
                    $refunds[] = [
                        'arn'                => '786355626788',
                        'gateway_keys'       => [
                            'arn'           => '12345678910',
                            'recon_batch_id'=> $input['batch_id']
                        ],
                        'reconciled_at'      => 1549108187,
                        'refund_id'          => 'JvGbfizowfftSE',
                        'status'             => 'processed',
                        'gateway_settled_at' => 1549108189,
                    ];

                    $response = [
                        'body' => [
                            'response'              => [
                                'batch_id'                => $input['batch_id'],
                                'chunk_number'            => $input['chunk_number'],
                                'refunds'                 => $refunds,
                                'should_force_update_arn' => $input['should_force_update_arn'],
                                'source'                  => 'art',
                            ]
                        ]
                    ];

                    return $response;
                }

            ));

        $this->job->handle();
    }

    private function mockArtReconProcess(array $data)
    {
        $job = \Mockery::mock(ArtReconProcess::class, [$data])->makePartial();

        $job->shouldAllowMockingProtectedMethods();

        return $job;
    }

    private function buildRefundReconData()
    {
        return [
            'mode'                     => 'live',
            'should_force_update_arn'  => false,
            'source'                   => 'art',
            'gateway'                  => 'upi_icici',
            'art_request_id'           => '112234134566',
            'refunds'                  => [
                'refund_id'         => 'JvGbfizowfftSE',
                'status'            => 'processed',
                'gateway_keys'      => [],
                'arn'               => '786355621232',
                'gateway_settled_at'=> null,
                'reconciled_at'     => null,
            ],
        ];
    }

}
