<?php

namespace RZP\Tests\Functional\Mpan;

use Mockery;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Feature\Constants;
use Illuminate\Support\Facades\DB;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Mpan\Repository as MpanRepo;
use RZP\Models\Batch\Processor\Mpan as MpanBatch;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MpanTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use MpanTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    private function insertIntoMpanTable($mpanData)
    {
        foreach ($mpanData as $mpan)
        {
            // this is as per Services\Mock\CardVault::tokenize()
            $mpan['mpan'] = base64_encode($mpan['mpan']);

            $this->fixtures->create('mpan', $mpan);
        }
    }

    private function setUpMpanTable()
    {
        $mpanData = $this->getMpanData();

        $this->insertIntoMpanTable($mpanData);
    }

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/MpanTestData.php';

        parent::setUp();

        $this->setUpMpanTable();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Constants::ISSUE_MPANS]);
    }

    public function testMpanIssue()
    {
        $response = $this->startTest();

        $issuedMpans = $response['items'];

        foreach ($issuedMpans as $mpanFromResponse)
        {
            $mpanFromDatabase = $this->getDbEntityById(Entity::MPAN, base64_encode($mpanFromResponse[Entity::MPAN]));

            $this->assertEquals($mpanFromDatabase['merchant_id'], self::DEFAULT_MERCHANT_ID);

            $this->assertTrue($mpanFromDatabase['assigned']);
        }
    }

    public function testMpanIssueCountExceedsAllowedLimit()
    {
        $this->startTest();
    }

    public function testMpanIssueInvalidNetwork()
    {
        $this->startTest();
    }

    public function testMpanIssueRequestedCountUnavailable()
    {
        $this->startTest();
    }

    public function testMpanFetch()
    {
        foreach (['Visa', 'MasterCard', 'RuPay'] as $network)
        {
            $mpansIssueRequest = [
                'url'         => '/mpans/issue',
                'method'      => 'post',
                'content'     => [
                    'network'       => $network,
                    'count'         => 3,
                ],
            ];

            $this->makeRequestAndGetContent($mpansIssueRequest);
        }

        $mpansFetchRequest = [
            'url'             => '/mpans',
            'method'          => 'get',
            'content'         => [
                'network'           => 'Visa',
                'count'             => 2,
            ]
        ];

        $fetchMpanResponse = $this->makeRequestAndGetContent($mpansFetchRequest);

        $fetchedMpans = $fetchMpanResponse['items'];

        $this->assertEquals(2, sizeof($fetchedMpans));

        foreach ($fetchedMpans as $fetchedMpan)
        {
            $mpanFromDatabase = $this->getDbEntityById(Entity::MPAN, base64_encode($fetchedMpan[Entity::MPAN]));

            $this->assertEquals($mpanFromDatabase[\RZP\Models\Mpan\Entity::MERCHANT_ID], self::DEFAULT_MERCHANT_ID);

            $this->assertTrue($mpanFromDatabase[\RZP\Models\Mpan\Entity::ASSIGNED]);
        }
    }

    public function testMpanBulk()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(3, count($response['items']));

        foreach ($this->testData[__FUNCTION__]['unmasked_mpans'] as $row)
        {
            foreach (MpanBatch::BATCH_HEADER_NETWORK_CODE_MAP  as $header => $network)
            {
                $mpanFromDatabase = $this->getDbEntityById(Entity::MPAN, base64_encode($row[$header]));

                $this->assertNull($mpanFromDatabase->getMerchantId());

                $this->assertFalse($mpanFromDatabase->isAssigned());

                $this->assertEquals($network, $mpanFromDatabase->getNetwork());
            }
        }
    }

    public function testMpanBulkInvalidMpan()
    {
        $this->ba->batchAppAuth();

        $beforeCount = Db::table('mpan')
            ->count();

        $response = $this->startTest();

        $afterCount = Db::table('mpan')
            ->count();

        // even though input is 3 rows(3x3 networks = 9 mpans)
        // the 2nd row has invalid input.
        // the 3rd row contains an already existing mpan
        // test is to check that entire row is failed
        $this->assertEquals($beforeCount + 3, $afterCount);


        $row = $response['items']['2'];

        $this->assertStringContainsString("SQLSTATE[23000]: Integrity constraint violation", $row['error']['description']);
    }

    public function testMpanTokenizeExistingMpans()
    {
        // adds mpans in table original form(non tokenized) to test mpan migration cron
        $mpanData = [
            [
                'mpan'    => '5114901005005799',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '4114901005005823',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '6114901005005856',
                'network' => 'RuPay'
            ],
        ];

        foreach ($mpanData as $mpan)
        {
            $this->fixtures->create('mpan', $mpan);
        }

        $this->ba->cronAuth();

        $this->startTest();

        foreach($mpanData as $mpan)
        {
            $mpanFromDb = $this->getDbEntity('mpan', ['mpan' => base64_encode($mpan['mpan'])]);

            $this->assertNotNull($mpanFromDb);
        }
    }

    public function testMpanTokenizeExistingMpansInputValidationFailure()
    {
        $this->ba->cronAuth();

        $res = $this->startTest();
    }

    public function testMpanTokenizeExistingMpansOneCardVaultRequestFails()
    {
        // adds mpans in table original form(non tokenized) to test mpan migration cron
        $mpanData = [
            [
                'mpan'    => '5114901005005799',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '4114901005005823',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '6114901005005856',
                'network' => 'RuPay'
            ],
        ];

        foreach ($mpanData as $mpan)
        {
            $this->fixtures->create('mpan', $mpan);
        }

        $cardVault = Mockery::mock('RZP\Services\CardVault');

        $this->app->instance('mpan.cardVault', $cardVault);

        $cardVault->shouldReceive('tokenize')
                ->with(Mockery::type('array'))
                ->andReturnUsing
                (function ($input)
                {
                    // fail tokenization for one mpan
                    if ($input['secret'] === '4114901005005823')
                    {
                        throw new Exception\ServerErrorException(
                            'Request timedout at card vault service',
                            ErrorCode::SERVER_ERROR);
                    }

                    $token = base64_encode($input['secret']);

                    return $token;
                });

        $this->ba->cronAuth();

        $this->startTest();

        foreach($mpanData as $mpan)
        {
            if ($mpan['mpan'] === '4114901005005823')
            {
                $mpanSavedInDb = $mpan['mpan'];
            }
            else
            {
                $mpanSavedInDb = base64_encode($mpan['mpan']);
            }

            $mpanFromDb = $this->getDbEntity('mpan', ['mpan' => $mpanSavedInDb]);

            $this->assertNotNull($mpanFromDb);
        }
    }

    public function testQrCodeTokenizeExistingMpans()
    {
        $this->ba->cronAuth();

        $originalQrString = '000201010211021652873468239864230415428734682398642061662873468239864230827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFRu0HVS8RAvIgU5204539953033565802IN5905atque6009BANGALORE610656003062270514FRu0HVS8RAvIgU0705abcde6304E308';

        $qrCode = $this->fixtures->create('qr_code', [
            'qr_string' => $originalQrString,
            ]);

        $this->startTest();

        $qrCode->reload();

        $this->assertEquals(1, $qrCode['mpans_tokenized']);

        $dbQrString = $qrCode['qr_string'];
        $qrString = $qrCode->getQrString();

        $this->assertEquals($originalQrString, $qrString);
        $this->assertNotEquals($originalQrString, $dbQrString);

        $this->assertEquals($qrString, \RZP\Models\QrCode\Entity::getQrStringWithDetokenizedMpans($dbQrString));
    }

    // qr_string does not have mpan tags
    public function testQrCodeTokenizeExistingMpansHavingNoMpanTag()
    {
        $this->ba->cronAuth();

        $originalQrString = '0002010102110827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFRu0HVS8RAvIgU5204539953033565802IN5905atque6009BANGALORE610656003062270514FRu0HVS8RAvIgU0705abcde6304E308';

        $qrCode = $this->fixtures->create('qr_code', [
            'qr_string' => $originalQrString,
            ]);

        $this->startTest();

        $qrCode->reload();

        $this->assertEquals(1, $qrCode['mpans_tokenized']);

        $dbQrString = $qrCode['qr_string'];

        $qrString = $qrCode->getQrString();

        $this->assertEquals($originalQrString, $qrString);
        $this->assertEquals($originalQrString, $dbQrString);
    }

    // qr_string have mpan tags only for 1 network
    public function testQrCodeTokenizeExistingMpansHavingOneMpanTag()
    {
        $this->ba->cronAuth();

        $originalQrString = '000201021652873468239864230102110827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFRu0HVS8RAvIgU5204539953033565802IN5905atque6009BANGALORE610656003062270514FRu0HVS8RAvIgU0705abcde6304E308';

        $qrCode = $this->fixtures->create('qr_code', [
            'qr_string' => $originalQrString,
            ]);

        $res = $this->startTest();

        $qrCode->reload();

        $this->assertEquals(1, $qrCode['mpans_tokenized']);
        $this->assertEquals([$qrCode->getId()], $res['qr_string_mpan_tokenization_success_ids']);
        $dbQrString = $qrCode['qr_string'];

        $qrString = $qrCode->getQrString();

        $this->assertEquals($originalQrString, $qrString);
        $this->assertNotEquals($originalQrString, $dbQrString);
    }

    public function testQrCodeTokenizeExistingMpansCardVaultRequestFails()
    {
        $this->ba->cronAuth();

        $originalQrString = '000201010211021652873468239864230415428734682398642061662873468239864230827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFRu0HVS8RAvIgU5204539953033565802IN5905atque6009BANGALORE610656003062270514FRu0HVS8RAvIgU0705abcde6304E308';

        $qrCode = $this->fixtures->create('qr_code', [
            'qr_string' => $originalQrString,
            ]);

        $cardVault = Mockery::mock('RZP\Services\CardVault');

        $this->app->instance('mpan.cardVault', $cardVault);

        $cardVault->shouldReceive('tokenize')
                ->with(Mockery::type('array'))
                ->andReturnUsing
                (function ($input)
                {
                    // fail tokenization for one mpan
                    if ($input['secret'] === '428734682398642')
                    {
                        throw new Exception\ServerErrorException(
                            'Request timedout at card vault service',
                            ErrorCode::SERVER_ERROR);
                    }

                    $token = base64_encode($input['secret']);

                    return $token;
                });


        $res = $this->startTest();
        $this->assertEquals([$qrCode->getId()], $res['qr_string_mpan_tokenization_failed_ids']);

        $qrCode->reload();

        $this->assertNull($qrCode['mpans_tokenized']);

        $dbQrString = $qrCode['qr_string'];

        $this->assertEquals($originalQrString, $dbQrString);
    }
}
