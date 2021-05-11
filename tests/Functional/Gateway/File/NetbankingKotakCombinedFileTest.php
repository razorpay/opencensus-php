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

class NetbankingKotakCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingKotakCombinedFileTestData.php';

        parent::setUp();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGenerateKotakCombinedFileForNonTpv()
    {
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Kotak Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims' => 500,
                    'refunds' => 500,
                    'total' => 0
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    public function testGenerateKotakCombinedFileForTpv()
    {
        Mail::fake();

        $terminalAttrs = [
            'id'               => 'TpvNbKotakTmnl',
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        $terminal = $this->fixtures->create(
                        'terminal:shared_netbanking_kotak_terminal',
                        $terminalAttrs);

        $payment = $this->makeTpvPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Kotak Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims' => 500,
                    'refunds' => 500,
                    'total' => 0
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    public function testGenerateKotakCombinedFileForNonTpvRefundsOutOfRange()
    {
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Refund out of range
        $refundEntity['created_at'] = Carbon::yesterday()->getTimestamp();

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }

    public function testGenerateKotakCombinedFileForTpvRefundsOutOfRange()
    {
        Mail::fake();

        $terminalAttrs = [
            'id'               => 'TpvNbKotakTmnl',
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        $terminal = $this->fixtures->create(
            'terminal:shared_netbanking_kotak_terminal',
            $terminalAttrs);

        $payment = $this->makeTpvPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Refund out of range
        $refundEntity['created_at'] = Carbon::yesterday()->getTimestamp();

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }

    public function testGenerateTpvKotakRefundFile()
    {
        Mail::fake();

        $terminalAttrs = [
            'id'               => 'TpvNbKotakTmnl',
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        $terminal = $this->fixtures->create(
            'terminal:shared_netbanking_kotak_terminal',
            $terminalAttrs);

        $payment = $this->makeTpvPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertQueued(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Kotak Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims' => 500,
                    'refunds' => 500,
                    'total' => 0
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    public function testGenerateNonTpvKotakRefundFile()
    {
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Kotak refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertQueued(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Kotak Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims' => 500,
                    'refunds' => 500,
                    'total' => 0
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });

    }

    protected function makeTpvPayment()
    {
        $this->fixtures->merchant->enableTPV();

        $order = $this->createTpvOrderForBank('KKBK');

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->fixtures->merchant->disableTpv();

        return $payment;
    }

    protected function createTpvOrderForBank($bank)
    {
        $request = [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '0040304030403040',
                'bank'           => $bank,
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        return $content;
    }

    protected function checkClaimsFile(array $claimsFileData)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $claimsFileLine1 = explode('|', $claimsFileContents[0]);

        $this->assertCount(1, $claimsFileContents);

        $this->assertCount(6, $claimsFileLine1);
    }

    protected function checkRefundsFile(array $refundsFileData)
    {
        $refundsFileContents = file($refundsFileData['url']);

        $refundsFileName = $refundsFileData['name'];

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);

        $this->assertCount(6, $refundsFileLine1);

        $this->assertCount(2, $refundsFileContents);

        $this->assertEquals($refundsFileName, explode('|', $refundsFileContents[0])[0]);
    }
}
