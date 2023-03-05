<?php

namespace RZP\Tests\Functional\FundTransfer\FTS;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Services\FTS\CreateAccount;
use RZP\Services\FTS\Transfer\Client;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\PartnerBankHealth\Events;
use RZP\Models\PartnerBankHealth\Notifier;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Gateway\File\Processor\Emi\Rbl;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\PartnerBankHealth\PartnerBankHealthMail;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields as RblGatewayFields;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType as Type;

class FtsTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FtsTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->setPartnerBankHealthNotificationConfigFetchLimitInRedis(2);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function setUpForRblUpiCredsUpdateTest()
    {
        // Creates banking balance
        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            1000000, '10000000000000',AccountType::DIRECT, Channel::RBL);

        $bankingAccount    = $this->fixtures->create(
            'banking_account',
            [
                'id'             => '1000000lcustba',
                'account_type'   => AccountType::DIRECT,
                'merchant_id'    => '10000000000000',
                'account_number' => '2224440041626906',
                'account_ifsc'   => 'RAZRB000000',
                'status'         => 'activated'
            ]);

        $bankingAccount->balance()->associate($bankingBalance);
        $bankingAccount->save();
    }
    public function setPartnerBankHealthNotificationConfigFetchLimitInRedis(int $limit)
    {
        $merchantNotificationConfigFetchLimit = (new Admin\Service)->getConfigKey(
            ['key' => Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT]
        );

        $merchantNotificationConfigFetchLimit[Type::PARTNER_BANK_HEALTH] = $limit;

        (new Admin\Service)->setConfigKeys(
            [Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT => $merchantNotificationConfigFetchLimit]
        );
    }
    public function testFtsTransferGet()
    {
        $this->markTestSkipped('Fts mock changes pending');

        $this->ba->adminAuth();

        $request = [
          'url'     => '/fts/dashboard/fund_transfer?count=20',
          'method'  => 'get',
          'content' => [],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testDoTransfer()
    {
        $this->fixtures->create(
          'fund_account',
          [
            'id'           => '100000000000fa',
            'source_id'    => '1000001contact',
            'source_type'  => 'contact',
            'account_type' => 'bank_account',
            'account_id'   => 'rzpBankAccount',
          ]);

        $this->createPayout([
          'mode' => 'NEFT',
          'narration' => 'Test Fund Transfer',
          'type' => 'default']);

        $request               = [
          'transfer'     => [
            'type'                        => $this->payout->getPayoutType(),
            'source_id'                   => $this->payout->getId(),
            'source_type'                 => 'payout',
            'preferred_mode'              => $this->payout->getMode(),
            'amount'                      => $this->payout->getAmount(),
            'narration'                   => $this->payout->getNarration(),
            'preferred_channel'           => $this->payout->getChannel(),
            'transfer_account_type'       => 'bank_account',
            'purpose'                     => $this->payout->getPurpose(),
            'preferred_source_account_id' => 21,
            'merchant_id'                 => $this->payout->getMerchantId(),
          ],
          'bank_account' => [
            'id'                  => 'rzpBankAccount',
            'ifsc_code'           => 'KKBK0001754',
            'account_type'        => 'saving',
            'account_number'      => '991121053806',
            'beneficiary_name'    => 'AMKONOSH HARAO NARHARE',
            'beneficiary_city'    => null,
            'beneficiary_email'   => null,
            'beneficiary_state'   => null,
            'beneficiary_mobile'  => null,
            'is_virtual_account'  => false,
            'beneficiary_address' => 'Bangalore',
            'beneficiary_country' => 'IN',
          ]
        ];

        $this->app['rzp.mode'] = Mode::LIVE;

        $client                = new Client($this->app);

        $client->setRequest($request);

        $response = $client->doTransfer();

        $this->assertArrayKeysExist($response, ['body', 'code']);

        $this->assertArrayKeysExist($response['body'], ['fund_account_id', 'fund_transfer_id', 'status']);

        $this->assertEquals('CREATED', $response['body']['status']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Status::INITIATED, $attempt['status']);

        $this->assertNotEquals(0, $attempt['fts_transfer_id']);

    }

    public function testChannelNoftify()
    {
        $this->markTestSkipped('Old payload no longer in use');

        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        $request = [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'contains'=> ['bene_health'],
                'entity'  =>'event',
                'event'   =>'bene_health.started',
                'payload'=> [
                    'bene_health' => [
                        'entity'=> [
                            'begin' => 1610430729,
                            'created_at'=>1610430729,
                            'end'=>0,
                            'entity'=>'bene_health',
                            'id'=>'GOHp6DSA5odXTu',
                            'instrument'=> [
                                'bank'=>'UTIB'
                            ],
                            'method' => ['IMPS'],
                            'scheduled'=>false,
                            'source'=>'BENEFICIARY',
                            'status'=>'started',
                            'updated_at'=>1610430729
                        ]
                    ]
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testChannelNotifyWithNewPayload()
    {
        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        $request = [
            'url' => '/fts/channel/notify',
            'method' => 'post',
            'content' => [
                'type' => 'bene_health',
                'payload' => [
                    'begin' => 1610430729,
                    'created_at' => 1610430729,
                    'end' => 0,
                    'entity' => 'bene_health',
                    'id' => 'GOHp6DSA5odXTu',
                    'instrument' => [
                        'bank' => 'UTIB'
                    ],
                    'method' => ['IMPS'],
                    'scheduled' => false,
                    'source' => 'BENEFICIARY',
                    'status' => 'started',
                    'updated_at' => 1610430729
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testBulkStatus()
    {
        $this->ba->adminAuth(Mode::LIVE);

        $this->startTest();
    }

    public function mockBankingAccountService()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testGracefulUpdateOfExistingSourceAccount()
    {
        $this->setUpForRblUpiCredsUpdateTest();

        $this->mockBankingAccountService();

        $this->ba->adminAuth();

        $request = $this->generateMockRequestForGracefulSourceAccountUpdate();

        $response = $this->makeRequestAndGetContent($request);

        $credentials = $request['content']['source_account']['credentials'];

        $bankingAccountDetails = $this->getDbEntities('banking_account_detail',
                                                      ['banking_account_id' => '1000000lcustba'])->toArray();

        $vpa = $this->getDbEntities('vpa',
                                    ['entity_id' => '1000000lcustba', 'entity_type' => 'banking_account'])->toArray();

        $this->assertCount(count($credentials), $bankingAccountDetails);

        foreach($bankingAccountDetails as $bankingAccountDetail)
        {
            $this->assertEquals('1000000lcustba', $bankingAccountDetail['banking_account_id']);
            if(($bankingAccountDetail['gateway_key'] === RblGatewayFields::BCAGENT_PASSWORD) or
               ($bankingAccountDetail['gateway_key'] === RblGatewayFields::HMAC_KEY))
            {
                // This is because these credentials will be tokenised.
                $this->assertNotEquals($credentials[$bankingAccountDetail['gateway_key']],
                                    $bankingAccountDetail['gateway_value']);
            }
            else
            {
                $this->assertEquals($credentials[$bankingAccountDetail['gateway_key']],
                                    $bankingAccountDetail['gateway_value']);
            }
        }

        $this->assertEquals('payouts.puv27-2', $vpa[0]['username']);
        $this->assertEquals('rbl', $vpa[0]['handle']);
        $this->assertEquals('banking_account', $vpa[0]['entity_type']);
        $this->assertEquals('1000000lcustba', $vpa[0]['entity_id']);
        $this->assertEquals('payouts.puv27-2@rbl', $vpa[0]['address']);
    }

    /**
     * To test the case where the VPA doesn't match
     */
    public function testGracefulUpdateOfExistingSourceAccountNegativeCase()
    {
        $this->setUpForRblUpiCredsUpdateTest();

        $this->mockBankingAccountService();

        $this->ba->adminAuth();

        $request = $this->generateMockRequestForGracefulSourceAccountUpdate();

        $request['content']['source_account']['credentials'][RblGatewayFields::PAYER_VPA] = 'wrong_vpa@rbl';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['exception'], CreateAccount::VPAS_DO_NOT_MATCH_ERROR);
    }

    /**
     * To test the case where the VPA doesn't start with Capital P
     */
    public function testGracefulUpdateOfExistingSourceAccountNegativeCaseFirstLetter()
    {
        $this->setUpForRblUpiCredsUpdateTest();

        $this->mockBankingAccountService();

        $this->ba->adminAuth();

        $request = $this->generateMockRequestForGracefulSourceAccountUpdate();

        $request['content']['source_account']['credentials'][RblGatewayFields::PAYER_VPA] = 'payouts.puv27-2@rbl';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['exception'], CreateAccount::VPAS_FIRST_LETTER_ERROR);
    }

    protected function generateMockRequestForGracefulSourceAccountUpdate()
    {
        return [
            'url'     => '/fts/dashboard/source_account/graceful_update',
            'method'  => 'PATCH',
            'content' => [
                'source_account' => [
                    'banking_account_id' => '1000000lcustba',
                    'credentials'        => [
                        RblGatewayFields::BCAGENT          => 'RandomBcagent',
                        RblGatewayFields::BCAGENT_USERNAME => 'username123',
                        RblGatewayFields::BCAGENT_PASSWORD => 'passwordIsRedacted',
                        RblGatewayFields::HMAC_KEY         => 'hMacKeyIsTested',
                        RblGatewayFields::PAYER_VPA        => 'Payouts.puv27-2@rbl',
                        RblGatewayFields::MRCH_ORG_ID      => 'TheMrchOrgId',
                        RblGatewayFields::AGGR_ORG_ID      => 'TheAggrOrgId',
                    ],
                    'graceful_update'    => true,
                ],
            ],
        ];
    }

    public function testPartnerBankHealthDowntimeNotificationForDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $this->createPartnerBankHealthNotificationConfigs();

        $this->startTest();

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', 'live')->toArrayPublic();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.direct.imps.ratn',
            'value'      => ['last_down_at' => 1640430729],
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "We are facing an issue with processing IMPS payouts through RBL Bank " . PartnerBankHealthMail::SMILING_FACE_WITH_TEAR;

            $this->assertSame($subject, $mail->subject);

            $from = [
                0 => [
                    'name'    => 'Team Razorpay',
                    'address' => 'no-reply@razorpay.com',
                ]
            ];

            $to = [
                0 => [
                    'address' => '200@gmail.com',
                    'name'    => 'Merchant 10000000000012',
                ],
                1 => [
                    'address' => '201@gmail.com',
                    'name'    => 'Merchant 10000000000012',
                ],
            ];

            $this->assertArraySelectiveEquals($from, $mail->from);
            $this->assertArraySelectiveEquals($to, $mail->to);
            $expectedEmailParams = [
                'source'     => 'RBL Bank',
                'mode'       => 'IMPS',
                'start_time' => '25 Dec 4:42 pm',
                'status'     => 'down'
            ];

            $this->assertArraySelectiveEquals($expectedEmailParams, $mail->params);

            $this->assertSame('emails.partner_bank_health.down', $mail->view);

            return true;
        });
    }

    public function testPartnerBankHealthUptimeNotificationForDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $this->createPartnerBankHealthNotificationConfigs();

        $this->fixtures->on('live')->create('partner_bank_health',
                                            [
                                                'event_type' => 'fail_fast_health.direct.imps.ratn',
                                                'value'      => json_encode([
                                                                                'last_down_at'       => 1640430729,
                                                                                'affected_merchants' => [
                                                                                    "ALL"
                                                                                ],
                                                                            ])
                                            ]);

        $this->startTest();

        // no merchants are now affected as we received an uptime webhook and hence affected_merchants array
        // should be empty
        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.direct.imps.ratn',
            'value'      => [
                'last_down_at'       => 1640430729,
                'last_up_at'         => 1640431729,
                'affected_merchants' => []
            ]
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', 'live')->toArrayPublic();

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "IMPS Payouts through RBL Bank is up and running!";

            $this->assertSame($subject, $mail->subject);

            $from = [
                0 => [
                    'name'    => 'Team Razorpay',
                    'address' => 'no-reply@razorpay.com',
                ]
            ];

            $to = [
                0 => [
                    'address' => '200@gmail.com',
                    'name'    => 'Merchant 10000000000012',
                ],
                1 => [
                    'address' => '201@gmail.com',
                    'name'    => 'Merchant 10000000000012',
                ],
            ];

            $this->assertArraySelectiveEquals($from, $mail->from);
            $this->assertArraySelectiveEquals($to, $mail->to);

            $expectedEmailParams = [
                'source'     => 'RBL Bank',
                'mode'       => 'IMPS',
                'end_time'   => '25 Dec 4:58 pm',
                'status'     => 'up'
            ];

            $this->assertArraySelectiveEquals($expectedEmailParams, $mail->params);

            $this->assertSame('emails.partner_bank_health.up', $mail->view);

            return true;
        });
    }

    public function testPartnerBankHealthUptimeNotificationWithoutCorrespondingDowntimeWebhook()
    {
        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        $this->startTest();

        $this->fixtures->on('live')->create('partner_bank_health',
                                            [
                                                'event_type' => 'fail_fast_health.direct.upi.icic',
                                                'value'      => json_encode([
                                                                                'last_down_at'       => 1640430729,
                                                                                'affected_merchants' => [
                                                                                    "ALL"
                                                                                ],
                                                                            ])
                                            ]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['payload']['instrument']['bank'] = 'RBL';
        $testData['request']['content']['payload']['mode'] = 'UPI';
        $this->startTest($testData);
    }

    public function testDuplicatePartnerBankHealthNotificationsForDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $this->createPartnerBankHealthNotificationConfigs();

        // first test duplicate downtime notification was not sent
        $this->fixtures->on('live')->create('partner_bank_health',
                                            [
                                                'event_type' => 'fail_fast_health.direct.imps.ratn',
                                                'value'      => json_encode([
                                                                                'last_down_at'       => 1640430729,
                                                                                'affected_merchants' => [
                                                                                    "ALL"
                                                                                ],
                                                                            ])
                                            ]);

        $this->startTest();

        Mail::assertNotSent(PartnerBankHealthMail::class);

        $entity = $this->getDbEntity('partner_bank_health', ['event_type' => 'fail_fast_health.direct.imps.ratn'], 'live');

        $this->fixtures->on('live')->edit('partner_bank_health', $entity->getId(),
                                          [
                                              'value' => json_encode(
                                                  [
                                                      'last_down_at'       => 1640430729,
                                                      'last_up_at'         => 1640431729,
                                                      'affected_merchants' => [],
                                                  ])
                                          ]);

        // now check that duplicate uptime notification was not sent
        $this->testData[__FUNCTION__]['request'] = $this->testData['testPartnerBankHealthUptimeNotificationForDirectIntegration']['request'];

        $this->startTest();

        Mail::assertNotSent(PartnerBankHealthMail::class);
    }

    public function testPartnerBankHealthDowntimeNotificationForSharedIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        $this->createPartnerBankHealthNotificationConfigs();

        $this->startTest();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value'      => [
                'YESB'               => [
                    'last_down_at' => 1640430729,
                ],
                'affected_merchants' => ["10000000000015"]
            ]
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 1);

        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "We are facing an issue with processing UPI payouts through RazorpayX ". PartnerBankHealthMail::SMILING_FACE_WITH_TEAR;

            $this->assertSame($subject, $mail->subject);

            $from = [['name' => 'Team Razorpay', 'address' => 'no-reply@razorpay.com']];
            $to   = [['address' => '500@gmail.com', 'name' => 'Merchant 10000000000015',], ['address' => '501@gmail.com', 'name' => 'Merchant 10000000000015',]];

            $this->assertArraySelectiveEquals($from, $mail->from);
            $this->assertArraySelectiveEquals($to, $mail->to);
            $expectedEmailParams = [
                'source'     => 'RazorpayX',
                'mode'       => 'UPI',
                'start_time' => '25 Dec 4:42 pm',
                'status'     => 'down'
            ];

            $this->assertArraySelectiveEquals($expectedEmailParams, $mail->params);
            $this->assertSame('emails.partner_bank_health.down', $mail->view);

            return true;
        });

        $testPayload = &$this->testData[__FUNCTION__]['request']['content']['payload'];

        $testPayload['instrument']['bank'] = 'ICICI';
        $testPayload['include_merchants'] = ["10000000000013"];
        $testPayload['begin'] = 1640431729;
        $testPayload['exclude_merchants'] = [];

        $this->startTest();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value'      => [
                'YESB'               => [
                    'last_down_at' => 1640430729,
                ],
                'ICIC'               => [
                    'last_down_at' => 1640431729
                ],
                'affected_merchants' => ["10000000000015", "10000000000013"]
            ],
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();
        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 2);
        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        // Since include_list was 10000000000013, email should be sent to 10000000000013 only
        $this->assertSame($sentMails[1]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'     => 'RazorpayX',
            'mode'       => 'UPI',
            'start_time' => '25 Dec 4:58 pm',
            'status'     => 'down'
        ];
        //all the three mails sent in must have the same params as that of expectedEmailParams
        $mailParams = array_intersect($expectedEmailParams, $sentMails[1]->params);

        $this->assertArraySelectiveEquals($mailParams, $expectedEmailParams);

        $testPayload['instrument']['bank'] = 'AXIS';
        $testPayload['include_merchants'] = ["ALL"];
        $testPayload['begin'] = 1640432729;
        $testPayload['exclude_merchants'] = ["10000000000015", "10000000000013"];

        $this->startTest();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value'      => [
                'YESB'               => [
                    'last_down_at' => 1640430729,
                ],
                'ICIC'               => [
                    'last_down_at' => 1640431729
                ],
                'UTIB'               => [
                    'last_down_at' => 1640432729
                ],
                'affected_merchants' => ["ALL"],
            ],
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();
        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 4);
        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        // Since include_list was all and exclude_list was 10000000000015, email should be sent to 10000000000013,
        // 10000000000014 and 10000000000016
        $this->assertSame($sentMails[2]->to, [['address' => '600@gmail.com', 'name' => 'Merchant 10000000000016'],
                                              ['address' => '601@gmail.com', 'name' => 'Merchant 10000000000016']]);

        $this->assertSame($sentMails[3]->to, [['address' => '400@gmail.com', 'name' => 'Merchant 10000000000014'],
                                              ['address' => '401@gmail.com', 'name' => 'Merchant 10000000000014']]);

        $expectedEmailParams = [
            'source'     => 'RazorpayX',
            'mode'       => 'UPI',
            'start_time' => '25 Dec 5:14 pm',
            'status'     => 'down'
        ];
        //all the three mails sent in must have the same params as that of expectedEmailParams
        $mailParams = array_intersect($expectedEmailParams, $sentMails[2]->params, $sentMails[3]->params);

        $this->assertArraySelectiveEquals($mailParams, $expectedEmailParams);
    }

    public function testPartnerBankUptimeNotificationForSharedIntegration()
    {
        //first assume all channels are down
        $this->fixtures->on(Mode::LIVE)->create('partner_bank_health',
                                                [
                                                    'event_type' => 'fail_fast_health.shared.upi',
                                                    'value'      => json_encode(
                                                        [
                                                            'YESB'               => [
                                                                'last_down_at' => 1640430729,
                                                            ],
                                                            'ICIC'               => [
                                                                'last_down_at' => 1640431729,
                                                            ],
                                                            'affected_merchants' => [
                                                                "ALL"
                                                            ],
                                                        ])
                                                ]);

        $this->createPartnerBankHealthNotificationConfigs();

        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        $this->startTest();

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value'      => [
                'YESB'               => [
                    'last_down_at' => 1640430729,
                    'last_up_at'   => 1640432729,
                ],
                'ICIC'               => [
                    'last_down_at' => 1640431729,
                ],
                'affected_merchants' => [
                    "10000000000013"
                ],
            ]
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 3);

        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        // Both yesbank merchants and icici routing enabled merchant should receive a notification
        // thus 10000000000013, 10000000000014 and 10000000000016 should receive a notification.
        $this->assertSame($sentMails[0]->to, [['address' => '500@gmail.com', 'name' => 'Merchant 10000000000015'],
                                              ['address' => '501@gmail.com', 'name' => 'Merchant 10000000000015']]);

        $this->assertSame($sentMails[1]->to, [['address' => '600@gmail.com', 'name' => 'Merchant 10000000000016'],
                                              ['address' => '601@gmail.com', 'name' => 'Merchant 10000000000016']]);

        $this->assertSame($sentMails[2]->to, [['address' => '400@gmail.com', 'name' => 'Merchant 10000000000014'],
                                              ['address' => '401@gmail.com', 'name' => 'Merchant 10000000000014']]);

        $expectedEmailParams = [
            'source'   => 'RazorpayX',
            'mode'     => 'UPI',
            'status'   => 'up',
            'end_time' => '25 Dec 5:15 pm'
        ];
        //all the three mails sent in must have the same params as that of expectedEmailParams
        $mailParams = array_intersect($expectedEmailParams, $sentMails[0]->params, $sentMails[1]->params, $sentMails[2]->params);

        $this->assertArraySelectiveEquals($expectedEmailParams, $mailParams);

        // now we mark ICICI as up. As YESBANK is already up, only merchant 10000000000015 should receive a notification
        // since the merchant has routing disabled on ICICI.
        $payload = &$this->testData[__FUNCTION__]['request']['content']['payload'];
        $payload['instrument']['bank'] = 'ICICI';
        $payload['begin'] = 1640433729;
        $payload['include_merchants'] = ['10000000000013'];
        $payload['exclude_merchants'] = [];

        $this->startTest();

        // as all channels are up now, no merchant is currently affected

        $expectedPartnerBankHealthStatus = [
            'event_type'   => 'fail_fast_health.shared.upi',
            'value' => [
                'YESB'               => [
                    'last_down_at' => 1640430729,
                    'last_up_at'   => 1640432729,
                ],
                'ICIC'               => [
                    'last_down_at' => 1640431729,
                    'last_up_at' => 1640433729
                ],
                'affected_merchants' => [],
            ]
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        //Only Merchant 10000000000015 should receive a mail in this scenario. As 3 mails were already sent, the total
        // count should be 4 now.
        Mail::assertSent(PartnerBankHealthMail::class, 4);
        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[3]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'   => 'RazorpayX',
            'mode'     => 'UPI',
            'status'   => 'up',
            'end_time' => '25 Dec 5:32 pm'
        ];

        $this->assertArraySelectiveEquals($expectedEmailParams, $sentMails[3]->params);
    }

    public function testPartnerBankHealthDowntimeUptimeNotificationForSharedIntegration()
    {
        $this->createPartnerBankHealthNotificationConfigs();

        Mail::fake();

        $this->ba->ftsAuth(Mode::LIVE);

        //In this test, first we mark ICICI as down
        $this->startTest();

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();
        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value'      => [
                'ICIC'               => [
                    'last_down_at' => 1640434729,
                ],
                'affected_merchants' => ['10000000000013']
            ]
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        //Mail related assertions
        Mail::assertSent(PartnerBankHealthMail::class, 1);
        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[0]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'     => 'RazorpayX',
            'mode'       => 'UPI',
            'status'     => 'down',
            'start_time' => '25 Dec 5:48 pm'
        ];

        $this->assertArraySelectiveEquals($expectedEmailParams, $sentMails[0]->params);

        // now we will send a request to mark ICICI up. In this scenario, only routing disabled merchant on ICICI
        // should receive a notification as YESBANK is up and routing enabled merchants on ICICI are not affected.
        $payload = &$this->testData[__FUNCTION__]['request']['content']['payload'];
        $payload['status'] = 'up';
        $payload['begin'] = 1640435729;
        $payload['include_merchants'] = ['10000000000013'];
        $payload['exclude_merchants'] = [];

        $this->startTest();
        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', Mode::LIVE)->toArrayPublic();
        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.shared.upi',
            'value' => [
                'ICIC' => [
                    'last_down_at' => 1640434729,
                    'last_up_at' => 1640435729
                ],
                'affected_merchants' => [],
            ]
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 2);
        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[1]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'   => 'RazorpayX',
            'mode'     => 'UPI',
            'status'   => 'up',
            'end_time' => '25 Dec 6:05 pm'
        ];

        $this->assertArraySelectiveEquals($expectedEmailParams, $sentMails[1]->params);
    }

    public function testFetchPartnerBankHealthEntities()
    {
        Carbon::setTestNow();
        $this->fixtures->on(Mode::LIVE)->create('partner_bank_health',
                                                [
                                                    'id'    => 'downtime100000',
                                                    'event_type'   => 'fail_fast_health.direct.imps',
                                                    'value' => json_encode(
                                                        [
                                                            'YESB'               => [
                                                                'last_down_at' => 1640430729,
                                                            ],
                                                            'ICIC'               => [
                                                                'last_down_at' => 1640431729,
                                                            ],
                                                            'affected_merchants' => [
                                                                "ALL"
                                                            ],
                                                        ]),
                                                    'created_at' => Carbon::now()->getTimestamp(),
                                                ]);

        $this->fixtures->on(Mode::LIVE)->create('partner_bank_health',
                                                [
                                                    'id'    => 'downtime200000',
                                                    'event_type'   => 'fail_fast_health.direct.upi',
                                                    'value' => json_encode(
                                                        [
                                                            'YESB'               => [
                                                                'last_down_at' => 1640430729,
                                                            ],
                                                            'ICIC'               => [
                                                                'last_down_at' => 1640431729,
                                                            ],
                                                            'affected_merchants' => [
                                                                "ALL"
                                                            ],
                                                        ]),
                                                    'created_at' => Carbon::now()->addSeconds(100)->getTimestamp(),
                                                ]);

        $this->fixtures->on(Mode::LIVE)->create('partner_bank_health',
                                                [
                                                    'id'    => 'downtime300000',
                                                    'event_type'   => 'fail_fast_health.shared.upi',
                                                    'value' => json_encode(
                                                        [
                                                            'YESB'               => [
                                                                'last_down_at' => 1640430729,
                                                            ],
                                                            'ICIC'               => [
                                                                'last_down_at' => 1640431729,
                                                            ],
                                                            'affected_merchants' => [
                                                                "ALL"
                                                            ],
                                                        ]),
                                                    'created_at' => Carbon::now()->addSeconds(200)->getTimestamp(),

                                                ]);

        $this->fixtures->on(Mode::LIVE)->create('partner_bank_health',
                                                [
                                                    'id'    => 'downtime400000',
                                                    'event_type'   => 'fail_fast_health.shared.imps',
                                                    'value' => json_encode(
                                                        [
                                                            'YESB'               => [
                                                                'last_down_at' => 1640430729,
                                                            ],
                                                            'ICIC'               => [
                                                                'last_down_at' => 1640431729,
                                                            ],
                                                            'affected_merchants' => [
                                                                "ALL"
                                                            ],
                                                        ]),
                                                    'created_at' => Carbon::now()->addSeconds(300)->getTimestamp(),
                                                ]);

        $this->ba->adminAuth(Mode::LIVE);

        $this->startTest();
    }

    public function testPartnerBankHealthDowntimeNotificationForAxisDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $testData = &$this->testData['testPartnerBankHealthDowntimeNotificationForDirectIntegration'];
        $testData['request']['content']['payload']['instrument']['bank'] = 'AXIS';

        $this->createPartnerBankHealthNotificationConfigsForChannelAndAccountType(Channel::AXIS, AccountType::DIRECT);

        $this->startTest($testData);

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', 'live')->toArrayPublic();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.direct.imps.utib',
            'value'      => [
                'last_down_at'       => 1640430729,
                'affected_merchants' => ['ALL'],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 2);

        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[0]->to, [['address' => '200@gmail.com', 'name' => 'Merchant 10000000000012'],
                                              ['address' => '201@gmail.com', 'name' => 'Merchant 10000000000012']]);

        $this->assertSame($sentMails[1]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'     => 'Axis Bank',
            'mode'       => 'IMPS',
            'status'     => 'down',
            'start_time' => '25 Dec 4:42 pm'
        ];
        //all the three mails sent in must have the same params as that of expectedEmailParams
        $actualEmailParams = array_intersect($expectedEmailParams, $sentMails[0]->params, $sentMails[1]->params);

        $this->assertArraySelectiveEquals($expectedEmailParams, $actualEmailParams);

        //assert other contents of the Mailable class like subject, sender and view
        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "We are facing an issue with processing IMPS payouts through Axis Bank " . PartnerBankHealthMail::SMILING_FACE_WITH_TEAR;

            $this->assertSame($subject, $mail->subject);

            $from = [
                0 => [
                    'name'    => 'Team Razorpay',
                    'address' => 'no-reply@razorpay.com',
                ]
            ];

            $this->assertArraySelectiveEquals($from, $mail->from);

            $this->assertSame('emails.partner_bank_health.down', $mail->view);

            return true;
        });
    }

    public function testPartnerBankHealthUptimeNotificationForAxisDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $testData = &$this->testData['testPartnerBankHealthUptimeNotificationForDirectIntegration'];
        $testData['request']['content']['payload']['instrument']['bank'] = 'AXIS';

        $this->createPartnerBankHealthNotificationConfigsForChannelAndAccountType(Channel::AXIS, AccountType::DIRECT);

        $this->fixtures->on('live')->create('partner_bank_health',
                                            [
                                                'event_type' => 'fail_fast_health.direct.imps.utib',
                                                'value'      => json_encode([
                                                                                'last_down_at'       => 1640430729,
                                                                                'affected_merchants' => [
                                                                                    "ALL"
                                                                                ],
                                                                            ])
                                            ]);

        $this->startTest($testData);

        // no merchants are now affected as we received an uptime webhook and hence affected_merchants array
        // should be empty
        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.direct.imps.utib',
            'value'      => [
                'last_down_at'       => 1640430729,
                'last_up_at'         => 1640431729,
                'affected_merchants' => []
            ]
        ];

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', 'live')->toArrayPublic();

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 2);

        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[0]->to, [['address' => '200@gmail.com', 'name' => 'Merchant 10000000000012'],
                                              ['address' => '201@gmail.com', 'name' => 'Merchant 10000000000012']]);

        $this->assertSame($sentMails[1]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);


        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "IMPS Payouts through Axis Bank is up and running!";

            $this->assertSame($subject, $mail->subject);

            $from = [
                0 => [
                    'name'    => 'Team Razorpay',
                    'address' => 'no-reply@razorpay.com',
                ]
            ];

            $this->assertArraySelectiveEquals($from, $mail->from);

            $expectedEmailParams = [
                'source'     => 'Axis Bank',
                'mode'       => 'IMPS',
                'end_time'   => '25 Dec 4:58 pm',
                'status'     => 'up'
            ];

            $this->assertArraySelectiveEquals($expectedEmailParams, $mail->params);

            $this->assertSame('emails.partner_bank_health.up', $mail->view);

            return true;
        });
    }

    public function testPartnerBankHealthDowntimeNotificationForYesbankDirectIntegration()
    {
        Mail::fake();

        $this->ba->ftsAuth('live');

        $testData = &$this->testData['testPartnerBankHealthDowntimeNotificationForDirectIntegration'];
        $testData['request']['content']['payload']['instrument']['bank'] = 'YESBANK';

        $this->createPartnerBankHealthNotificationConfigsForChannelAndAccountType(Channel::YESBANK, AccountType::DIRECT);

        $this->startTest($testData);

        $partnerBankHealthStatus = $this->getDbLastEntity('partner_bank_health', 'live')->toArrayPublic();

        $expectedPartnerBankHealthStatus = [
            'event_type' => 'fail_fast_health.direct.imps.yesb',
            'value'      => [
                'last_down_at'       => 1640430729,
                'affected_merchants' => ['ALL'],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedPartnerBankHealthStatus, $partnerBankHealthStatus);

        Mail::assertSent(PartnerBankHealthMail::class, 2);

        $sentMails = Mail::sent(PartnerBankHealthMail::class)->toArray();

        $this->assertSame($sentMails[0]->to, [['address' => '200@gmail.com', 'name' => 'Merchant 10000000000012'],
                                              ['address' => '201@gmail.com', 'name' => 'Merchant 10000000000012']]);

        $this->assertSame($sentMails[1]->to, [['address' => '300@gmail.com', 'name' => 'Merchant 10000000000013'],
                                              ['address' => '301@gmail.com', 'name' => 'Merchant 10000000000013']]);

        $expectedEmailParams = [
            'source'   => 'Yes Bank',
            'mode'     => 'IMPS',
            'status'   => 'down',
            'start_time' => '25 Dec 4:42 pm'
        ];
        //all the three mails sent in must have the same params as that of expectedEmailParams
        $actualEmailParams = array_intersect($expectedEmailParams, $sentMails[0]->params, $sentMails[1]->params);

        $this->assertArraySelectiveEquals($expectedEmailParams, $actualEmailParams);

        //assert other contents of the Mailable class like subject, sender and view
        Mail::assertSent(PartnerBankHealthMail::class, function($mail) {

            $subject = "We are facing an issue with processing IMPS payouts through Yes Bank " . PartnerBankHealthMail::SMILING_FACE_WITH_TEAR;

            $this->assertSame($subject, $mail->subject);

            $from = [
                0 => [
                    'name'    => 'Team Razorpay',
                    'address' => 'no-reply@razorpay.com',
                ]
            ];

            $this->assertArraySelectiveEquals($from, $mail->from);

            $this->assertSame('emails.partner_bank_health.down', $mail->view);

            return true;
        });
    }

    public function createPartnerBankHealthNotificationConfigs()
    {
        //rbl direct account merchant
        $this->createMerchantNotificationConfig('10000000000012', ['200@gmail.com', '201@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance200000', '10000000000012', 'direct', '00002000000000', 'rbl');

        //icici routing disabled merchant
        $this->createMerchantNotificationConfig('10000000000013', ['300@gmail.com', '301@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance300000', '10000000000013', 'shared', '34343000000000', 'icici');

        //icici routing enabled merchant
        $this->createMerchantNotificationConfig('10000000000014', ['400@gmail.com', '401@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance400000', '10000000000014', 'shared', '5656000000000', 'icici');

        //yesbank routing disabled merchant
        $this->createMerchantNotificationConfig('10000000000015', ['500@gmail.com', '501@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance500000', '10000000000015', 'shared', '456456000000000', 'yesbank');

        //yesbank routing enabled merchant
        $this->createMerchantNotificationConfig('10000000000016', ['600@gmail.com', '601@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance600000', '10000000000016', 'shared', '787878000000000', 'yesbank');
    }

    public function createMerchantNotificationConfig($merchantId, $emails, $mobileNumbers)
    {
        $merchantConfigs = $this->fixtures->on('live')->create('merchant_notification_config',
                                                               [
                                                                   'id'                          => substr('config' . strrev($merchantId), 0, 14),
                                                                   'merchant_id'                 => $merchantId,
                                                                   'notification_type'           => 'partner_bank_health',
                                                                   'notification_emails'         => implode(',', $emails),
                                                                   'notification_mobile_numbers' => implode(',', $mobileNumbers),
                                                               ]);

        $merchant = $this->getDbEntityById('merchant', $merchantId, 'live');
        $this->fixtures->on('live')->edit('merchant', $merchantId, ['display_name' => "", 'name' => "Merchant $merchantId"]);
        $merchantConfigs->merchant()->associate($merchant);
        $merchantConfigs->save();
    }

    public function createRelevantEntities($balanceId, $merchantId, $accountType, $accountNumber, $channel = 'rbl')
    {
        $basDetailEntity      = null;
        $bankingAccountEntity = null;
        $balanceEntity        = $this->createBalanceEntity($balanceId, $accountNumber, $merchantId, $accountType);

        if ($accountType === AccountType::SHARED)
        {
            $bankingAccountEntity = $this->createBankingAccountEntity($balanceEntity, $channel);
        }
        elseif ($accountType === AccountType::DIRECT)
        {
            $basDetailEntity = $this->createBankingAccountStatementDetailsEntity($balanceEntity, $channel);
        }

        return [$balanceEntity, $bankingAccountEntity, $basDetailEntity];
    }

    public function createBalanceEntity($id, $accountNumber, $merchantId, $accountType)
    {
        $balanceEntity = $this->fixtures->on('live')->create('balance',
                                                             [
                                                                 'id'             => $id,
                                                                 'account_number' => $accountNumber,
                                                                 'type'           => 'banking',
                                                                 'merchant_id'    => $merchantId,
                                                                 'account_type'   => $accountType,
                                                                 'balance'        => 2000000,
                                                             ]);

        $balanceEntity->save();

        return $balanceEntity;
    }

    public function createBankingAccountEntity(Entity $balanceEntity, $channel)
    {
        $bankingAccountEntity = $this->fixtures->on('live')->create('banking_account',
                                                                    [
                                                                        'id'             => preg_replace('/xbalance/', 'xbankacc', $balanceEntity['id']),
                                                                        'account_number' => $balanceEntity['account_number'],
                                                                        'balance_id'     => $balanceEntity['id'],
                                                                        'merchant_id'    => $balanceEntity->getMerchantId(),
                                                                        'account_type'   => 'current',
                                                                        'channel'        => $channel,
                                                                        'status'         => 'activated',
                                                                    ]);
        $bankingAccountEntity->balance()->associate($balanceEntity);
        $bankingAccountEntity->save();
    }

    public function createBankingAccountStatementDetailsEntity(Entity $balance, $channel)
    {
        $basDetails = $this->fixtures->on(Mode::LIVE)->create('banking_account_statement_details', [
            'id'             => random_alphanum_string(14),
            'channel'        => $channel,
            'account_number' => $balance->getAccountNumber(),
            'status'         => 'active',
            'account_type'   => 'direct',
            'merchant_id'    => $balance->getMerchantId(),
            'balance_id'     => $balance->getId(),
        ]);

        $basDetails->balance()->associate($balance);
        $basDetails->save();
    }

    public function createPartnerBankHealthNotificationConfigsForChannelAndAccountType($channel, $accountType)
    {
        $this->createMerchantNotificationConfig('10000000000012', ['200@gmail.com', '201@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance200000', '10000000000012', $accountType, '00002000000000', $channel);

        $this->createMerchantNotificationConfig('10000000000013', ['300@gmail.com', '301@gmail.com'], ['9898989898', '8989898989']);
        $this->createRelevantEntities('xbalance300000', '10000000000013', $accountType, '00003000000000', $channel);
    }

    public function testPartnerBankDowntime()
    {
        $this->ba->ftsAuth(Mode::LIVE);

        $testData = $this->testData[__FUNCTION__];
        $this->startTest();
        $redis = $this->app['redis'];
        $payload = $testData['request']['content']['payload'];
        $channel = $payload['channel'];
        $accountType = $payload['account_type'];
        $mode = $payload['mode'];
        $configKey = strtolower($accountType."_"."$channel"."_".$mode);
        $config = json_decode($redis->hget(Events::PARTNER_BANK_HEALTH, $configKey));
        $this->assertEquals("RBL", $config->channel);
        $this->assertEquals("IMPS", $config->mode);
        $this->assertEquals("downtime", $config->status);
        $this->assertEquals("ALL", $config->include_merchants[0]);
        // tear down
        $redis->del(Events::PARTNER_BANK_HEALTH);

    }

    public function testPartnerBankUptime()
    {
        $this->ba->ftsAuth(Mode::LIVE);
        $redis = $this->app['redis'];

        $testData = $this->testData[__FUNCTION__];
        //create key
        $payload = $testData['request']['content']['payload'];
        $channel = $payload['channel'];
        $accountType = $payload['account_type'];
        $mode = $payload['mode'];
        $configKey = strtolower($accountType."_"."$channel"."_".$mode);

        $this->startTest($testData);
        $config = json_decode($redis->hget(Events::PARTNER_BANK_HEALTH, $configKey));
        $this->assertEquals(Events::STATUS_UPTIME, $config->status);
        $redis->del(Events::PARTNER_BANK_HEALTH);

    }
}
