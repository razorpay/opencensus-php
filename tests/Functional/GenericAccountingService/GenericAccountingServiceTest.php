<?php

namespace Functional\GenericAccountingService;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\Payout\SourceUpdater\GenericAccountingUpdater;

class GenericAccountingServiceTest extends TestCase
{
    use MocksSplitz;


    public function testExperimentEnabled()
    {
        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertFalse($resp);

        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', true);

        $resp = GenericAccountingUpdater::isGAIExperimentEnabled("test_merchant_id");

        $this->assertTrue($resp);
    }

    public function testPayoutStatusPushForPayoutLinkAsSource()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $gaiMock = Mockery::mock('RZP\Services\GenericAccountingIntegration\Service');

        $this->app->instance('accounting-integration-service', $gaiMock);

        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', true);

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

        // If experiment is disabled, gai should not be called
        $this->app['config']->set('app.generic_ai_enabled_experiment_result_mock', false);

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
}
