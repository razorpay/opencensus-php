<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIbkCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIbkCombinedFileTestData.php';

        parent::setUp();

        $this->bank = IFSC::IDIB;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');
    }

    public function testNetbankingIbkCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('IDIB');

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

        $file = $this->getEntities(Entity::FILE_STORE, ['count' => 2], true);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
                [
                    'type' => 'ibk_netbanking_claim',
                    'location' => 'Ibk/Claim/Netbanking/Claim_' . $date . '_IndianBank-NetBanking.xlsx',
                ],
                [
                    'type' => 'ibk_netbanking_refund',
                    'location' => 'Ibk/Refund/Netbanking/Refund_' . $date . '_IndianBank-NetBanking.xlsx',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Ibk Netbanking claims and refund files for '.$date,
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundFileData['url'])[0];

        $this->assertCount(2, $refundsFileContents);

        $refundFileRow1 = $refundsFileContents[0];

        $this->assertCount(11, $refundFileRow1);
        $this->assertEquals($refundFileRow1['refund_id'], $fullRefund['id']);
        $this->assertEquals($refundFileRow1['refund_date'], $this->getFormattedDate($fullRefund['created_at'], 'm/d/Y'));
        $this->assertNotNull($refundFileRow1['bank_ref_no']);
        $this->assertEquals($refundFileRow1['pgi_reference_no'], $payment1['id']);
        $this->assertEquals($refundFileRow1['txn_amount_rs_ps'], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundFileRow1['refund_amount_rs_ps'], $this->getFormattedAmount($fullRefund['amount']));

        $refundFileRow2 = $refundsFileContents[1];

        $this->assertCount(11, $refundFileRow1);
        $this->assertEquals($refundFileRow2['refund_id'], $partialRefund['id']);
        $this->assertEquals($refundFileRow2['refund_date'], $this->getFormattedDate($partialRefund['created_at'], 'm/d/Y'));
        $this->assertNotNull($refundFileRow2['bank_ref_no']);
        $this->assertEquals($refundFileRow2['pgi_reference_no'], $payment2['id']);
        $this->assertEquals($refundFileRow2['txn_amount_rs_ps'], $this->getFormattedAmount($payment2['amount']));
        $this->assertEquals($refundFileRow2['refund_amount_rs_ps'], $this->getFormattedAmount($partialRefund['amount']));
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = (new ExcelImport)->toArray($claimData['url'])[0];

        $this->assertCount(1, $claimFileContents);

        $claimFileRow = $claimFileContents[0];

        $this->assertCount(12, $claimFileRow);
        $this->assertEquals($claimFileRow['date'], $this->getFormattedDate($payment1['created_at'], 'm/d/Y'));
        $this->assertNotEmpty($claimFileRow['nodal_bank_account_details']);
        $this->assertEquals('2', $claimFileRow['number_of_transaction']);
        $this->assertEquals('1000.00', $claimFileRow['transactions_amount_in_rs']);
        $this->assertEquals('2', $claimFileRow['number_of_refunds']);
        $this->assertEquals('505.00', $claimFileRow['refund_amount_in_rs']);
        $this->assertEquals('495.00', $claimFileRow['net_transaction_amount_in_rs']);
    }

    protected function getFormattedDate($date, $format)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
