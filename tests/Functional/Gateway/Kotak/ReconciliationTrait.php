<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\FundTransfer\Kotak;
use RZP\Models\FileStore\Creator;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;

trait ReconciliationTrait
{
    protected function createPayoutEntities()
    {
        $prEntities = array();

        $r = range(1,5);

        $createdAt = Carbon::today(Timezone::IST)->subDays(4)->timestamp + 5;

        foreach ($r as $i)
        {
            $payout = $this->fixtures->create('payout',
                [
                    'amount' => 1000,
                    'created_at' => $createdAt
                ]);

            array_push($prEntities, $payout);
        }

        return $prEntities;
    }

    protected function matchTransactions($prEntities)
    {
        $count = count($prEntities);

        $testData = [
            'request' => [
                'url' => '/transactions',
                'method' => 'GET',
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => $count,
                    'items' => [],
                ]
            ]
        ];

        $txns = [];
        foreach ($prEntities as $prEntity)
        {
            $txns[] = [
                'entity' => 'transaction',
                'amount' => $prEntity->getAmount(),
                'currency' => 'INR',
                'debit' => 0,
                'entity_id' => $prEntity->getPublicId(),
                'type' => $prEntity->getEntity()
            ];

            // array_push($txns, $txn);
        }

        $testData['response']['items'] = $txns;

        $this->ba->proxyAuth();

        $content = $this->runRequestResponseFlow($testData);

        return $content;
    }

    protected function createSettlementsAndSettlementFile(
        $settlementCount = 2,
        $setlAttemptTimestamp = null): Creator
    {
        $timestamp = $setlAttemptTimestamp ?: Carbon::today(Timezone::IST)->timestamp;

        // Create merchant
        $merchant = $this->fixtures->create('merchant');
        $merchantId = $merchant->getId();

        $this->fixtures->create('bank_account', ['entity_id' => $merchantId]);

        // Create settlements
        $settlements = $this->fixtures->times($settlementCount)->create(
            'settlement',
            [
                'merchant_id'       => $merchantId,
                'bank_account_id'   => $merchant->bankAccount->getId(),
                'utr'               => null,
                'created_at'        => $timestamp,
            ]);

        // Create batch of settlement
        $batchTransferEntity = $this->fixtures->create(
            'batch_fund_transfer',
            [
                'total_count'       => 1,
                'transaction_count' => $settlementCount,
                'created_at'        => $timestamp
            ]);

        // Create fund transfer attempts
        $allAttempts = [];

        foreach ($settlements as $settlement)
        {
            // Create transaction
            $transaction = $this->fixtures->create(
                                'transaction',
                                [
                                    'merchant_id'   => $merchantId,
                                    'type'          => 'settlement',
                                    'entity_id'     => $settlement->getId()
                                ]);

            $this->fixtures->edit('settlement', $settlement->getId(), ['transaction_id' => $transaction->getId()]);

            $fta = $this->fixtures->create(
                'fund_transfer_attempt',
                [
                    'channel'                   => Channel::KOTAK,
                    'source_id'                 => $settlement->getId(),
                    'created_at'                => $timestamp,
                    'bank_account_id'           => $settlement->bankAccount->getId(),
                    'batch_fund_transfer_id'    => $batchTransferEntity->getId(),
                    'merchant_id'               => $merchantId,
                    'purpose'                   => 'settlement',
                    'status'                    => AttemptStatus::INITIATED,
                    'bank_status_code'          => 'P',
                ]
            );

            $allAttempts[] = $fta;
        }

        list($textFile, $excelFile) = (new Kotak\NodalAccount)->generateSettlementFile(
                                                                        $allAttempts, false);

        $this->fixtures->edit(
            'batch_fund_transfer',
            $batchTransferEntity->getId(),
            ['txt_file_id' => ($textFile->get())['id']]
        );

        return $textFile;
    }
}
