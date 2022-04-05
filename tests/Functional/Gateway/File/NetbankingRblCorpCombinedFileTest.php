<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Feature\Constants;
use RZP\Models\Gateway\File;
use RZP\Encryption\PGPEncryption;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingRblCorpCombinedFileTest extends StaticCallbackNbplusGatewayTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NebtankingRblCorpCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = "RATN_C";

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        /**
         * @var array
         */

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_terminal', $terminalAttrs);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testGenerateRblCorpCombinedFile()
    {
        Mail::fake();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);

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

        $file = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'rbl_corp_netbanking_claim',
                    'location' => 'Rbl/Claims/Netbanking/Rbl_Corp_Netbanking_Claims_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'rbl_corp_netbanking_refund',
                    'location' => 'Rbl/Refund/Netbanking/Rbl_Corp_Netbanking_Refunds_test' . '_' . $time . '.xlsx',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Rbl_corp Netbanking claims and refund files for '. $date,
                'count' => [
                    'claims' => 2,
                    'refunds' => 2,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertFileExists($mail->viewData['claimsFile']['url']);

            $this->assertFileExists($mail->viewData['refundsFile']['url']);

            $this->assertCount(2, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            $this->assertNotNull($refundTransaction['reconciled_type']);

            return true;
        });
    }

}
