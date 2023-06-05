<?php

namespace RZP\Tests\Unit\CapitalCollections;

use Mockery;

use RZP\Constants\Mode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Ondemand\Service as Service;
use RZP\Models\Reversal\Core as Reversal;
use RZP\Models\Settlement\OndemandPayout;
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

    public function testPushInstantSettlementLedgerUpdateSuccessForReversal()
    {
        $collectionsMock = Mockery::mock('RZP\Services\CapitalCollectionsClient');

        $collectionsMock->shouldReceive('pushInstantSettlementLedgerUpdateForReversalScenario');

        $this->app->instance('capital_collections', $collectionsMock);

        $ondemandSettlementPayout = $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_payout',[
            'merchant_id'                 => '10000000000000',
            'amount'                      => 475857,
            'settlement_ondemand_id'      =>'KQ8VzkjC27pS3v',
            'status'                      =>'created'
        ]);

        (new Reversal) -> updateLedgerEntryToCollectionsForReversal( true,$ondemandSettlementPayout,'','');
        // assert that the Payout Update Status was called when feature was enabled
        $collectionsMock->shouldHaveReceived('pushInstantSettlementLedgerUpdateForReversalScenario');
    }

    public function testPushInstantSettlementLedgerUpdateSuccess()
    {
        $collectionsMock = Mockery::mock('RZP\Services\CapitalCollectionsClient');

        $collectionsMock->shouldReceive('pushInstantSettlementLedgerUpdate');

        $this->app->instance('capital_collections', $collectionsMock);

        $ondemandSettlement = $this->fixtures->create('settlement.ondemand', [
            'amount'        => 10000,
            'total_fees'    => 0,
            'total_tax'     => 0,
            'currency'      => "INR",
        ]);

        (new Service) -> updateLedgerEntryToCollections($ondemandSettlement, false);
        // assert that the Payout Update Status was called when feature was enabled
        $collectionsMock->shouldHaveReceived('pushInstantSettlementLedgerUpdate');
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
