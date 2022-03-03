<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Gateway\Mozart\NetbankingCbi\RefundFields;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingCbiCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCbiCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CBIN';

        /**
         * @var array
         */
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_cbi_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

    }

    public function testNetbankingCbiCombinedFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);
        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

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


        // Netbanking Cbi refunds have moved to scrooge
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

        $file = $this->getLastEntity('file_store', true);

        $expectedFilesContent = [
            'type'      => 'cbi_netbanking_refund',
            'extension' => 'txt'
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($transaction1, $transaction2, $refundTransaction1, $refundTransaction2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Cbi Netbanking claims and refund files for '.$today,
                'bankName' => 'Cbi',
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile'], [$refundTransaction1['amount'], $refundTransaction2['amount']], $refundTransaction1, $refundTransaction2);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $refundAmounts, $refundTransaction1,$refundTransaction2)
    {
        $refundsFileContents = file($refundFileData['url']);

        $refNoInFile1 = substr($refundsFileContents[0], -16, 14);
        $refundID1 = substr($refundTransaction1['entity_id'],-14);

        $refNoInFile2 = substr($refundsFileContents[1], -14);
        $refundID2 = substr($refundTransaction2['entity_id'],-14);

        $this->assertEquals($refundID1, $refNoInFile1);
        $this->assertEquals($refundID2, $refNoInFile2);

        $this->assertCount(2, $refundsFileContents);

        $refund1 = substr($refundsFileContents[0],36,17);
        $refund2 = substr($refundsFileContents[1],36,17);

        $this->assertEquals($refundAmounts[0], (int)$refund1);
        $this->assertEquals($refundAmounts[1], (int)$refund2);

        // Marking netbanking transaction as reconciled after sending in bank file
        $refundTransaction = $this->getLastEntity('transaction',true);
        $this->assertNotNull($refundTransaction['reconciled_at']);
    }
}
