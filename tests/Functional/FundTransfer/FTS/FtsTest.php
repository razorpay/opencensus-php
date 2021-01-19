<?php

namespace RZP\Tests\Functional\FundTransfer\FTS;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Services\FTS\Transfer\Client;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class FtsTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
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
}
