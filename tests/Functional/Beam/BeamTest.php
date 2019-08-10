<?php

namespace RZP\Tests\Functional\Beam;

use Queue;
use RZP\Jobs\BeamJob;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BeamTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function testbeamPushForIcici()
    {
        Queue::fake();

        $this->fixtures->create('bank_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'submitted'   => true,
                'locked'      => true
            ]);

        $request = [
            'method'    => 'POST',
            'url'       => '/merchants/beneficiary/file/icici',
            'content'   => [
                "merchant_ids" => [
                    "10000000000000"
                ]
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }
}
