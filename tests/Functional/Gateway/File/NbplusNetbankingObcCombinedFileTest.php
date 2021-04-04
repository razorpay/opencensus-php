<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingObcCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected function setUp(): void
    {
        $this->markTestSkipped();

        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingObcGatewayFileTestData.php';

        parent::setUp();

        $this->bank = 'ORBC';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testGenerateCombinedFileTest()
    {
        Mail::fake();

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

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

        $this->refundPayment($transaction2['entity_id'], 100);

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

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 1
        ], true);

        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $actualFileDetails = $this->getLastEntity('file_store', true);

        $this->assertEquals($actualFileDetails['type'], 'obc_netbanking_refund');

        $this->assertEquals($actualFileDetails['extension'], 'txt');

        $this->assertEquals($actualFileDetails['name'], 'REFUND_NB_OBC_RAZORPAY_'. $date);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'entity_id' => $content['id'],
                    'type' => 'obc_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $paymentEntity2, $refundEntity1, $refundEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'obc Netbanking claims and refund files for ' . $date,
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '501.00',
                    'total'   => '499.00',
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(1, $mail->attachments);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileData, $payment1,$payment2, $refund1,$refund2)
    {

        $refundsFileContents = file($refundsFileData['url']);

        $headerLine = explode('|', trim($refundsFileContents[0]));

        $date = Carbon::today(Timezone::IST)->format('Ymd');

        $expectedHeaderLine = [
            'HOBCUTLPRFD',
            $date,
            'test_merchant_id',
        ];

        $this->assertArraySelectiveEquals($expectedHeaderLine, $headerLine);

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);
        $refundsFileLine2 = explode('|', $refundsFileContents[2]);
        $refundsFileLine3 = explode('|', $refundsFileContents[3]);

        $this->assertCount(7, $refundsFileLine1);

        $this->assertCount(4, $refundsFileContents);

        $this->assertEquals($refundsFileLine1[0], $payment1['id']);
        $this->assertEquals($refundsFileLine2[0], $payment2['id']);
        $this->assertEquals($refundsFileLine1[2], $this->getFormattedAmount($payment1['amount_refunded']));
        $this->assertEquals($refundsFileLine2[2], $this->getFormattedAmount($payment2['amount_refunded']));
        $this->assertNotNull($refundsFileLine1[2]);
        $this->assertNotNull($refundsFileLine2[2]);
        $this->assertEquals($refundsFileLine1[5], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundsFileLine2[5], $this->getFormattedAmount($payment2['amount']));

        $totalAmountRefunded = $payment1['amount_refunded'] + $payment2['amount_refunded'];

        $this->assertEquals($refundsFileLine3[3], $this->getFormattedAmount($totalAmountRefunded));

    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
