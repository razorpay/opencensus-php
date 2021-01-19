<?php

namespace RZP\Tests\Functional\FundTransfer;

use Mail;
use Queue;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payout;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Batch;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


trait AttemptTrait
{
    use PaymentTrait;
    use SettlementTrait;

    protected function initiateTransfer(string $channel, string $purpose, string $sourceType, string $failureTest = "")
    {
        $content['purpose'] = $purpose;

        $request = [
            'url'       => '/fund_transfer_attempts/initiate/' . $channel,
            'method'    => 'POST',
            'content'   =>  [
                Attempt\Entity::PURPOSE     => $purpose,
                Attempt\Entity::SOURCE_TYPE => $sourceType,
                'failed_response'           => $failureTest,
            ]
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function assertInitiateTransferResponseSuccess(string $channel, array $content, int $sourceCount)
    {
        $this->assertArrayHasKey($channel, $content);

        $count = $content[$channel]['count'];

        $this->assertEquals($sourceCount, $count);

        if($count > 0)
        {
            if (in_array($channel, Channel::getFileBasedChannels(), true) === true)
            {
                $this->assertArrayHasKey('file', $content[$channel]);
                $this->assertNotNull($content[$channel]['file']['local_file_path']);
            }
            else if (in_array($channel, Channel::getApiBasedChannels(), true) === true)
            {
                $this->assertEquals($sourceCount, $content[$channel]['success']);
                $this->assertEquals(0, $content[$channel]['failed']);
            }
        }
    }

    protected function initiateTransferViaFileAndAssertSuccess(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        $content = $this->initiateTransfer($channel, $purpose, $sourceType);

        $this->assertInitiateTransferResponseSuccess($channel, $content, $sourceCount);

        if ($sourceCount > 0)
        {
            $this->assertEntitiesAfterInitiateTransfer($channel, $purpose, $sourceType, $sourceCount);
        }

        return $content;
    }

    protected function initiateTransferAndAssertSuccess(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        $content = $this->initiateTransfer($channel, $purpose, $sourceType);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);

        return $content;
    }

    protected function createDataAndAssertInitiateTransferResponse(
        string $channel, string $purpose, int $setlCount, string $sourceType)
    {
        $this->createDataForChannel($channel, $purpose, $setlCount, $sourceType);

        $content = $this->initiateTransfer($channel, $purpose, $sourceType);

        $this->assertInitiateTransferResponseSuccess($channel, $content, $setlCount);

        return $content;
    }

    protected function createDataAndAssertInitiateOnlineTransferResponse(
        string $channel, string $purpose, int $setlCount, string $sourceType, $failureTest)
    {
        $this->createDataForChannel($channel, $purpose, $setlCount, $sourceType);

        $this->initiateTransfer($channel, $purpose, $sourceType, $failureTest);
    }

    protected function createDataAndAssertInitiateOnlineTransferResponseForVpa(
        string $channel, string $purpose, int $setlCount, string $sourceType, bool $failureTest)
    {
        $this->createDataForChannelForVpa($channel, $purpose, $setlCount, $sourceType);

        $this->initiateTransfer($channel, $purpose, $sourceType, $failureTest);
    }

    protected function createDataAndAssertInitiateTransferSuccess(string $channel, int $setlCount, string $sourceType)
    {
        Mail::fake();

        $purpose = Attempt\Purpose::SETTLEMENT;

        $content = $this->createDataAndAssertInitiateTransferResponse(
            $channel, $purpose, $setlCount, $sourceType);

        $this->assertEntitiesAfterInitiateTransfer($channel, $purpose, $sourceType, $setlCount);

        $setl = $this->getLastEntity('settlement', true);

        if (empty($setl) === false)
        {
            $bta = $this->getLastEntity('fund_transfer_attempt', true);

            $this->validationSettlementDestination($setl['id'], Entity::FUND_TRANSFER_ATTEMPT, $bta['id']);
        }

        return $content;
    }

    protected function createDataAndAssertInitiateOnlineTransferSuccess(string $channel, int $setlCount, string $sourceType, bool $failureTest)
    {
        $purpose = Attempt\Purpose::SETTLEMENT;

        $this->createDataAndAssertInitiateOnlineTransferResponse($channel, $purpose, $setlCount, $sourceType, $failureTest);

        $this->assertEntitiesAfterInitiateOnlineTransfer($channel, $purpose, $sourceType, $setlCount, $failureTest);
    }

    protected function createDataAndAssertInitiateOnlineTransferSuccessForVpa(string $channel, int $setlCount, string $sourceType, bool $failureTest)
    {
        $purpose = Attempt\Purpose::SETTLEMENT;

        $this->createDataAndAssertInitiateOnlineTransferResponseForVpa($channel, $purpose, $setlCount, $sourceType, $failureTest);

        $this->assertEntitiesAfterInitiateOnlineTransfer($channel, $purpose, $sourceType, $setlCount, $failureTest);
    }

    protected function assertEntitiesAfterInitiateTransfer(
        string $channel, string $purpose, string $sourceType, int $sourceCount)
    {
        $isFileBased = in_array($channel, Channel::getFileBasedChannels(), true);
        // Verify Batch
        $batch = $this->getLastEntity(Entity::BATCH_FUND_TRANSFER, true);

        $suffix = ($isFileBased === true) ? '' : 'Api';

        $batchTestData = 'testFileCreation' . ucfirst($sourceType). $suffix;
        $this->assertTestResponse($batch, $batchTestData);

        $this->assertEquals($channel, $batch[Batch\Entity::CHANNEL]);

        if ($isFileBased === true)
        {
            $this->assertNotNull($batch['urls']['file']);
            $this->assertNotNull($batch[Batch\Entity::TXT_FILE_ID]);

            $mailClass = 'RZP\\Mail\\Settlement\\Settlement';

            Mail::assertQueued($mailClass);
        }

        // Verify settlement entity
        $sourceEntities = $this->getEntities($sourceType, ['count' => $sourceCount], true);

        foreach ($sourceEntities['items'] as $source)
        {
            $this->assertEquals($batch['id'], $source['batch_fund_transfer_id']);

            $expectedStatus = Attempt\Status::INITIATED;

            if ($sourceType === Entity::PAYOUT)
            {
                $expectedStatus = ($isFileBased === false) ? Payout\Status::PROCESSED : Payout\Status::PROCESSING;
            }

            $this->assertEquals($expectedStatus, $source['status']);
        }


        $expectedStatus = Attempt\Status::INITIATED;

        if (in_array($channel, Channel::getApiBasedChannels(), true) === true)
        {
            $expectedStatus = Attempt\Status::PROCESSED;
        }
        // Verify FTA
        $ftas = $this->getEntities('fund_transfer_attempt', ['count' => $sourceCount], true);
        foreach ($ftas['items'] as $fta)
        {
            $this->assertEquals($batch['id'], $fta['batch_fund_transfer_id']);
            $this->assertEquals($expectedStatus, $fta[Attempt\Entity::STATUS]);
        }
    }

    protected function assertEntitiesAfterInitiateOnlineTransfer(
        string $channel, string $purpose, string $sourceType, int $sourceCount, bool $failureTest = false)
    {
        // Verify Batch
        $batch = $this->getLastEntity(Entity::BATCH_FUND_TRANSFER, true);

        $attempt = $this->getLastEntity(Entity::FUND_TRANSFER_ATTEMPT, true);

        $suffix = ($failureTest === true) ? 'Failure' : '';

        $batchTestData = 'testFileCreation' . ucfirst($sourceType) . 'Api' . $suffix;

        // for VPA recon happens instantly
        if (empty($attempt['vpa_id']) === false)
        {
            $batchTestData = 'testFileCreation' . ucfirst($sourceType) . 'Vpa';
        }

        $this->assertTestResponse($batch, $batchTestData);

        $this->assertEquals($channel, $batch[Batch\Entity::CHANNEL]);

        // Verify settlement entity
        $sourceEntities = $this->getEntities($sourceType, ['count' => $sourceCount], true);

        foreach ($sourceEntities['items'] as $source)
        {
            $this->assertEquals($batch['id'], $source['batch_fund_transfer_id']);

            $expectedStatus = ($failureTest === true) ? Attempt\Status::FAILED : Attempt\Status::PROCESSED;

            if ($sourceType === Entity::PAYOUT)
            {
                $expectedStatus = (empty($attempt['vpa_id']) === false) ?
                    Payout\Status::PROCESSED :
                    Payout\Status::PROCESSING;
            }

            $this->assertEquals($expectedStatus, $source['status']);
        }

        // Verify FTA
        $ftas = $this->getEntities('fund_transfer_attempt', ['count' => $sourceCount], true);
        foreach ($ftas['items'] as $fta)
        {
            $expectedStatus = (empty($attempt['vpa_id']) === false) ?
                Payout\Status::PROCESSED:
                (($failureTest === true) ? Attempt\Status::FAILED : Attempt\Status::PROCESSED);

            $this->assertEquals($batch['id'], $fta['batch_fund_transfer_id']);
            $this->assertEquals($expectedStatus, $fta[Attempt\Entity::STATUS]);
        }
    }

    protected function createDataForChannel(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        switch ($sourceType) {
            case Attempt\Type::SETTLEMENT:
                $this->createSettlementData($channel, $purpose, $sourceCount);
                break;

            case Attempt\Type::PAYOUT:
                $this->createPayoutData($channel, $purpose, $sourceCount);
                break;

            default:
                throw new Exception\LogicException('Invalid source type: ' . $sourceType);
        }
    }

    protected function createDataForChannelForVpa(
        string $channel, string $purpose, int $sourceCount, string $sourceType)
    {
        switch ($sourceType) {
            case Attempt\Type::PAYOUT:
                $this->createPayoutDataForVpa($channel, $purpose, $sourceCount);
                break;

            default:
                throw new Exception\LogicException('Invalid source type: ' . $sourceType);
        }
    }

    protected function createPayoutData(string $channel, string $purpose, int $sourceCount)
    {
        $payouts = $this->fixtures->times($sourceCount)->create(
            'payout',
            [
                'channel'           =>      $channel,
                'amount'            =>      1000,
                'balance_id'        =>      '10000000000000',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
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

    protected function createPayoutDataForVpa(string $channel, string $purpose, int $sourceCount)
    {
        $payouts = $this->fixtures->times($sourceCount)->create(
            'payout',
            [
                'channel'           => $channel,
                'amount'            => 1000,
                'destination_id'    => '1000000lcustba',
                'destination_type'  => 'vpa',
                'balance_id'        => '10000000000000',
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
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
                    'vpa_id'                    => $payout->getDestinationId(),
                    'merchant_id'               => $payout->getMerchantId(),
                    'purpose'                   => $purpose,
                    'status'                    => Attempt\Status::CREATED,
                    'source_type'               => Attempt\Type::PAYOUT,
                    'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
                ]);
        }
    }

    protected function createSettlementData(string $channel, string $purpose, int $sourceCount)
    {
        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $payments = $this->createPaymentEntities(2);

        $this->createRefundFromPayments($payments);

        $this->initiateSettlements($channel);
    }

    protected function updateFta( $ftsId, string $sourceId, string $sourceType, string $status)
    {
        $content = [
            Attempt\Entity::STATUS           => $status,
            Attempt\Entity::SOURCE_ID        => $sourceId,
            Attempt\Entity::SOURCE_TYPE      => $sourceType,
            Attempt\Entity::FUND_TRANSFER_ID => $ftsId
        ];


        $request = [
            'url'       => '/update_fts_fund_transfer',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->ba->ftsAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function validationSettlementDestination(string $settlementId, string $destinationType, string $destinationId)
    {
        $input =  [
            'settlement_id' => substr($settlementId, 5),
            'destination_id' => substr($destinationId, 4)
        ];

        $content = $this->getEntities('settlement_destination', $input, true);

        $this->assertNotEmpty($content['items'][0]);

        $this->assertEquals($destinationType, $content['items'][0]['destination_type']);
    }
}
