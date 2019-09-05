<?php

namespace RZP\Tests\Functional\Mpan;

use RZP\Constants\Entity;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

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
            $this->fixtures->create('mpan', $mpan);
        }
    }

    private function setUpMpanTable()
    {
        $mpanData = $this->getMpanData();

        $this->insertIntoMpanTable($mpanData);
    }

    public function setUp()
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
            $mpanFromDatabase = $this->getDbEntityById(Entity::MPAN, $mpanFromResponse[Entity::MPAN]);

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
            $mpanFromDatabase = $this->getDbEntityById(Entity::MPAN, $fetchedMpan[Entity::MPAN]);

            $this->assertEquals($mpanFromDatabase[\RZP\Models\Mpan\Entity::MERCHANT_ID], self::DEFAULT_MERCHANT_ID);

            $this->assertTrue($mpanFromDatabase[\RZP\Models\Mpan\Entity::ASSIGNED]);
        }
    }
}
