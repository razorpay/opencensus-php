<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Services\Scrooge;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingAusfCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingAusfCombinedFileTestData.php';

        parent::setUp();

        $this->bank = IFSC::AUBL;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ausf_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }


    public function testNetbankingAusfCombinedFile()
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

        $file = $this->getEntities(Entity::FILE_STORE, ['entity_id' => $content[File\Entity::ID]], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
                [
                    'type' => 'aubl_netbanking_combined',
                ],
                [
                    'type' => 'aubl_netbanking_claim',
                ]
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Aubl Netbanking claims and refund files for '.$date,
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkSummaryFile(
                $mail->viewData['summaryFile'],
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }

    protected function checkSummaryFile($summaryFileLocation, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $summaryFileContents = (new ExcelImport)->toArray($summaryFileLocation['url'])[0];

        $this->assertCount(4, $summaryFileContents);

        $summaryFileRow1 = $summaryFileContents[0];

        $this->assertCount(16, $summaryFileRow1);
        $this->assertEquals($this->getFormattedDate($payment1['created_at'], 'd/m/Y'), $summaryFileRow1['date']);
        $this->assertEquals('PAYMENT', $summaryFileRow1['transaction_type']);
        $this->assertEquals($payment1['id'], $summaryFileRow1['payment_id']);
        $this->assertEquals($payment1['id'], $summaryFileRow1['userreferenceno']);
        $this->assertNotEmpty($summaryFileRow1['externalreferenceid']);
        $this->assertEquals($this->getFormattedAmount($payment1['amount']), $summaryFileRow1['amount']);

        $summaryFileRow2 = $summaryFileContents[1];

        $this->assertCount(16, $summaryFileRow2);
        $this->assertEquals($this->getFormattedDate($payment2['created_at'], 'd/m/Y'), $summaryFileRow2['date']);
        $this->assertEquals('PAYMENT', $summaryFileRow2['transaction_type']);
        $this->assertEquals($payment2['id'], $summaryFileRow2['payment_id']);
        $this->assertEquals($payment2['id'], $summaryFileRow2['userreferenceno']);
        $this->assertNotEmpty($summaryFileRow2['externalreferenceid']);
        $this->assertEquals($this->getFormattedAmount($payment2['amount']), $summaryFileRow2['amount']);

        $summaryFileRow3 = $summaryFileContents[2];

        $this->assertCount(16, $summaryFileRow3);
        $this->assertEquals($this->getFormattedDate($fullRefund->payment->getCreatedAt(), 'd/m/Y'), $summaryFileRow3['date']);
        $this->assertEquals('REFUND', $summaryFileRow3['transaction_type']);
        $this->assertEquals($fullRefund['payment_id'], $summaryFileRow3['payment_id']);
        $this->assertEquals($fullRefund['payment_id'], $summaryFileRow3['userreferenceno']);
        $this->assertNotNull($summaryFileRow3['externalreferenceid']);
        $this->assertEquals($this->getFormattedAmount($fullRefund->payment->getAmount()), $summaryFileRow3['amount']);
        $this->assertEquals($summaryFileRow3['refund_amount'], $this->getFormattedAmount($fullRefund['amount']));

        $summaryFileRow4 = $summaryFileContents[3];

        $this->assertCount(16, $summaryFileRow4);
        $this->assertEquals($this->getFormattedDate($partialRefund->payment->getCreatedAt(), 'd/m/Y'), $summaryFileRow4['date']);
        $this->assertEquals('REFUND', $summaryFileRow4['transaction_type']);
        $this->assertEquals($partialRefund['payment_id'], $summaryFileRow4['payment_id']);
        $this->assertEquals($partialRefund['payment_id'], $summaryFileRow4['userreferenceno']);
        $this->assertNotNull($summaryFileRow4['externalreferenceid']);
        $this->assertEquals($this->getFormattedAmount($partialRefund->payment->getAmount()), $summaryFileRow4['amount']);
        $this->assertEquals($summaryFileRow4['refund_amount'], $this->getFormattedAmount($partialRefund['amount']));
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = (new ExcelImport)->toArray($claimData['url'])[0];

        $this->assertCount(2, $claimFileContents);

        $claimFileRow = $claimFileContents[0];

        $this->assertCount(16, $claimFileRow);
        $this->assertEquals($claimFileRow['date'], $this->getFormattedDate($payment1['created_at'], 'd/m/Y'));
        $this->assertNotEmpty($claimFileRow['externalreferenceid']);
        $this->assertEquals($payment1['id'], $claimFileRow['userreferenceno']);
        $this->assertEquals($this->getFormattedAmount($payment1['amount']), $claimFileRow['amount']);

        $claimFileRow2 = $claimFileContents[1];

        $this->assertCount(16, $claimFileRow2);
        $this->assertEquals($claimFileRow2['date'], $this->getFormattedDate($payment2['created_at'], 'd/m/Y'));
        $this->assertNotEmpty($claimFileRow2['externalreferenceid']);
        $this->assertEquals($payment2['id'], $claimFileRow2['userreferenceno']);
        $this->assertEquals($this->getFormattedAmount($payment2['amount']), $claimFileRow2['amount']);
    }

    protected function getFormattedDate($date, $format): string
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function setFetchFileBasedRefundsFromScroogeMockResponse(array $refundEntities)
    {
        $scroogeResponse = [
            'code' => 200,
            'body' => [
                'data' => [],
            ],
        ];

        $scroogeResponseForRef1Update = json_decode('{
            "api_failed_count": 0,
            "api_failures": [],
            "scrooge_failed_count": 0,
            "scrooge_failures": [],
            "success_count": 2,
            "time_taken": 0.24121499061584473
        }', true);

        foreach ($refundEntities as $refundEntity)
        {
            $scroogeResponse['body']['data'][] = [
                'id'               => $refundEntity['id'],
                'amount'           => $refundEntity['amount'],
                'base_amount'      => $refundEntity['base_amount'],
                'payment_id'       => $refundEntity['payment_id'],
                'bank'             => $refundEntity->payment['bank'],
                'gateway'          => $refundEntity['gateway'],
                'currency'         => $refundEntity['currency'],
                'gateway_amount'   => $refundEntity['gateway_amount'],
                'gateway_currency' => $refundEntity['gateway_currency'],
                'method'           => $refundEntity->payment['method'],
                'created_at'       => $refundEntity['created_at'],
            ];
        }

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->onlyMethods(['getRefunds', 'bulkUpdateRefundReference1'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefunds')
                           ->willReturn($scroogeResponse);

        $this->app->scrooge->method('bulkUpdateRefundReference1')
                           ->willReturn($scroogeResponseForRef1Update);
    }
}
