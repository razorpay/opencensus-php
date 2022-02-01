<?php

namespace RZP\Tests\Unit\CapitalCollections;

use Mockery;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class CapitalCollectionsTest extends TestCase
{
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    public function testPayoutStatusPushForCapitalCollectionsAsSource()
    {
        $collectionsMock = Mockery::mock('RZP\Services\CapitalCollectionsClient');

        $collectionsMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('capital_collections', $collectionsMock);

        $payout = $this->fixtures->create('payout', [
            'status'            =>      'processed',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id'   => $payout->getId(),
            'source_id'   => 'DpwgbG6EDN5bB4',
            'source_type' => 'capital_collections',
            'priority'    => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $collectionsMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testCapitalCollectionsPushSkippedWhenSourceDetailsNotPresent()
    {
        $collectionsMock = Mockery::mock('RZP\Services\CapitalCollectionsClient');

        $collectionsMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('capital_collections', $collectionsMock);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'processed',
            'pricing_rule_id' =>      '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id'   => $payout->getId(),
            'source_id'   => 'vdpm_1',
            'source_type' => 'payout_links',
            'priority'    => 1
        ]);

        SourceUpdater::update($payout);

        // assert that the Payout Update Status was called when feature was enabled
        $collectionsMock->shouldNotHaveReceived('pushPayoutStatusUpdate');
    }
}
