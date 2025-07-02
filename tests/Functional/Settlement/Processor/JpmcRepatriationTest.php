<?php

namespace RZP\Tests\Functional\Settlement\Processor;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Encryption\PGPEncryption;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class JpmcRepatriationTest extends TestCase
{
    use PartnerTrait;
    use PaymentTrait;
    use SettlementTrait;
	use FileHandlerTrait;
    use DbEntityFetchTrait;

    const DEFAULT_SUBMERCHANT_ID    = '10000000000009';

	protected function setUp(): void
    {
        parent::setUp();
        $connector = $this->mockSqlConnectorWithReplicaLag(0);
        $this->app->instance('db.connector.mysql', $connector);
        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
        $this->app['config']->set('applications.ufh.mock', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    protected function setupJpmcSettlement()
    {
        list($partner, $app) = $this->createPartnerAndApplication([
            'partner_type' => 'reseller'
        ]);
        $this->createConfigForPartnerApp($app->getId());
        [$subMerchant, $accessMap] = $this->createSubMerchant($partner, $app,
            ['id'=>self::DEFAULT_SUBMERCHANT_ID]);
        $this->fixtures->edit('merchant', $subMerchant->getId(), [
            'channel' => Channel::AXIS,
            'activated' => true ,
            'suspended_at' => null
        ]);

        $payments = $this->createPaymentEntities(2, $subMerchant->getId());

        $this->initiateSettlements(Channel::AXIS);
        $settlement = $this->getLastEntity('settlement', true);

        $this->fixtures->user->createUserForMerchant($settlement['merchant_id']);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow'], $settlement['merchant_id']);

        $txn1 = $this->getEntities('transaction', ['entity_id' => $payments[0]['id']], true);
        $txn2 = $this->getEntities('transaction', ['entity_id' => $payments[1]['id']], true);

        $this->fixtures->stripSign($settlement['id']);

        $request = [
            'url' => '/settlements/status/update',
            'method' => 'POST',
            'content' => [
                'id'                            => $settlement['id'],
                'utr'                           => '12312312311',
                'status'                        => 'processed',
                'redacted_ba'                   => 'sample',
                'remarks'                       => 'xyz',
                'failure_reason'                => 'na',
                'trigger_failed_notification'   => false
            ],
        ];

        $this->ba->settlementsAuth();
        $this->makeRequestAndGetContent($request);

        return [$settlement, $txn1, $txn2];
    }

//    public function testJpmcRepatriation()
//    {
//        [$settlement, $txn1, $txn2] = $this->setupJpmcSettlement();
//
//        $entries = [
//            [
//                'Invoices' => $this->getInvoiceDataSheet($settlement, [$txn1['items'][0], $txn2['items'][0]]),
//            ],
//        ];
//
//        $url = $this->writeToExcelFile($entries[0], 'RAZORPAYIN.POSTGTP.EXCEL.2023-10-27.18-20-56', 'files/filestore', ['Invoices']);
//        $this->encryptFile($url);
//        $uploadedFile = $this->createUploadedFile($url, 'RAZORPAYIN.POSTGTP.EXCEL.2023-10-27.18-20-56.xlsx');
//
//        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();
//        $this->app->instance('ufh.service', $ufhService);
//        $ufhService->shouldReceive('uploadFileAndGetResponse')->times(1);
//
//        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
//                    ->onlyMethods(['pushIdentifyAndTrackEvent'])
//                    ->getMock();
//
//        $this->app->instance('segment-analytics', $segmentMock);
//
//        $segmentMock->expects($this->exactly(1))
//                    ->method('pushIdentifyAndTrackEvent')
//                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
//                        $this->assertNotNull($properties);
//                        $this->assertTrue(in_array($eventName, ["JPMC_IMPORT_FLOW_RECON.FILE_RECEIVED"], true));
//                    }));
//
//        $input = [
//            'partner' => 'jpmc',
//        ];
//
//        $lambdaRequest = [
//            'url'     => '/settlements/jpmc/repat',
//            'content' => $input,
//            'method'  => 'POST',
//            'files'   => [
//                'file' => $uploadedFile,
//            ],
//        ];
//
//        $this->ba->h2hAuth();
//        $content = $this->makeRequestAndGetContent($lambdaRequest);
//
//        $this->assertTrue($content['success']);
//
//        $repatriationEntity = $this->getLastEntity('settlement_international_repatriation', true);
//        $this->assertEquals($settlement['amount'], $repatriationEntity['amount']);
//        $this->assertEquals('INR', $repatriationEntity['currency']);
//        $this->assertEquals($settlement['id'], $repatriationEntity['settlement_ids'][0]);
//        $this->assertEquals('USD', $repatriationEntity['credit_currency']);
//    }

    public function testJpmcRepatriationDifferentFile()
    {
        [$settlement, $txn1, $txn2] = $this->setupJpmcSettlement();

        $entries = [
            [
                'Invoices' => $this->getInvoiceDataSheet($settlement, [$txn1['items'][0], $txn2['items'][0]]),
            ],
        ];

        $url = $this->writeToExcelFile($entries[0], 'RAZORPAYIN.DISCREPANCY.EXCEL.2023-10-27.18-20-56', 'files/filestore', ['Invoices']);
        $this->encryptFile($url);
        $uploadedFile = $this->createUploadedFile($url, 'RAZORPAYIN.DISCREPANCY.EXCEL.2023-10-27.18-20-56');

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();
        $this->app->instance('ufh.service', $ufhService);
        $ufhService->shouldReceive('uploadFileAndGetResponse')->times(1);

        $input = [
            'partner' => 'jpmc',
        ];

        $lambdaRequest = [
            'url'     => '/settlements/jpmc/repat',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'file' => $uploadedFile,
            ],
        ];

        $this->ba->h2hAuth();
        $content = $this->makeRequestAndGetContent($lambdaRequest);

        $this->assertTrue($content['success']);
        $this->assertEquals('JPMC sent different reverse file', $content['message']);
    }

    public function testJpmcRepatriationInvalidFile()
    {
        [$settlement, $txn1, $txn2] = $this->setupJpmcSettlement();

        $entries = [
            [
                'Invoices' => $this->getInvoiceDataSheet($settlement, [$txn1['items'][0], $txn2['items'][0]]),
            ],
        ];

        $url = $this->writeToExcelFile($entries[0], 'file', 'files/filestore', ['Invoices']);
        $this->encryptFile($url);
        $uploadedFile = $this->createUploadedFile($url);

        $input = [
            'partner' => 'jpmc',
        ];

        $lambdaRequest = [
            'url'     => '/settlements/jpmc/repat',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'file' => $uploadedFile,
            ],
        ];

        $this->ba->h2hAuth();
        $content = $this->makeRequestAndGetContent($lambdaRequest);

        $this->assertFalse($content['success']);
        $this->assertEquals('JPMC invalid file', $content['message']);
    }

