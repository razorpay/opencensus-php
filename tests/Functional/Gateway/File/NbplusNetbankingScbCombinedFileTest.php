<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use Illuminate\Http\UploadedFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingScbCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingScbCombinedFileTestData.php';

        $this->bank = 'SCBL';

        parent::setUp();

        $this->bank = 'SCBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_scb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingScbCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment1 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $payment2 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $payment3 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        // full refund
        $refundFull = $this->refundPayment($payment1['id']);

        $refundEntity1 = $this->getDbLastEntity('refund');

        //partial refund
        $refundPartial = $this->refundPayment($payment2['id'], 500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Scb refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);

        $this->assertNotNull($content[File\Entity::SENT_AT]);

        $this->assertNull($content[File\Entity::FAILED_AT]);

        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'scb_netbanking_claim',
                ],
                [
                    'type' => 'scb_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2, $payment3, $refundFull, $refundPartial) {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Scbl Netbanking claims and refund files for '.$today,
                'amount' => [
                    'claims'  =>  "1500.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "995.00"
                ],
                'count' => [
                    'claims'  => 3,
                    'refunds' => 2,
                    'total'   => 0
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);
            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $mailData = $mail->viewData;

            $refundfilePath = $mailData['refundsFile']['url'];
            $claimsfilePath = $mailData['claimsFile']['url'];

            $this->checkRefundsFile(
                $refundfilePath,
                $payment1,
                $payment2,
                $refundFull,
                $refundPartial
            );

            $this->checkClaimsFile(
                $claimsfilePath,
                $payment1,
                $payment2,
                $payment3
            );

            $this->assertEquals($refundTransaction['type'], 'refund');
            $this->assertNotNull($refundTransaction['id']);
            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile($refundfilePath, $payment1,$payment2, $refund1, $refund2)
    {
        $refundSheet = (new ExcelImport)->toArray($refundfilePath)[0];


        $refundsFileLine1 = $refundSheet[0];
        $refundsFileLine2 = $refundSheet[1];

        $this->assertCount(7, $refundsFileLine1);
        $this->assertCount(7, $refundsFileLine2);

        $this->assertEquals($refundsFileLine1['payment_id'], str_replace('pay_', '', $payment1['id']));
        $this->assertEquals($refundsFileLine2['payment_id'], str_replace('pay_', '', $payment2['id']));

        $this->assertEquals($refundsFileLine1['txn_amount'], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundsFileLine2['txn_amount'], $this->getFormattedAmount($payment2['amount']));

        $totalAmountRefunded = $this->getFormattedAmount($refund1['amount']) + $this->getFormattedAmount($refund2['amount']);
        $totalAmountRefundedInFile = $refundsFileLine1['refund_amount'] + $refundsFileLine2['refund_amount'];

        $this->assertEquals($totalAmountRefunded, $totalAmountRefundedInFile);

    }

    protected function checkClaimsFile($claimsfilePath, $payment1,$payment2, $payment3)
    {
        $claimsSheet = (new ExcelImport)->toArray($claimsfilePath)[0];


        $claimsFileLine1 = $claimsSheet[0];
        $claimsFileLine2 = $claimsSheet[1];
        $claimsFileLine3 = $claimsSheet[2];

        $this->assertCount(6, $claimsFileLine1);
        $this->assertCount(6, $claimsFileLine2);
        $this->assertCount(6, $claimsFileLine3);

        $this->assertEquals($claimsFileLine1['paymentid'], str_replace('pay_', '', $payment1['id']));
        $this->assertEquals($claimsFileLine2['paymentid'], str_replace('pay_', '', $payment2['id']));
        $this->assertEquals($claimsFileLine3['paymentid'], str_replace('pay_', '', $payment3['id']));

        $this->assertEquals($claimsFileLine1['transaction_amount'], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($claimsFileLine2['transaction_amount'], $this->getFormattedAmount($payment2['amount']));
        $this->assertEquals($claimsFileLine3['transaction_amount'], $this->getFormattedAmount($payment3['amount']));

        $totalAmount = $this->getFormattedAmount($payment1['amount']) + $this->getFormattedAmount($payment2['amount']) + $this->getFormattedAmount($payment3['amount']);;
        $totalAmountInFile = $claimsFileLine1['transaction_amount'] + $claimsFileLine2['transaction_amount'] + $claimsFileLine3['transaction_amount'];

        $this->assertEquals($totalAmount, $totalAmountInFile);

    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

}
