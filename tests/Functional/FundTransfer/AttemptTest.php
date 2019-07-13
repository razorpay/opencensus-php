<?php

namespace RZP\Tests\Functional\FundTransfer;

use Queue;
use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Account;
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
        Queue::fake();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::SETTLEMENT);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('general_test', BeamJob::class);
    }

    public function testSettlementFileCreationKotak()
    {
        $this->markTestSkipped('Kotak is not used anymore');

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::KOTAK, 1, Attempt\Type::SETTLEMENT);
    }

    public function testSettlementFileCreationAxis()
    {
        Queue::fake();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::SETTLEMENT);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('general_test', BeamJob::class);
    }

    public function testInitiateAtCheckDuringFileCreation()
    {
        $channel = Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createPaymentEntities(2);

        $this->initiateSettlements($channel);

        $purpose = Attempt\Purpose::SETTLEMENT;

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $initiate_at = $fta[Attempt\Entity::INITIATE_AT];

        // Set current time to a value before initiate_at
        $beforeInitiateTime = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->subDay()->hour(10);

        Carbon::setTestNow($beforeInitiateTime);

        $content = $this->initiateTransfer($channel, $purpose);

        // Check that no attempts were picked up
        $this->assertEquals(0, $content[$channel]['count']);
        $this->assertEquals('No Attempts to process', $content[$channel]['message']);

        // Set initiate_at to a value post the value in the column
        $postInitiateAt = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->addSecond()->hour(10);

        Carbon::setTestNow($postInitiateAt);

        // Verify that attempts were picked up
        $content = $this->initiateTransfer($channel, $purpose);

        $this->assertEquals(1, $content[$channel]['count']);
    }

    public function testPayoutFileCreationAxisSuccess()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationAxisFail()
    {
        $this->markTestSkipped('test mode overrides transfer time check');

        $channel = Channel::AXIS;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 14, 6, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $content = $this->initiateTransfer($channel, $purpose, false);

        $this->assertEquals($channel, $content['channel']);
        $this->assertEquals(0, $content['count']);
        $this->assertEquals('Invalid time to initiate transfer', $content['message']);
    }

    public function testPayoutFileCreationIciciSuccess()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationIciciFail()
    {
        $this->markTestSkipped('test mode overrides transfer time check');

        $channel = Channel::ICICI;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $content = $this->initiateTransfer($channel, $purpose, false);

        $this->assertEquals($channel, $content['channel']);
        $this->assertEquals(0, $content['count']);
        $this->assertEquals('Invalid time to initiate transfer', $content['message']);
    }

    public function testPayoutFileCreationYesbankImps()
    {
        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::YESBANK, 2, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationYesbankRtgsSuccess()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::YESBANK, 2, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationYesbankRtgsFailed()
    {
        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 30000000,
            ]);


        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, false);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(0, $content[$channel]['success']);
        $this->assertEquals(1, $content[$channel]['failed']);
    }

    public function testYesbankRefundToCreditCard()
    {
        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::REFUND;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payment = $this->fixtures->create('payment');

        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment->getId(),
                'merchant_id' => Account::TEST_ACCOUNT,
                'amount'      => $payment->getAmount(),
                'base_amount' => $payment->getAmount(),
                'gateway'     => 'upi_axis',
            ]);

        $card = $this->fixtures->create('card', ['type' => 'credit']);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $refund->getId(),
                'card_id'                   => $card->getId(),
                'merchant_id'               => $refund->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::REFUND,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, false);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(1, $content[$channel]['success']);
        $this->assertEquals(0, $content[$channel]['failed']);
        $this->assertEquals(Attempt\Status::INITIATED, $fta['status']);
    }

    public function testYesbankRefundToInvalidCard()
    {
        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::REFUND;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('iin',411111, ['type' => 'debit']);

        $card = $this->fixtures->create('card');

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payment = $this->fixtures->create('payment');

        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment->getId(),
                'merchant_id' => Account::TEST_ACCOUNT,
                'amount'      => $payment->getAmount(),
                'base_amount' => $payment->getAmount(),
                'gateway'     => 'upi_axis',
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $refund->getId(),
                'card_id'                   => $card->getId(),
                'merchant_id'               => $refund->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::REFUND,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, false);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(0, $content[$channel]['success']);
        $this->assertEquals(1, $content[$channel]['failed']);
        $this->assertEquals(Attempt\Status::CREATED, $fta['status']);
    }

    public function testRblSettlementWithInvalidSourceType()
    {
        $channel = Channel::RBL;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $sourceType = Attempt\Type::SETTLEMENT;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $content = $this->initiateTransfer($channel, $purpose, false, $sourceType);

        $this->assertEquals($channel, $content['channel']);
        $this->assertEquals(0, $content['count']);
    }


    public function testPayoutStatusWithFtaUpdateViaFts()
    {
        $channel = Channel::RBL;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
                'status' => Payout\Status::INITIATED
            ]);

        $fta = $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => Attempt\Purpose::SETTLEMENT,
                'status'                    => Attempt\Status::INITIATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
                'fts_transfer_id'           => 1,
                'is_fts'                    => true
            ]
        );

        $response = $this->updateFta(1, $payout->getId(),Attempt\Type::PAYOUT);

        $processedFTA = $this->getEntityById('fund_transfer_attempt', $fta->getId(), true);

        $processedPayout = $this->getEntityById('payout', $processedFTA['source'], true);

        $this->assertEquals(Attempt\Status::REVERSED, $processedFTA['status']);

        //TODO: Update the status to reversed once payout module handles it
        $this->assertEquals(Payout\Status::PROCESSING, $processedPayout['status']);

        $this->assertEquals('FTA and source updated succesfully', $response['message']);
    }
}
