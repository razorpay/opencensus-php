<?php

namespace Functional\GenericAccountingService;

use Mockery;
use RZP\Models\Payout\Status;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\PayoutSourceUpdaterJob;
use Illuminate\Support\Facades\Queue;
use RZP\Services\GenericAccountingIntegration\Service;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class GenericAccountingServiceTest extends TestCase
{
    use MocksSplitz;


    public function testPayoutStatusPushForPayoutLinkAsSource()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $gaiMock = Mockery::mock('RZP\Services\GenericAccountingIntegration\Service');

        $this->app->instance('accounting-integration-service', $gaiMock);

        $this->fixtures->merchant->addFeatures(['gai_payouts_sync']);

        // For processed payout, both PL and GAI should be called
        $payout = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'poutlk_1',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout);

        // For reversed payout, both PL and GAI should be called
        $payout2 = $this->fixtures->create('payout', [
            'status' => 'reversed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout2->getId(),
            'source_id' => 'poutlk_2',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout2);

        //For status updates other than processed, reversed GAI should not be called
        $payout3 = $this->fixtures->create('payout', [
            'status' => 'processing'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout3->getId(),
            'source_id' => 'poutlk_3',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout3);

        $this->fixtures->merchant->removeFeatures(['gai_payouts_sync']);

        $payout4 = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout4->getId(),
            'source_id' => 'poutlk_4',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        $plMock->shouldReceive('pushPayoutStatus')->times(1);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout4);
    }

    public function testPayoutStatusPushForVanillaPayouts()
    {
        $gaiMock = Mockery::mock('RZP\Services\GenericAccountingIntegration\Service');

        $this->app->instance('accounting-integration-service', $gaiMock);

        $this->fixtures->merchant->addFeatures(['gai_payouts_sync']);

        $payout = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout);

        $payout2 = $this->fixtures->create('payout', [
            'status' => 'reversed'
        ]);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(1);

        SourceUpdater::update($payout2);

        //For status updates other than processed, reversed GAI should not be called
        $payout3 = $this->fixtures->create('payout', [
            'status' => 'processing'
        ]);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout3);

        $this->fixtures->merchant->removeFeatures(['gai_payouts_sync']);

        $payout4 = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $gaiMock->shouldReceive('pushPayoutStatusUpdate')->times(0);

        SourceUpdater::update($payout4);
    }

    public function testMakePayoutRequestBody()
    {
        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => 'D6Z9Jfir2egAUT',
                'source_type' => 'contact',
                'source_id'   => '1000001contact',
            ]);

        $this->fixtures->create('balance',
            [
                'id'             => 'D6Z9Jfir2egAUX',
                'account_number' => "2224440041626904",
                "balance"        => 100000
            ]
        );

        $payout = $this->fixtures->create('payout', [
            'id'              => 'D6Z9Jfir2egAUZ',
            'status'          => 'processed',
            'fund_account_id' => 'D6Z9Jfir2egAUT',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'reference_id'    => 'reference_id',
            'utr'             => 'utr',
            'user_id'         => 'user_id',
            'mode'            => 'NEFT',
            'narration'       => 'narration',
            'balance_id'      => 'D6Z9Jfir2egAUX'
        ]);

        $response = (new Service($this->app))->makePayoutRequestBody($payout);

        $expectedResponse = [
            'id'                     => 'pout_D6Z9Jfir2egAUZ',
            'reference_id'           => "reference_id",
            'merchant_id'            => '10000000000000',
            'status'                 => 'processed',
            'banking_account_number' => "2224440041626904",
            'fund_account_id'        => 'fa_D6Z9Jfir2egAUT',
            'contact_id'             => 'cont_1000001contact',
            'utr'                    => "utr",
            'amount'                 => 100,
            'user_id'                => "user_id",
            'mode'                   => "NEFT",
            'purpose'                => 'refund',
            'currency'               => 'INR',
            'narration'              => "narration"
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    /**
     * Test can be uncommented
     */
    public function testPayoutSourceUpdaterForVanillaPayout()
    {
        $this->markTestSkipped("Test can be run when queue dispatch assertion is fixed");

        $this->app->instance('rzp.mode', "live");

        Queue::fake([PayoutSourceUpdaterJob::class]);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::INITIATED);

        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);

        // moving to processed, push should not happen as experiment is disbaled
        $payout->setStatus(Status::PROCESSED);

        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'initiated',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::CREATED);

        // Job is pushed for vanilla payouts in created state
        Queue::assertPushed(PayoutSourceUpdaterJob::class, 1);

        // Enabling experiment
        $this->fixtures->merchant->addFeatures(['gai_payouts_sync']);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::INITIATED);

        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);

        $payout->setStatus(Status::PROCESSED);

        // Job pushed for GenericAccountingIntegration
        Queue::assertPushed(PayoutSourceUpdaterJob::class, 1);

        $payout->setStatus(Status::REVERSED);

        // Job pushed for GenericAccountingIntegration
        Queue::assertPushed(PayoutSourceUpdaterJob::class, 1);
    }
}
