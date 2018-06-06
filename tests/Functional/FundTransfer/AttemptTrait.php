<?php

namespace RZP\Tests\Functional\FundTransfer;

use Carbon\Carbon;
use Mail;

use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Constants\Entity;
use RZP\Models\FundTransfer\Batch;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

trait AttemptTrait
{
    use PaymentTrait;
    use SettlementTrait;

    protected function initiateTransfer(string $channel, string $purpose, bool $failureTest = false)
    {
        $content['purpose'] = $purpose;

        $request = [
            'url'       => '/fund_transfer_attempts/initiate/'.$channel,
            'method'    => 'POST',
            'content'   => [
                Attempt\Entity::PURPOSE => $purpose,
                'failed_response'       => (int) $failureTest,
            ],
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function assertInitiateTransferResponseSuccess(string $channel, array $content, int $sourceCount)
    {
        $this->assertArrayHasKey($channel, $content);
        $this->assertArrayHasKey('file', $content[$channel]);
        $this->assertNotNull($content[$channel]['file']['local_file_path']);

        $this->assertEquals($sourceCount, $content[$channel]['count']);
    }

    protected function assertInitiateOnlineTransferResponseSuccess(
        string $channel, array $content, int $sourceCount, bool $failureTest)
    {
        $this->assertArrayHasKey($channel, $content);

        $this->assertEquals($sourceCount, $content[$channel]['count']);

        if ($failureTest === false)
        {
            $this->assertEquals($sourceCount, $content[$channel]['success']);
        }
        else
        {
            $this->assertEquals($sourceCount, $content[$channel]['failure']);
        }
    }

    protected function initiateTransferViaFileAndAssertSuccess(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        $content = $this->initiateTransfer($channel, $purpose);

        $this->assertInitiateTransferResponseSuccess($channel, $content, $sourceCount);

        $this->assertEntitiesAfterInitiateTransfer($channel, $purpose, $sourceType, $sourceCount);

        return $content;
    }

    protected function createDataAndAssertInitiateTransferResponse(
        string $channel, string $purpose, int $setlCount, string $sourceType)
    {
        $this->createDataForChannel($channel, $purpose, $setlCount, $sourceType);

        $content = $this->initiateTransfer($channel, $purpose);

        $this->assertInitiateTransferResponseSuccess($channel, $content, $setlCount);

        return $content[$channel]['file']['local_file_path'];
    }

    protected function createDataAndAssertInitiateOnlineTransferResponse(
        string $channel, string $purpose, int $setlCount, string $sourceType, bool $failureTest)
    {
        $this->createDataForChannel($channel, $purpose, $setlCount, $sourceType);

        $content = $this->initiateTransfer($channel, $purpose, $failureTest);

        $this->assertInitiateOnlineTransferResponseSuccess($channel, $content, $setlCount, $failureTest);
    }

    protected function createDataAndAssertInitiateTransferSuccess(string $channel, int $setlCount, string $sourceType)
    {
        Mail::fake();

        $purpose = Attempt\Purpose::SETTLEMENT;

        $content = $this->createDataAndAssertInitiateTransferResponse(
            $channel, $purpose, $setlCount, $sourceType);

        $this->assertEntitiesAfterInitiateTransfer($channel, $purpose, $sourceType, $setlCount);

        $mailClass = 'RZP\\Mail\\Settlement\\Settlement';

        Mail::assertQueued($mailClass);

        return $content;
    }

    protected function createDataAndAssertInitiateOnlineTransferSuccess(string $channel, int $setlCount, string $sourceType, bool $failureTest)
    {
        $purpose = Attempt\Purpose::SETTLEMENT;

        $content = $this->createDataAndAssertInitiateOnlineTransferResponse(
            $channel, $purpose, $setlCount, $sourceType, $failureTest);

        $this->assertEntitiesAfterInitiateOnlineTransfer($channel, $purpose, $sourceType, $setlCount);

        return $content;
    }

    protected function assertEntitiesAfterInitiateTransfer(
        string $channel, string $purpose, string $sourceType, int $sourceCount)
    {
        // Verify Batch
        $batch = $this->getLastEntity(Entity::BATCH_FUND_TRANSFER, true);

        $batchTestData = 'testFileCreation' . ucfirst($sourceType);
        $this->assertTestResponse($batch, $batchTestData);

        $this->assertEquals($channel, $batch[Batch\Entity::CHANNEL]);
        $this->assertNotNull($batch['urls']['file']);
        $this->assertNotNull($batch[Batch\Entity::TXT_FILE_ID]);

        // Verify settlement entity
        $sourceEntities = $this->getEntities($sourceType, ['count' => $sourceCount], true);
        foreach ($sourceEntities['items'] as $source)
        {
            $this->assertEquals($batch['id'], $source['batch_fund_transfer_id']);
            $this->assertEquals(Attempt\Status::INITIATED, $source['status']);
        }

        // Verify FTA
        $ftas = $this->getEntities('fund_transfer_attempt', ['count' => $sourceCount], true);
        foreach ($ftas['items'] as $fta)
        {
            $this->assertEquals($batch['id'], $fta['batch_fund_transfer_id']);
            $this->assertEquals(Attempt\Status::INITIATED, $fta[Attempt\Entity::STATUS]);
        }
    }

    protected function assertEntitiesAfterInitiateOnlineTransfer(
        string $channel, string $purpose, string $sourceType, int $sourceCount)
    {
        // Verify Batch
        $batch = $this->getLastEntity(Entity::BATCH_FUND_TRANSFER, true);

        $batchTestData = 'testFileCreation' . ucfirst($sourceType);
        $this->assertTestResponse($batch, $batchTestData);

        $this->assertEquals($channel, $batch[Batch\Entity::CHANNEL]);

        // Verify settlement entity
        $sourceEntities = $this->getEntities($sourceType, ['count' => $sourceCount], true);

        foreach ($sourceEntities['items'] as $source)
        {
            $this->assertEquals($batch['id'], $source['batch_fund_transfer_id']);
            $this->assertEquals(Attempt\Status::INITIATED, $source['status']);
        }

        // Verify FTA
        $ftas = $this->getEntities('fund_transfer_attempt', ['count' => $sourceCount], true);
        foreach ($ftas['items'] as $fta)
        {
            $this->assertEquals($batch['id'], $fta['batch_fund_transfer_id']);
            $this->assertEquals(Attempt\Status::INITIATED, $fta[Attempt\Entity::STATUS]);
        }
    }

    protected function createDataForChannel(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        switch ($sourceType) {
            case Attempt\Type::SETTLEMENT:
                return $this->createSettlementData($channel, $purpose, $sourceCount);

            case Attempt\Type::PAYOUT:
                return $this->createPayoutData($channel, $purpose, $sourceCount);

            default:
                throw new Exception\LogicException('Invalid source type: ' . $sourceType);
        }
    }

    protected function createPayoutData(string $channel, string $purpose, int $sourceCount)
    {
        $payouts = $this->fixtures->times($sourceCount)->create(
            'payout',
            [
               'channel' => $channel,
                'amount' => 1000,
            ]);

        if ($sourceCount === 1)
        {
            $payouts = [$payouts];
        }

        foreach ($payouts as $payout)
        {
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
        }
    }

    protected function createSettlementData(string $channel, string $purpose, int $sourceCount)
    {
        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $payments = $this->createPaymentEntities(2);

        $this->createRefundFromPayments($payments);

        $this->initiateSettlements($channel);
    }
}
