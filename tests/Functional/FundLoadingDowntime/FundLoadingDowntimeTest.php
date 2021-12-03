<?php

namespace Functional\FundLoadingDowntime;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class FundLoadingDowntimeTest extends TestCase
{
    use HeimdallTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FundLoadingDowntimeTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id'   => $this->org->getId(),
            'hostname' => 'dashboard.sampleorg.dev',
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testCreateEntity()
    {
        $this->startTest();
    }

    public function testCreateDuplicateEntity()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'start_time'       => 1632423321,
            'end_time'         => 1732423321,
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down due to RBI',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testCreateEntityEndTimeException()
    {
        $this->startTest();
    }

    public function testCreateEntityChannelException()
    {
        $this->startTest();
    }

    public function testCreateEntityModeException()
    {
        $this->startTest();
    }

    public function testUpdateEntity()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down due to RBI',
        ];
        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testUpdateDuplicateEntity()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'yesbank',
            'mode'             => 'NEFT',
            'start_time'       => 1637930829,
            'end_time'         => 1637940829,
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down due to RBI',
        ];
        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testFetchById()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testFetchAll()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'               => '100001downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'IMPS',
            'created_by'       => 'Chirag.Chiranjib'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testDeleteEntity()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testFetchActiveDowntimesWithCurrentTimeAndParameters()
    {
        $attributes = [
            'id'               => '100001downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'UPI',
            'start_time'       =>  Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'end_time'         =>  Carbon::tomorrow(Timezone::IST)->addSeconds(10000)->getTimestamp(),
            'created_by'       => 'chirag'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'               => '100002downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'IMPS',
            'start_time'       =>  Carbon::now(Timezone::IST)->subSeconds(30000)->getTimestamp(),
            'end_time'         =>  null,
            'created_by'       => 'chirag'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'               => '100003downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'RTGS',
            'start_time'       =>  Carbon::now(Timezone::IST)->subSeconds(30000)->getTimestamp(),
            'end_time'         =>  Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'created_by'       => 'chirag'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testFetchActiveDowntimesWithStartTimeAndParameters()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'start_time'       =>  Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'               => '100001downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'IMPS',
            'start_time'       =>  Carbon::now(Timezone::IST)->addSeconds(20000)->getTimestamp(),
            'created_by'       => 'Chirag.Chiranjib'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "&start_time=" . Carbon::yesterday(Timezone::IST)->getTimestamp();

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchActiveDowntimesWithStartAndEndTimeAndParameters()
    {
        $attributes = [
            'id'               => '100000downtime',
            'type'             => 'Sudden Downtime',
            'source'           => 'RBI',
            'channel'          => 'all',
            'mode'             => 'NEFT',
            'start_time'       => Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'end_time'         => Carbon::now(Timezone::IST)->addSeconds(10000)->getTimestamp(),
            'created_by'       => 'Chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'               => '100001downtime',
            'type'             => 'Scheduled Maintenance Activity',
            'source'           => 'Partner Bank',
            'channel'          => 'icicibank',
            'mode'             => 'IMPS',
            'start_time'       =>  Carbon::now(Timezone::IST)->addSeconds(20000)->getTimestamp(),
            'end_time'         => Carbon::tomorrow(Timezone::IST)->subSeconds(10000)->getTimestamp(),
            'created_by'       => 'Chirag'
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "&start_time=" . Carbon::yesterday(Timezone::IST)->getTimestamp()
               ."&end_time=". Carbon::tomorrow(Timezone::IST)->getTimestamp();

        $data['request']['url'] = $url;

        $this->startTest();
    }
}
