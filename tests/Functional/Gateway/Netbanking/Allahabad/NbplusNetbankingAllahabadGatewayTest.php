<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Transaction\Statement\Entity;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingAllahabadGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->markTestSkipped("Bank merged with Indian bank");

        $this->testDataFilePath = __DIR__ . '/NetbankingAllahabadGatewayTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');

        $this->bank = 'ALLA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingAllahabadCombinedFile()
    {
        Mail::fake();

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
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 1
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'allahabad_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Allahabad Netbanking claims and refund files for '.$date,
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

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $refundEntity2,
                $paymentEntity2);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData, $payment1, $fullRefund, $partialRefund, $payment2)
    {
        $this->assertFileExists($refundFileData['url']);

        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $refundsFileRow1 = explode('|', $refundsFileContents[0]);

        $refundsFileRow2 = explode('|', $refundsFileContents[1]);

        $this->assertCount(10, $refundsFileRow1);

        $this->assertEquals($refundsFileRow1[0], 'RAZOR');

        $this->assertEquals($refundsFileRow1[1], 'ALB');

        $actualAmount = number_format($payment1['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow1[8], floatval($actualAmount));

        $this->assertEquals($payment1['id'], $refundsFileRow1[7]);

        $refundAmount = number_format($fullRefund['amount'] / 100, 2, '.', '');

        $this->assertEquals(floatval($refundAmount), trim($refundsFileRow1[9]));

        $this->assertCount(10, $refundsFileRow2);

        $this->assertEquals($payment2['id'], $refundsFileRow2[7]);

        $this->assertEquals($refundsFileRow2[0], 'RAZOR');

        $this->assertEquals($refundsFileRow2[1], 'ALB');

        $actualAmount = number_format($payment2['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow2[8], floatval($actualAmount));

        $refundAmount = number_format($partialRefund['amount'] / 100, 2, '.', '');

        $this->assertEquals(floatval($refundAmount), trim($refundsFileRow2[9]));
    }
}
