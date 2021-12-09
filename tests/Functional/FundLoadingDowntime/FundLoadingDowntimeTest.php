<?php

namespace Functional\FundLoadingDowntime;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Mail;
use RZP\Models\FundLoadingDowntime\Entity;
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
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'RBI',
            'channel'    => 'all',
            'mode'       => 'NEFT',
            'start_time' => 1637930829,
            'end_time'   => 1732423321,
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
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'all',
            'mode'       => 'NEFT',
            'start_time' => 1538952230,
            'end_time'   => 1638952230,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testUpdateDuplicateEntity()
    {
        $attributes = [
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'yesbank',
            'mode'       => 'NEFT',
            'start_time' => 1637930829,
            'end_time'   => 1637940829,
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
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'created_by' => 'Chirag.Chiranjib'
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
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'UPI',
            'start_time' => 1537930829,
            'end_time'   => 1737930829,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100002downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => 1537930829,
            'end_time'   => null,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100003downtime',
            'type'       => 'Sudden Downtime',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'RTGS',
            'start_time' => 1537930829,
            'end_time'   => 1557930829,
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
            'start_time'       => Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'created_by'       => 'chirag',
            'downtime_message' => 'All banks NEFT payments are down',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => Carbon::now(Timezone::IST)->addSeconds(20000)->getTimestamp(),
            'created_by' => 'Chirag.Chiranjib'
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
            'id'         => '100000downtime',
            'type'       => 'Sudden Downtime',
            'source'     => 'RBI',
            'channel'    => 'all',
            'mode'       => 'NEFT',
            'start_time' => Carbon::now(Timezone::IST)->subSeconds(20000)->getTimestamp(),
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(10000)->getTimestamp(),
            'created_by' => 'Chirag',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => Carbon::now(Timezone::IST)->addSeconds(40000)->getTimestamp(),
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(50000)->getTimestamp(),
            'created_by' => 'Chirag',
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "&start_time=" . Carbon::now(Timezone::IST)->subSeconds(30000)->getTimestamp()
               . "&end_time=" . Carbon::now(Timezone::IST)->addSeconds(30000)->getTimestamp();

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreationFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');

        $this->createBalance('xbalance111111');

        $this->startTest();

        $fundLoadingDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $downtimeInputs = &$this->testData[__FUNCTION__]['request']['content']['downtime_inputs'];

        $expectedFundLoadingDowntimes = [];

        foreach($downtimeInputs['durations_and_modes'] as $durationAndModes)
        {
            $downtime[Entity::TYPE] = $downtimeInputs[Entity::TYPE];
            $downtime[Entity::SOURCE] = $downtimeInputs[Entity::SOURCE];
            $downtime[Entity::CHANNEL] = $downtimeInputs[Entity::CHANNEL];

            foreach($durationAndModes['modes'] as $mode)
            {
                $downtime[Entity::START_TIME] = $durationAndModes[Entity::START_TIME];
                $downtime[Entity::END_TIME] = $durationAndModes[Entity::END_TIME] ?? null;
                $downtime[Entity::MODE] = $mode;

                $expectedFundLoadingDowntimes[] = $downtime;
            }
        }
        $this->assertEquals(count($fundLoadingDowntimes), count($expectedFundLoadingDowntimes));

        foreach($expectedFundLoadingDowntimes as $key => $fundLoadingDowntime)
        {
            $this->assertArraySelectiveEquals($fundLoadingDowntime, $fundLoadingDowntimes[$key]);
        }

    }

    public function testUpdationFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');

        $this->createBalance('xbalance111111');

        $attributes = [
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => Carbon::now(Timezone::IST)->subSeconds(10000)->getTimestamp(),
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(10000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'NEFT',
            'start_time' => Carbon::now(Timezone::IST)->addSeconds(10000)->getTimestamp(),
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(20000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();

        $updateDetails = &$this->testData[__FUNCTION__]['request']['content']['update_details'];

        $updatedFundLoadingDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $expectedFundLoadingDowntimes = [
            [
                Entity::ID         => $updateDetails[0][Entity::ID],
                Entity::START_TIME => $updateDetails[0][Entity::START_TIME],
                Entity::END_TIME   => $updateDetails[0][Entity::END_TIME],
                Entity::TYPE       => $attributes[Entity::TYPE],
                Entity::SOURCE     => $attributes[Entity::SOURCE],
                Entity::CHANNEL    => $attributes[Entity::CHANNEL]
            ],
            [
                Entity::ID         => $updateDetails[1][Entity::ID],
                Entity::START_TIME => $updateDetails[1][Entity::START_TIME],
                Entity::END_TIME   => $updateDetails[1][Entity::END_TIME],
                Entity::TYPE       => $attributes[Entity::TYPE],
                Entity::SOURCE     => $attributes[Entity::SOURCE],
                Entity::CHANNEL    => $attributes[Entity::CHANNEL]
            ]
        ];

       foreach($expectedFundLoadingDowntimes as $key => $downtime)
        {
            $this->assertArraySelectiveEquals($downtime, $updatedFundLoadingDowntimes[$key]);
        }
    }

    public function testResolutionFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');

        $this->createBalance('xbalance111111');

        $firstDowntime = [
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'NEFT',
            'start_time' => 1632413321,
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(20000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $firstDowntime);

        $secondDowntime = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => 1632413321,
            'end_time'   => Carbon::now(Timezone::IST)->addSeconds(10000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $secondDowntime);

        $this->startTest();

        $resolutionDetails = &$this->testData[__FUNCTION__]['request']['content']['update_details'];

        $resolvedDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $expectedResolvedDowntimes = [
            [
                Entity::TYPE       => $firstDowntime[Entity::TYPE],
                Entity::SOURCE     => $firstDowntime[Entity::SOURCE],
                Entity::CHANNEL    => $firstDowntime[Entity::CHANNEL],
                Entity::MODE       => $firstDowntime[Entity::MODE],
                Entity::START_TIME => $firstDowntime[Entity::START_TIME],
                Entity::END_TIME   => $resolutionDetails[0][Entity::END_TIME],
            ],
            [
                Entity::TYPE       => $secondDowntime[Entity::TYPE],
                Entity::SOURCE     => $secondDowntime[Entity::SOURCE],
                Entity::CHANNEL    => $secondDowntime[Entity::CHANNEL],
                Entity::MODE       => $secondDowntime[Entity::MODE],
                Entity::START_TIME => $secondDowntime[Entity::START_TIME],
                Entity::END_TIME   => $resolutionDetails[1][Entity::END_TIME],
            ]
        ];

        foreach($expectedResolvedDowntimes as $key => $downtime)
        {
            $this->assertArraySelectiveEquals($downtime, $resolvedDowntimes[$key]);
        }

    }

    public function testCancellationFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');

        $this->createBalance('xbalance111111');

        $attributes = [
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'NEFT',
            'start_time' => 1632413321,
            'end_time'   => 1632443321,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => 1632413321,
            'end_time'   => 1632443321,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100002downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'UPI',
            'start_time' => 1632413321,
            'end_time'   => 1632443321,
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();

        $remainingDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $this->assertEquals(1, count($remainingDowntimes));

        $this->assertArraySelectiveEquals($attributes, $remainingDowntimes[0]);
    }

    public function createMerchantConfigs($mid, $emails, $mobiles)
    {
        $merchantConfigs = [
            'merchant_id'                 => $mid,
            'notification_type'           => 'fund_loading_downtime',
            'notification_emails'         => implode(',', $emails),
            'notification_mobile_numbers' => implode(',', $mobiles),
        ];

        $this->fixtures->create('merchant_notification_config', $merchantConfigs);
    }

    public function createVirtualAccount($merchantId, $balanceId, $id)
    {
        $virtualAccount = [
            'status'          => 'active',
            'balance_id'      => $balanceId,
            'bank_account_id' => null,
            'merchant_id'     => $merchantId,
            'id'              => $id
        ];

        $this->fixtures->create('virtual_account', $virtualAccount);
    }

    public function createBankAccount($merchantId, $entityId, $accountNumber)
    {
        $bankAccount = [
            'merchant_id'    => $merchantId,
            'entity_id'      => $entityId,
            'account_number' => $accountNumber
        ];

        $this->fixtures->create('bank_account', $bankAccount);
    }

    public function createBalance($id)
    {
        $balance = [
            'id'           => $id,
            'type'         => 'banking',
            'account_type' => 'shared'
        ];

        $this->fixtures->create('balance', $balance);
    }

}
