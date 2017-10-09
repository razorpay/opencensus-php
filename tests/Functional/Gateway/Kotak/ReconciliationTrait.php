<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\FileStore\Creator;

trait ReconciliationTrait
{
    protected function createPaymentAndRefundEntities()
    {
        $prEntities = [];

        $r = range(1,5);

        $createdAt = Carbon::today(Timezone::IST)->subDays(20)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(20)->timestamp + 10;

        foreach ($r as $i)
        {
            $payment = $this->fixtures->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

            $attrs = [
                'payment' => $payment,
                'amount' => '100000',
                'created_at' => $createdAt + 20,
                'updated_at' => $createdAt + 20];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);

            array_push($prEntities, $payment);
            array_push($prEntities, $refund);
        }

        return $prEntities;
    }

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

    protected function initiateSettlementsAndAssertSuccess()
    {
        $content = $this->initiateSettlements();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('settlement_text_file', $content['kotak']);
        $this->assertArrayHasKey('settlement_excel_file', $content['kotak']);

        return $content['kotak']['settlement_text_file']['local_file_path'];
    }

    // Fetches and matches batch data for given entity
    protected function fetchAndMatchBatchData(string $entityName)
    {
        $batchFundTransfer = $this->getLastEntity('batch_fund_transfer', true);

        $expectedData = 'fetchAndMatchBatchData' . ucfirst($entityName);

        $this->assertTestResponse($batchFundTransfer, $expectedData);

        $time = time();

        $this->assertGreaterThanOrEqual($batchFundTransfer['initiated_at'], $time);
        $this->assertGreaterThanOrEqual($batchFundTransfer['reconciled_at'], $time);
        $this->assertGreaterThanOrEqual($batchFundTransfer['returned_at'], $time);

        return $batchFundTransfer;
    }

    protected function createSettlementsAndSettlementFile(
        $settlementCount = 2,
        $setlAttemptTimestamp = null): Creator
    {
        $timestamp = $setlAttemptTimestamp ?: Carbon::today(Timezone::IST)->timestamp;

        // Create merchant
        $merchant = $this->fixtures->create('merchant');
        $merchantId = $merchant->getId();

        $bankAccount = $this->fixtures->create('bank_account', ['entity_id' => $merchantId]);

        // Create settlements
        $settlements = $this->fixtures->times($settlementCount)->create(
            'settlement',
            [
                'merchant_id' => $merchantId,
                'utr' => null,
                'created_at' => $timestamp,
            ]);

        // Create batch of settlement
        $batchTransferEntity = $this->fixtures->create(
            'batch_fund_transfer',
            [
                'total_count' => 1,
                'transaction_count' => $settlementCount,
                'created_at' => $timestamp
            ]);

        // Create fund transfer attempts
        $textData = $allAttempts = [];

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
                    'source_id'                 => $settlement->getId(),
                    'created_at'                => $timestamp,
                    'batch_fund_transfer_id'    => $batchTransferEntity->getId(),
                    'merchant_id'               => $merchantId,
                    'status'                    => AttemptStatus::INITIATED,
                ]
            );

            $allAttempts[] = $fta;
        }

        list($textFile, $excelFile) = (new Kotak\NodalAccount)->generateSettlementFile(
                                                                        $allAttempts, false);

        // Update batch with generated settlement file id
        $batchTransferEntity->setTxtFileId(($textFile->get())['id']);

        $this->fixtures->edit(
            'batch_fund_transfer',
            $batchTransferEntity->getId(),
            ['txt_file_id' => ($textFile->get())['id']]
        );

        return $textFile;
    }

    protected function checkAdjustmentCreated()
    {
        $setl = $this->getLastEntity('settlement', true);
        $settlementSign = 'setl_';
        $setlId = substr($setl['id'], strlen($settlementSign));

        $data = [
            'merchant_id' => "10000000000000",
            'amount' => 4385000,
            'currency' => "INR",
            'channel' => "kotak",
            'description' => "Adjustment for failed settlement",
            'settlement_id' => $setlId
        ];

        $content = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($data, $content);
    }

}
