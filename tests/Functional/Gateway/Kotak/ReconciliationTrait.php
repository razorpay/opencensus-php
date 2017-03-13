<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use Carbon\Carbon;

trait ReconciliationTrait
{
    protected function createPaymentAndRefundEntities()
    {
        $prEntities = [];

        $r = range(1,5);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(20)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(20)->timestamp + 10;

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

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(4)->timestamp + 5;

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

    protected function initiatePayoutsAndAssertSuccess()
    {
        $content = $this->initiatePayouts();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('payout_text_file', $content['kotak']);

        return $content['kotak']['payout_text_file'];
    }

    protected function initiateSettlementsAndAssertSuccess()
    {
        $content = $this->initiateSettlements();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('settlement_text_file', $content['kotak']);
        $this->assertArrayHasKey('settlement_excel_file', $content['kotak']);

        return $content['kotak']['settlement_text_file'];
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