    // Repat file sent by JPMC named as Post Good to Pay report.
    // Sample data, for full list of columns refer PRD or
    // https://docs.google.com/spreadsheets/d/1NSClUvSgVKDSnPwhXzaidE2QgRiT5Cnc/edit?usp=sharing&ouid=115040325753050384971&rtpof=true&sd=true
    protected function getInvoiceDataSheet($settlement, $transactions)
    {
        $invoiceData = [];

        foreach ($transactions as $transaction)
        {
            $invoiceData[] = [
                'Invoice Status'        => 'paid',
                'Date'                  => Carbon::createFromTimestamp($transaction['created_at'])->isoFormat('DD-MM-YYYY'),
                'Order No'              => $transaction['entity_id'] . '|' .  $transaction['settlement_id'],
                'Transaction Amount'    => '120',
                'Transaction Currency'  => 'USD',
                'Exchange Rate'         => '30',
                'Net Invoice Amount'    => ((float)$transaction['credit']) / 100,
            ];
        }

        return $invoiceData;
    }

    protected function createUploadedFile(string $url, $fileName = 'settl_file_0123.xlsx'): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile($url, $fileName, $mime, null, true);
    }

    protected function encryptFile(string $filePath)
    {
        $pgp = $this->getPgpInstance();

        $data = file_get_contents($filePath);

        $encryptedData = $pgp->encryptSign($data);

        file_put_contents($filePath, $encryptedData);
    }

    protected function getPgpInstance()
    {
        $config = $this->config['applications.jpmc'];

        $pgpConfig = [
            'public_key'  => trim(str_replace('\n', PHP_EOL, $config['razorpay_pub_key'])),
            'private_key' => trim(str_replace('\n', PHP_EOL, $config['jpmc_priv_key'])),
            'passphrase'  => 'chase',
        ];

        $pgp = new PGPEncryption($pgpConfig);

        return $pgp;
    }
}
