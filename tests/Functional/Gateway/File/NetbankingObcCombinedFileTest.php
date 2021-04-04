<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingObcCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingObcGatewayFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $this->obcPaymentArray = $this->getDefaultNetbankingPaymentArray('ORBC');
    }

    public function testGenerateCombinedFileTest()
    {
        Mail::fake();

        $payment1 = $this->doAuthAndCapturePayment($this->obcPaymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $fullRefund = $this->refundPayment($payment1['id']);

        $payment2 = $this->doAuthAndCapturePayment($this->obcPaymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $partialRefund = $this->refundPayment($payment2['id'], 100);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Scb refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
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

        Mail::assertSent(DailyFileMail::class, function ($mail)
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

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileData)
    {
        $refundsFileContents = file($refundsFileData['url']);

        $refundsFileName = $refundsFileData['name'];

        $headerLine = explode('|', trim($refundsFileContents[0]));

        $date = Carbon::today(Timezone::IST)->format('Ymd');

        $expectedHeaderLine = [
            'HOBCUTLPRFD',
             $date,
            'test_merchant_id',
        ];

        $this->assertArraySelectiveEquals($expectedHeaderLine, $headerLine);

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);

        $this->assertCount(7, $refundsFileLine1);

        $this->assertCount(4, $refundsFileContents);
    }
}
