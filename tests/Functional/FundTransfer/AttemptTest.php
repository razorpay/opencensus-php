<?php

namespace RZP\Tests\Functional\FundTransfer;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;

class AttemptTest extends TestCase
{
    use AttemptTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AttemptTestData.php';

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testSettlementFileCreationIcici()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::SETTLEMENT);
    }

    public function testSettlementFileCreationKotak()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::KOTAK, 1, Attempt\Type::SETTLEMENT);
    }

    public function testSettlementFileCreationAxis()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::SETTLEMENT);
    }

    public function testInitiateAtCheckDuringFileCreation()
    {
        $channel = Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->createPaymentEntities(2);

        $today = Carbon::today(Timezone::IST);

        Carbon::setTestNow($today);

        $this->initiateSettlements($channel);

        $purpose = Attempt\Purpose::SETTLEMENT;

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $initiate_at = $fta[Attempt\Entity::INITIATE_AT];

        // Set current time to a value before initiate_at
        $beforeInitiateTime = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->subDay();

        Carbon::setTestNow($beforeInitiateTime);

        $content = $this->initiateTransfer($channel, $purpose);

        // Check that no attempts were picked up
        $this->assertEquals(0, $content[$channel]['count']);
        $this->assertEquals('No Attempts to process', $content[$channel]['message']);

        // Set initiate_at to a value post the value in the column
        $postInitiateAt = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->addSecond();

        Carbon::setTestNow($postInitiateAt);

        // Verify that attempts were picked up
        $content = $this->initiateTransfer($channel, $purpose);

        $this->assertEquals(1, $content[$channel]['count']);
    }

    public function testPayoutFileCreationAxis()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationIcici()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::PAYOUT);
    }
}
