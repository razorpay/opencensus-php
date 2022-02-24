<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;

class NbplusNetbankingRblCombinedFileTest extends StaticCallbackNbplusGatewayTest
{
    /**
     * @var array
     */
    protected $terminal;
    /**
     * @var string
     */
    protected $bank;
    /**
     * @var array
     */
    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingRblGatewayTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = 'RATN';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingRblCombinedFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $paymentEntity1 =  $this->getLastEntity('payment', true);

        $this->gateway = $paymentEntity1['gateway'];

        $this->refundPayment($transaction1['entity_id']);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $paymentEntity2 = $this->getLastEntity('payment', true);

        $this->gateway = $paymentEntity2['gateway'];

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

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $expectedFilesContent = [
            'entity'=> 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'rbl_netbanking_claim',
                ],
                [
                    'type' => 'rbl_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity2, $paymentEntity1) {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Rbl Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  1000,
                    'refunds' =>  505,
                    'total'   =>  495
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2
                ],
            ];
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile']);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            $this->assertCount(2, $mail->attachments);
            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }
    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundFileData['url'])[0];

        $this->assertCount(2, $refundsFileContents);

        $refundAmounts = [500, 5];

        array_map(
            function($amount, $index) use ($refundsFileContents)
            {
                // We increment $ind in the local scope so that srno = $ind = 1
                $refund = $refundsFileContents[$index];

                $this->assertEquals($index, $refund['srno']);
                $this->assertEquals(500, $refund['txn_amount_rs_ps']);
                $this->assertEquals($amount, $refund['refund_amount_rs_ps']);

            },
            $refundAmounts,
            array_keys($refundAmounts)
        );
        $this->assertEquals(2, count($refundsFileContents));
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = file($claimData['url']);

        $payment1RowData = explode(',', $claimFileContents[0]);

        $payment2RowData = explode(',', $claimFileContents[1]);

        $this->assertCount(12, $payment1RowData);

        $this->assertEquals($payment1['id'], $payment1RowData[7]);

        $this->assertEquals($payment2['id'], $payment2RowData[7]);

        $this->assertEquals("Razorpay", $payment1RowData[8]);

        $this->assertEquals("Razorpay", $payment2RowData[8]);

        $this->assertEquals("Success", $payment1RowData[9]);

        $this->assertEquals("Success", $payment2RowData[9]);

        $this->assertEquals("Success", trim($payment1RowData[11]));

        $this->assertEquals("Success", trim($payment2RowData[11]));

        $paymentAmount = number_format($payment1['amount'] / 100, 2, '.', '');

        $this->assertEquals($paymentAmount, $payment1RowData[5]);

        $paymentAmount = number_format($payment2['amount'] / 100, 2, '.', '');

        $this->assertEquals($paymentAmount, $payment2RowData[5]);
    }
}
