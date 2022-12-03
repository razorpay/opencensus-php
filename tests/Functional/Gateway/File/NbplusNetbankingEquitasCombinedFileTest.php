<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingEquitasCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    public function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingEquitasCombinedFileTestData.php';

        parent::setUp();

        $this->bank = Bank::ESFB;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_equitas_terminal');
    }

    public function testNetbankingEquitasCombinedFile()
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

        $this->refundPayment($transaction2['entity_id'],500);

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
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type'     => 'equitas_netbanking_refund',
                    'location' => 'Equitas/Refund/Netbanking/Equitas_Netbanking_Refunds' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $refundEntity2, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Equitas Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 1000,
                    'refunds' => 505,
                    'total'   => 495
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);
            $this->assertCount(1, $mail->attachments);
            $this->checkRefundsFile($mail->viewData['refundsFile'], $paymentEntity1, $paymentEntity2);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, Payment\Entity $pay1, $pay2)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(3, $refundsFileContents);

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);
        $this->assertCount(9, $refundsFileLine1);
        $this->assertEquals($this->terminal->getGatewayMerchantId(), $refundsFileLine1[0]);
        $this->assertEquals($pay1->getId(), $refundsFileLine1[2]);
        $this->assertNotEmpty($refundsFileLine1[3]);
        $this->assertEquals(500, $refundsFileLine1[4]);
        $this->assertEquals('F', $refundsFileLine1[5]);
        $this->assertEquals($this->formatAmount($pay1->getAmount()), trim($refundsFileLine1[8]));

        $refundsFileLine2 = explode('|', $refundsFileContents[2]);
        $this->assertCount(9, $refundsFileLine2);
        $this->assertEquals($this->terminal->getGatewayMerchantId(), $refundsFileLine2[0]);
        $this->assertEquals($pay2->getId(), $refundsFileLine2[2]);
        $this->assertNotEmpty($refundsFileLine2[3]);
        $this->assertEquals(5, $refundsFileLine2[4]);
        $this->assertEquals('P', $refundsFileLine2[5]);
        $this->assertEquals($this->formatAmount($pay2->getAmount()), trim($refundsFileLine2[8]));
    }

    protected function formatAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
