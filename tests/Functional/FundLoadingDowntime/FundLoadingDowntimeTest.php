<?php

namespace Functional\FundLoadingDowntime;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundLoadingDowntime\Entity;
use RZP\Models\FundLoadingDowntime\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Mail\FundLoadingDowntime\FundLoadingDowntimeMail;

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

        $downtime = $this->getDbLastEntity('fund_loading_downtimes');

        $this->assertEquals($downtime['id'], $attributes['id']);

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

        Carbon::setTestNow(Carbon::createFromTimestamp(1637930829,Timezone::IST));

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'UPI',
            'start_time' => 1537930829,
            'end_time'   => 1737930829,
            'created_at' => Carbon::now(Timezone::IST)->subSeconds(2000)->getTimestamp(),
            'updated_at' => Carbon::now(Timezone::IST)->subSeconds(2000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100002downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => 1537920829,
            'end_time'   => null,
            'created_at' => Carbon::now(Timezone::IST)->subSeconds(3000)->getTimestamp(),
            'updated_at' => Carbon::now(Timezone::IST)->subSeconds(3000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100003downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'RTGS',
            'start_time' => 1537940829,
            'end_time'   => 1737930829,
            'created_at' => Carbon::now(Timezone::IST)->subSeconds(1000)->getTimestamp(),
            'updated_at' => Carbon::now(Timezone::IST)->subSeconds(1000)->getTimestamp(),
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();
    }

    public function testFetchActiveDowntimesWithStartTimeAndParameters()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1637930829,Timezone::IST));

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

        $url = $data['request']['url'] . "&start_time=" . Carbon::now(Timezone::IST)->subSeconds(30000)->getTimestamp();

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchActiveDowntimesWithStartAndEndTimeAndParameters()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1637930829,Timezone::IST));

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
        $this->createMerchantConfigs('10000000000011', ['sagnik2@razorpay.com', 'sagnik11@gmail.com'], ['9468620912', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        //Since 3 distinct mobile numbers are present, sendSMS call should be made 3 times.
        $storkMock->shouldReceive('sendSMS')->times(3)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {

                      $expectedSmsParams                  = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.creation_2.v1';
                      $expectedSmsParams['contentParams'] = [
                          'channel' => 'ICICI',
                          'start1'  => '22Sep 05:52 pm',
                          'end1'    => 'to 22Sep 06:08 pm',
                          'modes1'  => 'NEFT,UPI and',
                          'start2'  => '22Sep 06:08 pm',
                          'end2'    => 'to 22Sep 06:25 pm',
                          'modes2'  => 'RTGS',
                      ];

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

        (new AdminService)->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'sms.fund_loading_downtime.creation_1.v1' => [],
        ]]);

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

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::CREATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.creation.multiple', $mail->templateName);

            $expectedDowntimeParams = [
                'type'                => 'Scheduled Maintenance Activity',
                'source'              => 'Partner Bank',
                'channel'             => "ICICI",
                'durations_and_modes' => [
                    0 => [
                        'start_time' => '22 Sep 05:52 pm',
                        'end_time'   => 'to 22 Sep 06:08 pm',
                        'modes'      => 'NEFT, UPI',
                    ],
                    1 => [
                        'start_time' => '22 Sep 06:08 pm',
                        'end_time'   => 'to 22 Sep 06:25 pm',
                        'modes'      => 'RTGS',
                    ]
                ],
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::rotatingLight . '[Downtime Alert] : RazorpayX Lite via ICICI | 22 Sep 05:52 pm to 22 Sep 06:08 pm & 22 Sep 06:08 pm to 22 Sep 06:25 pm',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik2@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testCreationFlowWithSMSV3Template()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik2@razorpay.com', 'sagnik11@gmail.com'], ['9468620912', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        //Since 3 distinct mobile numbers are present, sendSMS call should be made 3 times.
        $storkMock->shouldReceive('sendSMS')->times(3)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {

                      $expectedSmsParams                  = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.creation_1.v1';

                      if ($smsPayload['ownerId'] == '10000000000011')
                      {
                          $expectedSmsParams['contentParams'] = [
                              'channel' => 'ICICI',
                              'start1'  => '22Sep 05:52 pm',
                              'end1'    => 'to 22Sep 06:08 pm',
                              'modes1'  => 'NEFT,UPI',
                          ];
                      }
                      else
                      {
                          $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.creation_1.v2';

                          $expectedSmsParams['contentParams'] = [
                              'channel' => 'ICICI',
                              'timings'  => '22Sep 05:52 pm to 22Sep 06:08 pm',
                              'modes'  => 'NEFT,UPI',
                          ];
                      }

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

        (new AdminService)->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'sms.fund_loading_downtime.creation_1.v1' => ['10000000000000'],
        ]]);

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

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::CREATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.creation', $mail->templateName);

            $expectedDowntimeParams = [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => "ICICI",
                'start_time' => '22 Sep 05:52 pm',
                'end_time'   => 'to 22 Sep 06:08 pm',
                'modes'      => 'NEFT, UPI',
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::rotatingLight . '[Downtime Alert] : RazorpayX Lite via ICICI | 22 Sep 05:52 pm to 22 Sep 06:08 pm',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik2@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testUpdationFlow()
    {
        Mail::fake();

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik2@razorpay.com', 'sagnik11@gmail.com'], ['9468620912', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        //Since 3 distinct mobile numbers are present, sendSMS call should be made 3 times.
        $storkMock->shouldReceive('sendSMS')->times(3)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {
                      $expectedSmsParams                  = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.update_1.v1';
                      $expectedSmsParams['contentParams'] = [
                          'channel' => 'ICICI',
                          'start1'  => '30Dec 12:00 am',
                          'end1'    => 'to 31Dec 12:00 am',
                          'modes1'  => 'IMPS,NEFT'
                      ];

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

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

        foreach ($expectedFundLoadingDowntimes as $key => $downtime)
        {
            $this->assertArraySelectiveEquals($downtime, $updatedFundLoadingDowntimes[$key]);
        }

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use(& $recipients)
        {
            $this->assertSame(Constants::UPDATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.updation', $mail->templateName);

            $expectedDowntimeParams = [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => "ICICI",
                'start_time' => '30 Dec 12:00 am',
                'end_time'   => 'to 31 Dec 12:00 am',
                'modes'      => 'IMPS, NEFT',
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::rotatingLight . '[Downtime Updated] : RazorpayX Lite via ICICI | 30 Dec 12:00 am to 31 Dec 12:00 am',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik2@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testUpdationFlowWithSMSV3Template()
    {
        Mail::fake();

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik2@razorpay.com', 'sagnik11@gmail.com'], ['9468620912', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        //Since 3 distinct mobile numbers are present, sendSMS call should be made 3 times.
        $storkMock->shouldReceive('sendSMS')->times(3)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {
                      $expectedSmsParams                  = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.update_1.v1';

                      if ($smsPayload['ownerId'] == '10000000000011')
                      {
                          $expectedSmsParams['contentParams'] = [
                              'channel' => 'ICICI',
                              'start1'  => '30Dec 12:00 am',
                              'end1'    => 'to 31Dec 12:00 am',
                              'modes1'  => 'IMPS,NEFT'
                          ];
                      }
                      else
                      {
                          $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.update_1.v2';

                          $expectedSmsParams['contentParams'] = [
                              'channel' => 'ICICI',
                              'timings'  => '30Dec 12:00 am to 31Dec 12:00 am',
                              'modes'  => 'IMPS,NEFT',
                          ];
                      }

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

        (new AdminService)->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'sms.fund_loading_downtime.update_1.v1' => ['10000000000000'],
        ]]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdationFlow'];

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

        foreach ($expectedFundLoadingDowntimes as $key => $downtime)
        {
            $this->assertArraySelectiveEquals($downtime, $updatedFundLoadingDowntimes[$key]);
        }

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use(& $recipients)
        {
            $this->assertSame(Constants::UPDATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.updation', $mail->templateName);

            $expectedDowntimeParams = [
                'type'       => 'Scheduled Maintenance Activity',
                'source'     => 'Partner Bank',
                'channel'    => "ICICI",
                'start_time' => '30 Dec 12:00 am',
                'end_time'   => 'to 31 Dec 12:00 am',
                'modes'      => 'IMPS, NEFT',
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::rotatingLight . '[Downtime Updated] : RazorpayX Lite via ICICI | 30 Dec 12:00 am to 31 Dec 12:00 am',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik2@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testUpdationFlowWithMultipleDurations()
    {
        Mail::fake();

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik2@razorpay.com', 'sagnik11@gmail.com'], ['9468620912', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');


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

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        //Since 3 distinct mobile numbers are present, sendSMS call should be made 3 times.
        $storkMock->shouldReceive('sendSMS')->times(3)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {
                      $expectedSmsParams                  = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']  = 'sms.fund_loading_downtime.update_2.v1';
                      $expectedSmsParams['contentParams'] = [
                          'channel' => 'ICICI',
                          'start1'  => '30Dec 12:00 am',
                          'end1'    => 'to 31Dec 12:00 am',
                          'modes1'  => 'IMPS and',
                          'start2'  => '30Dec 01:00 am',
                          'end2'    => 'to 30Dec 05:00 am',
                          'modes2'  => 'NEFT',
                      ];

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

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

        foreach ($expectedFundLoadingDowntimes as $key => $downtime)
        {
            $this->assertArraySelectiveEquals($downtime, $updatedFundLoadingDowntimes[$key]);
        }

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::UPDATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.updation.multiple', $mail->templateName);

            $expectedDowntimeParams = [
                'type'                => 'Scheduled Maintenance Activity',
                'source'              => 'Partner Bank',
                'channel'             => "ICICI",
                'durations_and_modes' => [
                    0 => [
                        'start_time' => '30 Dec 12:00 am',
                        'end_time'   => 'to 31 Dec 12:00 am',
                        'modes'      => 'IMPS',
                    ],
                    1 => [
                        'start_time' => '30 Dec 01:00 am',
                        'end_time'   => 'to 30 Dec 05:00 am',
                        'modes'      => 'NEFT',
                    ]
                ],
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::rotatingLight . '[Downtime Updated] : RazorpayX Lite via ICICI | 30 Dec 12:00 am to 31 Dec 12:00 am & 30 Dec 01:00 am to 30 Dec 05:00 am',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik2@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testResolutionFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('sendSMS')->times(2)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {
                      $expectedSmsParams                             = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']             = 'sms.fund_loading_downtime.resolution.v1';
                      $expectedSmsParams['contentParams']['channel'] = 'ICICI';
                      $expectedSmsParams['contentParams']['modes']  = 'NEFT,IMPS';

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

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

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::RESOLUTION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.resolution', $mail->templateName);

            $expectedDowntimeParams = [
                'channel' => "ICICI",
                'modes'   => 'NEFT, IMPS'
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            $this->assertArraySelectiveEquals(
                [
                    'address' => "sagnik1@razorpay.com"
                ],
                $mail->to[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::whiteCheckMark . '[Downtime Resolved] : RazorpayX Lite via ICICI',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testCancellationFlow()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        $recipients = [];

        // email related assertions
        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::CANCELLATION, $mail->flowType);
            $this->assertSame('fund_loading_downtime.cancellation', $mail->templateName);

            $expectedDowntimeParams = [
                'type'                => "Scheduled Maintenance Activity",
                'channel'             => "ICICI",
                'start_time'          => '23 Sep 09:38 pm',
                'end_time'            => 'to 24 Sep 05:58 am',
                'modes'               => 'NEFT, IMPS'
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            $this->assertArraySelectiveEquals(
                [
                    'address' => "sagnik1@razorpay.com"
                ],
                $mail->to[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::whiteCheckMark . '[Downtime Cancelled] : RazorpayX Lite via ICICI',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
    }

    public function testCancellationFlowWithNeitherSMSNorEmailNotifications()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        Mail::assertNotQueued(FundLoadingDowntimeMail::class);
    }

    public function testCancellationFlowWithOnlySmsNotification()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

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

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('sendSMS')->times(2)
                  ->withArgs(function($mode, $smsPayload, $mockInTestMode) {
                      $expectedSmsParams                             = $this->getDefaultSmsParams();
                      $expectedSmsParams['templateName']             = 'sms.fund_loading_downtime.cancelation.v1';
                      $expectedSmsParams['contentParams']['channel'] = 'ICICI';
                      $expectedSmsParams['contentParams']['start1']  = '23Sep 09:38 pm';
                      $expectedSmsParams['contentParams']['end1']    = 'to 24Sep 05:58 am';
                      $expectedSmsParams['contentParams']['modes1']  = 'NEFT,IMPS';

                      $this->assertEquals('test', $mode);
                      $this->assertEquals(false, $mockInTestMode);
                      $this->assertArraySelectiveEquals($expectedSmsParams, $smsPayload);

                      return true;
                  })->andReturn(['message_id' => '10000000000msg']);

        $this->startTest();

        $remainingDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $this->assertCount(1, $remainingDowntimes);

        $this->assertArraySelectiveEquals($attributes, $remainingDowntimes[0]);

        Mail::assertNotQueued(FundLoadingDowntimeMail::class);
    }

    public function testCancellationFlowWithOnlyEmailNotification()
    {
        Mail::fake();

        $this->createMerchantConfigs('10000000000000', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);
        $this->createMerchantConfigs('10000000000011', ['sagnik1@razorpay.com', 'sagnik11@gmail.com'], ['9468620910', '9468620911']);

        $this->createBankAccount('10000000000000', 'va111111111111', '34340000000000');
        $this->createBankAccount('10000000000011', 'va222222222222', '56560000000000');

        $this->createBalance('xbalance111111', '10000000000000');
        $this->createBalance('xbalance222222', '10000000000011');

        $this->createVirtualAccount('10000000000000', 'xbalance111111', 'va111111111111');
        $this->createVirtualAccount('10000000000011', 'xbalance222222', 'va222222222222');

        $attributes = [
            'id'         => '100000downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'NEFT',
            'start_time' => 1632413321, // Thursday, 23 September 2021 21:38 pm
            'end_time'   => 1632443321, // Friday, 24 September 2021 05:58 AM
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100001downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'IMPS',
            'start_time' => 1632413321, // Thursday, 23 September 2021 21:38 pm
            'end_time'   => 1632443321, // Friday, 24 September 2021 05:58:AM
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $attributes = [
            'id'         => '100002downtime',
            'type'       => 'Scheduled Maintenance Activity',
            'source'     => 'Partner Bank',
            'channel'    => 'icicibank',
            'mode'       => 'UPI',
            'start_time' => 1632413321, // Thursday, 23 September 2021 21:38 pm
            'end_time'   => 1632443321, // Friday, 24 September 2021 05:58 AM
        ];

        $this->fixtures->create('fund_loading_downtimes', $attributes);

        $this->startTest();

        $remainingDowntimes = $this->getDbEntities('fund_loading_downtimes')->toArray();

        $this->assertCount(1, $remainingDowntimes);

        $this->assertArraySelectiveEquals($attributes, $remainingDowntimes[0]);

        $recipients = [];

        Mail::assertQueued(FundLoadingDowntimeMail::class, function($mail) use (& $recipients)
        {
            $this->assertSame(Constants::CANCELLATION, $mail->flowType);

            $expectedDowntimeParams = [
                'type'       => "Scheduled Maintenance Activity",
                'channel'    => "ICICI",
                'start_time' => '23 Sep 09:38 pm',
                'end_time'   => 'to 24 Sep 05:58 am',
                'modes'      => 'NEFT, IMPS'
            ];

            $this->assertArraySelectiveEquals($expectedDowntimeParams, $mail->emailParams);

            $this->assertSame(
                [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
                $mail->from[0]
            );

            $this->assertArraySelectiveEquals(
                [
                    'address' => "sagnik1@razorpay.com"
                ],
                $mail->to[0]
            );

            foreach ($mail->to as $recipient)
            {
                array_push($recipients, $recipient['address']);
            }

            $this->assertSame(FundLoadingDowntimeMail::whiteCheckMark . '[Downtime Cancelled] : RazorpayX Lite via ICICI',
                              $mail->subject);

            return true;
        });

        $expectedRecipients = ['sagnik1@razorpay.com', 'sagnik11@gmail.com'];
        $this->assertEqualsCanonicalizing($expectedRecipients, array_unique($recipients));
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

    public function createVirtualAccount($merchantId, $balanceId, $bankAccountId)
    {
        $virtualAccount = [
            'status'          => 'active',
            'balance_id'      => $balanceId,
            'bank_account_id' => $bankAccountId,
            'merchant_id'     => $merchantId,
            'id'              => null
        ];

        $this->fixtures->create('virtual_account', $virtualAccount);
    }

    public function createBankAccount($merchantId, $id, $accountNumber)
    {
        $bankAccount = [
            'merchant_id'    => $merchantId,
            'id'             => $id,
            'account_number' => $accountNumber
        ];

        $this->fixtures->create('bank_account', $bankAccount);
    }

    public function createBalance($id, $merchantId)
    {
        $balance = [
            'id'           => $id,
            'merchant_id'  => $merchantId,
            'type'         => 'banking',
            'account_type' => 'shared'
        ];

        $this->fixtures->create('balance', $balance);
    }

    public function getDefaultSmsParams()
    {
        return [
            'source'            => 'fund_loading_downtime',
            'language'          => 'english',
            'sender'            => 'RZPAYX',
            'contentParams'     => [],
            'templateNamespace' => 'razorpayx_payouts_core',
        ];
    }
}
