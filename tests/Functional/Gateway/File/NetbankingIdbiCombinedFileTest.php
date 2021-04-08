<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Excel\Import as ExcelImport;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingIdbiCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIdbiCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'IBKL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_idbi_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingIdbiCombinedFile()
    {
        Mail::fake();

        $payment = $this->payment;

        $this->doAuthAndCapturePayment($payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

        $refundTransaction2 = $this->getLastEntity('transaction', true);

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

        $refundTran1 = $this->getDbEntityById('transaction', $refundTransaction1['id']);
        $refundTran2 = $this->getDbEntityById('transaction', $refundTransaction2['id']);

        $this->assertNotNull($refundTran1['reconciled_at']);
        $this->assertNotNull($refundTran2['reconciled_at']);
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getEntities(ConstantsEntity::FILE_STORE, ['count' => 1], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'idbi_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Idbi Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  "1000.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "495.00"
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $paymentEntity2,
                $refundEntity2
            );

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment1, $refund1, $payment2, $refund2)
    {
        $this->assertTrue(file_exists($refundFileData['url']));

        $refundsFileContents = (new ExcelImport)->toArray($refundFileData['url'])[0];

        $this->assertCount(2, $refundsFileContents);

        $fullRefundRowData = $refundsFileContents[0];

        $this->assertCount(12, $fullRefundRowData);

        $paymentAmount = $this->getFormattedAmount($payment1['amount']);
        $refundAmount  = $this->getFormattedAmount($refund1['amount']);

        $this->assertEquals($payment1['id'], $fullRefundRowData['pgi_reference_no']);
        $this->assertEquals($paymentAmount, $fullRefundRowData['txn_amount_rs_ps']);
        $this->assertEquals($refund1['id'], $fullRefundRowData['merchnat_refund_id']);
        $this->assertEquals($refundAmount, $fullRefundRowData['refund_amount_rs_ps']);
        $this->assertNotNull($fullRefundRowData['bank_ref_no']);

        $partialRefundRowData = $refundsFileContents[1];

        $this->assertCount(12, $partialRefundRowData);

        $paymentAmount = $this->getFormattedAmount($payment2['amount']);
        $refundAmount  = $this->getFormattedAmount($refund2['amount']);

        $this->assertEquals($payment2['id'], $partialRefundRowData['pgi_reference_no']);
        $this->assertEquals($paymentAmount, $partialRefundRowData['txn_amount_rs_ps']);
        $this->assertEquals($refund2['id'], $partialRefundRowData['merchnat_refund_id']);
        $this->assertEquals($refundAmount, $partialRefundRowData['refund_amount_rs_ps']);
        $this->assertNotNull($fullRefundRowData['bank_ref_no']);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
