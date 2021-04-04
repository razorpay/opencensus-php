<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingKotakCorpCombinedFileTest extends StaticCallbackNbplusGatewayTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingKotakCorpCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = Netbanking::KKBK_C;

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal', $terminalAttrs);

    }

    public function testNetbankingKotakCorpCombinedFile()
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

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
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
                    'type' => 'kotak_corp_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Corporate Kotak_corp Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  '1000.00',
                    'refunds' =>  '505.00',
                    'total'   =>  '495.00'
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

        $this->assertCount(8, $refundsFileRow1);

        $refundAmount = number_format($fullRefund['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow1[2], floatval($refundAmount));

        $actualAmount = number_format($payment1['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow1[5], floatval($actualAmount));

        $this->assertCount(8, $refundsFileRow2);

        $refundAmount = number_format($partialRefund['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow2[2], floatval($refundAmount));

        $actualAmount = number_format($payment2['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundsFileRow2[5], floatval($actualAmount));
    }
}
