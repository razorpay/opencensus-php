<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingKvbCombinedFileTest extends StaticCallbackNbplusGatewayTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingKvbCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = 'KVBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kvb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingKvbCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->payment;

        $this->doAuthAndCapturePayment($paymentArray);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($paymentArray);

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

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kvb_netbanking_claim',
                ],
                [
                    'type' => 'kvb_netbanking_refund',
                    'location' => 'Kvb/Refund/Netbanking/RAZORPAY_REFUND_' . $date . '_01.xlsx',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $refundEntity2, $paymentEntity2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Kvb Netbanking claims and refund files for '.$today,
                'amount' => [
                    'claims'  =>  "1000.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "495.00"
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 0
                ],
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

        $this->assertCount(8, $refundFileRow1);
        $this->assertEquals($refundFileRow1['trans_date'], $this->getFormattedDate($payment1['created_at'], 'd/m/Y'));
        $this->assertEquals($refundFileRow1['refund_date'], $this->getFormattedDate($fullRefund['created_at'], 'd/m/Y'));
        $this->assertNotNull($refundFileRow1['bank_ref_no']);
        $this->assertEquals($refundFileRow1['pgi_reference_no'], $payment1['id']);
        $this->assertEquals($refundFileRow1['txn_amount_rs_ps'], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundFileRow1['refund_amount_rs_ps'], $this->getFormattedAmount($fullRefund['amount']));
        $this->assertNotNull($refundFileRow1['bank_account_no']);

        $refundFileRow2 = $refundsFileContents[1];

        $this->assertCount(8, $refundFileRow1);
        $this->assertEquals($refundFileRow2['trans_date'], $this->getFormattedDate($payment2['created_at'], 'd/m/Y'));
        $this->assertEquals($refundFileRow2['refund_date'], $this->getFormattedDate($partialRefund['created_at'], 'd/m/Y'));
        $this->assertNotNull($refundFileRow2['bank_ref_no']);
        $this->assertEquals($refundFileRow2['pgi_reference_no'], $payment2['id']);
        $this->assertEquals($refundFileRow2['txn_amount_rs_ps'], $this->getFormattedAmount($payment2['amount']));
        $this->assertEquals($refundFileRow2['refund_amount_rs_ps'], $this->getFormattedAmount($partialRefund['amount']));
        $this->assertNotNull($refundFileRow2['bank_account_no']);

    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = (new ExcelImport)->toArray($claimData['url'])[0];

        $this->assertCount(2, $claimFileContents);

        $claimFileRow1 = $claimFileContents[0];

        $this->assertCount(8, $claimFileRow1);
        $this->assertEquals($claimFileRow1['fldmerchcode'], 'RAZORPAY');
        $this->assertEquals($claimFileRow1['trans_date'], strtoupper($this->getFormattedDate($payment1['created_at'], 'd-M-Y')));
        $this->assertEquals($claimFileRow1['fldmerchrefnbr'], $payment1['id']);
        $this->assertNotNull($claimFileRow1['account_number']);
        $this->assertEquals($claimFileRow1['transaction_amount'], $this->getFormattedAmount($payment1['amount']));
        $this->assertNotNull($claimFileRow1['fldbankrefnbr']);
        $this->assertEquals($claimFileRow1['status'], 'refunded');

        $claimFileRow2 = $claimFileContents[1];

        $this->assertCount(8, $claimFileRow2);
        $this->assertEquals($claimFileRow2['fldmerchcode'], 'RAZORPAY');
        $this->assertEquals($claimFileRow2['trans_date'], strtoupper($this->getFormattedDate($payment2['created_at'], 'd-M-Y')));
        $this->assertEquals($claimFileRow2['fldmerchrefnbr'], $payment2['id']);
        $this->assertNotNull($claimFileRow2['account_number']);
        $this->assertEquals($claimFileRow2['transaction_amount'], $this->getFormattedAmount($payment2['amount']));
        $this->assertNotNull($claimFileRow2['fldbankrefnbr']);
        $this->assertEquals($claimFileRow2['status'], 'partial refunded');
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
