<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Transaction\Statement\Entity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIdfcCombinedFileTest extends NbPlusPaymentServiceNetbankingTest{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingIdfcGatewayTestData.php';

        parent::setUp();
    }

    public function testNetbankingIdfcCombinedFile()
    {
        Mail::fake();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_idfc_terminal');

        $this->bank = 'IDFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

        $paymentEntity2 = $this->getDbLastPayment();

        $refundEntity2 = $this->getDbLastRefund();

        $this->assertEquals('refunded', $paymentEntity1['status']);
        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals(3, $paymentEntity1['cps_route']);
        $this->assertEquals(3, $paymentEntity2['cps_route']);
        $this->assertEquals('full', $paymentEntity1['refund_status']);
        $this->assertEquals('partial', $paymentEntity2['refund_status']);
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $tran1 = $this->getDbEntityById('transaction', $transaction1['id']);
        $tran2 = $this->getDbEntityById('transaction', $transaction2['id']);

        $this->assertNotNull($tran1[Entity::RECONCILED_AT]);
        $this->assertNotNull($tran2[Entity::RECONCILED_AT]);
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 3
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 3,
            'items' => [
                [
                    'type' => 'idfc_netbanking_summary',
                ],
                [
                    'type' => 'idfc_netbanking_claims',
                ],
                [
                    'type' => 'idfc_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        $this->checkRefundExcelData($files['items'][2]);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Idfc Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  1000,
                    'refunds' =>  505,
                    'total'   =>  495
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            $this->assertCount(3, $mail->attachments);

            return true;
        });
    }
    protected function checkRefundExcelData(array $data)
    {
        $filePath = storage_path('files/filestore') . '/' . $data['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = (new ExcelImport)->toArray($filePath)[0];

        $refundAmounts = [500, 5];

        array_map(
            function($amount, $index) use ($refundsFileContents)
            {
                // We increment $ind in the local scope so that sr_no = $ind = 1
                $refund = $refundsFileContents[$index];

                $this->assertEquals(++$index, $refund['sr_no']);
                $this->assertEquals(500.00, $refund['txn_amount_rs_ps']);
                $this->assertEquals($amount, $refund['refund_amount_rs_ps']);
            },
            $refundAmounts,
            array_keys($refundAmounts)
        );

        $this->assertEquals(2, count($refundsFileContents));

        unlink($filePath);
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = file($claimData['url']);

        $payment1RowData = explode('|', $claimFileContents[1]);

        $payment2RowData = explode('|', $claimFileContents[2]);

        $this->assertCount(5, $payment1RowData);

        $this->assertCount(5, $payment2RowData);

        $this->assertEquals($payment1['id'], $payment1RowData[0]);

        $this->assertEquals($payment2['id'], $payment2RowData[0]);

        $this->assertEquals("SUCCESS", $payment1RowData[3]);

        $this->assertEquals("SUCCESS", $payment2RowData[3]);
    }

    public function testRefundFileDirectSettlement()
    {
        Mail::fake();

        $this->terminal = $this->fixtures->create('terminal:direct_settlement_idfc_terminal');

        $this->bank = 'IDFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $paymentEntity1 = $this->doAuthPayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($paymentEntity1['razorpay_payment_id']);

        $refundEntity1 = $this->getDbLastRefund();

        $paymentEntity2 = $this->doAuthPayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($paymentEntity2['razorpay_payment_id'], 500);

        $refundEntity2 = $this->getDbLastRefund();

        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $testData = $this->testData['testNetbankingIdfcCombinedFile'];

        $content = $this->runRequestResponseFlow($testData);

        $content = $content['items'][0];

        $tran1 = $this->getDbEntityById('transaction', $transaction1['id']);
        $tran2 = $this->getDbEntityById('transaction', $transaction2['id']);

        $this->assertNotNull($tran1[Entity::RECONCILED_AT]);
        $this->assertNotNull($tran2[Entity::RECONCILED_AT]);
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'idfc_netbanking_summary',
                ],
                [
                    'type' => 'idfc_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        $this->checkRefundExcelData($files['items'][1]);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Idfc Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  0,
                    'refunds' =>  505,
                    'total'   =>  -505,
                ],
                'count' => [
                    'claims'  => 0,
                    'refunds' => 2,
                    'total'   => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }
}
