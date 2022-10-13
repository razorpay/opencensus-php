<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Entity;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingSrcbCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSrcbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = Bank::SRCB;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_saraswat_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingSrcbCombinedFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity1 = $this->getDbLastPayment();

        $this->fixtures->edit('transaction', $paymentEntity1->getTransactionId(), [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($paymentEntity1->getPublicId());

        $refundEntity1 = $this->getDbLastRefund();

        $paymentEntity1 = $paymentEntity1->reload();

        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity2 = $this->getDbLastPayment();

        $this->fixtures->edit('transaction', $paymentEntity2->getTransactionId(), [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($paymentEntity2->getPublicId(), 500);

        $refundEntity2 = $this->getDbLastRefund();

        $paymentEntity2 = $paymentEntity2->reload();

        $this->assertEquals('refunded', $paymentEntity1['status']);
        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals(3, $paymentEntity1['cps_route']);
        $this->assertEquals(3, $paymentEntity2['cps_route']);
        $this->assertEquals('full', $paymentEntity1['refund_status']);
        $this->assertEquals('partial', $paymentEntity2['refund_status']);
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1,$refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertTrue($paymentEntity1->transaction->isReconciled());
        $this->assertTrue($paymentEntity2->transaction->isReconciled());
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities(ConstantsEntity::FILE_STORE, ['count' => 2], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'saraswat_netbanking_claims',
                ],
                [
                    'type' => 'saraswat_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function (Mailable $mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Srcb Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  "1000.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "495.00"
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkRefundsFile($mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $refundEntity2,
                $paymentEntity2);
            $this->checkClaimsFile($mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            //
            // Marking netbanking refund transaction as reconciled after sending in bank file
            //
            $this->assertTrue($refundEntity1->transaction->isReconciled());
            $this->assertTrue($refundEntity2->transaction->isReconciled());

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment1, $fullRefund, $partialRefund, $payment2)
    {
        $refundsFileContents = file($refundFileData['url']);

        $fullRefundRowData1 = explode('|', $refundsFileContents[0]);

        $this->assertEquals(trim($fullRefundRowData1[0], '"'), $fullRefund['payment_id']);
        $this->assertEquals((int)number_format(trim(trim($fullRefundRowData1[2]),'"')*100, 0, '', ''), $fullRefund['amount']);

        $partialRefundRowData1 = explode('|', $refundsFileContents[1]);

        $this->assertEquals(trim($partialRefundRowData1[0], '"'),$partialRefund['payment_id']);
        $this->assertEquals((int)number_format(trim(trim($partialRefundRowData1[2]),'"')*100, 0, '',''),$partialRefund['amount']);
    }

    protected function checkClaimsFile(array $claimsFileData, Entity $payment1, Entity $payment2)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $row1 = explode('|', $claimsFileContents[1]);

        $this->assertEquals($row1[0], $payment1->getId());
        $this->assertEquals($row1[2], $this->getFormattedAmount($payment1->getAmount()));

        $row2 = explode('|', $claimsFileContents[2]);

        $this->assertEquals($row2[0], $payment2->getId());
        $this->assertEquals($row2[2], $this->getFormattedAmount($payment2->getAmount()));
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
