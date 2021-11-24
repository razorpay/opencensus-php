<?php

namespace RZP\Tests\Functional\FundTransfer\FTS;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Services\FTS\Transfer\Client;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Gateway\File\Processor\Emi\Rbl;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields as RblGatewayFields;

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

    public function testGracefulUpdateOfExistingSourceAccount()
    {
        $this->setUpForRblUpiCredsUpdateTest();

        $this->ba->adminAuth();

        $request = $this->generateMockRequestForGracefulSourceAccountUpdate();

        $this->makeRequestAndGetContent($request);

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

        $this->assertEquals('testusername', $vpa[0]['username']);
        $this->assertEquals('rzp', $vpa[0]['handle']);
        $this->assertEquals('banking_account', $vpa[0]['entity_type']);
        $this->assertEquals('1000000lcustba', $vpa[0]['entity_id']);
        $this->assertEquals('testusername@rzp', $vpa[0]['address']);
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
                        RblGatewayFields::PAYER_VPA        => 'testUsername@rzp',
                        RblGatewayFields::MRCH_ORG_ID      => 'TheMrchOrgId',
                        RblGatewayFields::AGGR_ORG_ID      => 'TheAggrOrgId',
                    ],
                    'graceful_update'    => true,
                ],
            ],
        ];
    }
}
