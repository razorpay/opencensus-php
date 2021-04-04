<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Gateway\File\Processor\Refund\Sib;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingSibCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    const REFUND_FIELDS = [
        Sib::SERIAL_NO,
        Sib::PAYMENT_ID,
        Sib::REFUND_MODE,
        Sib::PAYEE_ID,
        Sib::REFUND_AMOUNT,
        Sib::BANK_REFERENCE_ID
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSibCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'SIBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sib_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingSibCombinedFile()
    {
        Mail::fake();

        // full refund
        $paymentArray  = $this->payment;

        $payment1      = $this->doAuthAndCapturePayment($paymentArray);
        $refundFull    = $this->refundPayment($payment1['id']);
        $refundTrans1  = $this->getLastEntity('transaction', true);
        $refundEntity1 = $this->getDbLastEntity('refund');

        //partial refund
        $payment2      = $this->doAuthAndCapturePayment($paymentArray);
        $refundPartial = $this->refundPayment($payment2['id'], 500);
        $refundTrans2  = $this->getLastEntity('transaction', true);
        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Sib refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $refundTransaction1 = $this->getDbEntityById('transaction', $refundTrans1['id']);
        $refundTransaction2 = $this->getDbEntityById('transaction', $refundTrans2['id']);
        $this->assertNotNull($refundTransaction1['reconciled_at']);
        $this->assertNotNull($refundTransaction2['reconciled_at']);
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFilesContent = [
            'type'      => 'sib_netbanking_refund',
            'extension' => 'txt'
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2, $refundFull, $refundPartial)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Sib Netbanking claims and refund files for '.$date,
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

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $payment1['id'],
                [$refundFull['amount'], $refundPartial['amount']], 2);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    public function testNetbankingSibCombinedFileOnScroogeGolive()
    {
        Mail::fake();

        $scroogeGoliveTimestamp = Carbon::createFromTimestamp(1575982238, Timezone::IST);

        Carbon::setTestNow($scroogeGoliveTimestamp);

        // Setting begin and end timestamps
        $this->testData['testNetbankingSibCombinedFileOnScroogeGolive']['request']['content']['begin'] =
            Carbon::today(Timezone::IST)->getTimestamp()-43200;

        $this->testData['testNetbankingSibCombinedFileOnScroogeGolive']['request']['content']['end'] =
            Carbon::tomorrow(Timezone::IST)->getTimestamp();

        // full refund
        $paymentArray    = $this->getDefaultNetbankingPaymentArray($this->bank);

        // refund on scrooge - returned
        $payment1      = $this->doAuthAndCapturePayment($paymentArray);
        $refundFull    = $this->refundPayment($payment1['id']);
        $refundEntity1 = $this->getDbLastEntity('refund');

        // partial refund on scrooge - not returned considering as TPV refund
        $payment2         = $this->doAuthAndCapturePayment($paymentArray);
        $refundPartial1   = $this->refundPayment($payment2['id'], 500);

        $scroogeGolivePreviousDayTimestamp = Carbon::createFromTimestamp(1575982238-43200, Timezone::IST);

        Carbon::setTestNow($scroogeGolivePreviousDayTimestamp);

        // partial refund on API - returned
        $refundPartial2   = $this->refundPayment($payment2['id'], 500);
        $refundEntity3    = $this->getDbLastEntity('refund');
        $this->fixtures->edit('refund', $refundEntity3['id'], ['is_scrooge' => 0]);

        // partial refund on API - returned
        $refundPartial3   = $this->refundPayment($payment2['id'], 10000);
        $refundEntity4    = $this->getDbLastEntity('refund');
        $this->fixtures->edit('refund', $refundEntity4['id'], ['is_scrooge' => 0]);

        // Netbanking Sib refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFilesContent = [
            'type'      => 'sib_netbanking_refund',
            'extension' => 'txt'
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        // Not using $refundPartial1 since it is not to be returned
        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2, $refundFull, $refundPartial2, $refundPartial3)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Sib Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '605.00',
                    'total'   => '395.00'
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 3,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $refundFileData = $mail->viewData['refundsFile'];

            $refundsFileContents = file($refundFileData['url']);

            $this->assertCount(3, $refundsFileContents);

            $fullRefundRowData = explode('||', $refundsFileContents[0]);
            $fullRefundRowData = array_combine(self::REFUND_FIELDS, $fullRefundRowData);

            $partialRefundRowData1 = explode('||',$refundsFileContents[1]);
            $partialRefundRowData1 = array_combine(self::REFUND_FIELDS, $partialRefundRowData1);

            $partialRefundRowData2 = explode('||',$refundsFileContents[2]);
            $partialRefundRowData2 = array_combine(self::REFUND_FIELDS, $partialRefundRowData2);

            $this->assertCount(6, $fullRefundRowData);
            $this->assertCount(6, $partialRefundRowData1);
            $this->assertCount(6, $partialRefundRowData2);

            $paymentId1 = $payment1['id'];
            $paymentId2 = $payment2['id'];

            $this->fixtures->stripSign($paymentId1);
            $this->fixtures->stripSign($paymentId2);

            $expectedPaymentIds = [$paymentId1, $paymentId2, $paymentId2];

            $actualPaymentIds = [
                $fullRefundRowData[Sib::PAYMENT_ID],
                $partialRefundRowData1[Sib::PAYMENT_ID],
                $partialRefundRowData2[Sib::PAYMENT_ID]
            ];

            $expectedAmounts = [
                number_format(($refundFull['amount']) / 100, 2, '.', ''),
                number_format(($refundPartial2['amount']) / 100, 2, '.', ''),
                number_format(($refundPartial3['amount']) / 100, 2, '.', '')];

            $actualAmounts = [
                $fullRefundRowData[Sib::REFUND_AMOUNT],
                $partialRefundRowData1[Sib::REFUND_AMOUNT],
                $partialRefundRowData2[Sib::REFUND_AMOUNT]
            ];

            $assertPaymentIds = (($expectedPaymentIds === array_intersect($expectedPaymentIds, $actualPaymentIds)) &&
                ($actualPaymentIds === array_intersect($actualPaymentIds, $expectedPaymentIds)));

            $assertRefundAmounts = (($expectedAmounts === array_intersect($expectedAmounts, $actualAmounts)) &&
                ($actualAmounts === array_intersect($actualAmounts, $expectedAmounts)));

            $this->assertEquals(true, $assertPaymentIds);
            $this->assertEquals(true, $assertRefundAmounts);

            $this->assertCount(1, $mail->attachments);

            return true;
        });

        Carbon::setTestNow();
    }

    protected function checkRefundsFile(array $refundFileData, $paymentId, $refundAmts, $expectedRefundCount)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount($expectedRefundCount, $refundsFileContents);

        $fullRefundRowData = explode('||',$refundsFileContents[0]);
        $fullRefundRowData = array_combine(self::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('||',$refundsFileContents[1]);
        $partialRefundRowData = array_combine(self::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(6, $fullRefundRowData);

        $this->fixtures->stripSign($paymentId);

        $this->assertEquals($paymentId, $fullRefundRowData[Sib::PAYMENT_ID]);

        // validating if partial refund amount is reflected in the file
        $refundAmount = number_format(($refundAmts[0]) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $fullRefundRowData[Sib::REFUND_AMOUNT]);

        $refundAmount = number_format(($refundAmts[1]) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $partialRefundRowData[Sib::REFUND_AMOUNT]);
    }
}
