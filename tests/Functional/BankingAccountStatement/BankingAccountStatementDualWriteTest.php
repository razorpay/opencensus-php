<?php

namespace Functional\BankingAccountStatement;

use Mockery;
use Carbon\Carbon;

use RZP\Constants\Mode as EnvMode;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\TestCase;

class BankingAccountStatementDualWriteTest extends TestCase
{

    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $this->setUpMerchantForBusinessBanking(false, 0, 'direct', 'icici');
    }

    public function testBankingAccountStatementDetailsHandleDualWriteHappyFlow()
    {
        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID => 'xbas0000000002',
            BasDetails\Entity::MERCHANT_ID => '10000000000000',
            BasDetails\Entity::BALANCE_ID => '100DemoTstBlId',
            BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905',
            BasDetails\Entity::CHANNEL => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::yesterday()->timestamp,
            BasDetails\Entity::BALANCE_LAST_FETCHED_AT => Carbon::yesterday()->timestamp,
        ]);

        $expectedTimeNow = Carbon::now()->timestamp;
        $input = [
            [
                'merchant_id' => '10000000000000',
                'balance_id' => '100DemoTstBlId',
                'account_number' => '2224440041626905',
                'channel' => 'icici',
                'gateway_balance' => 200,
                'balance_last_fetched_at' => $expectedTimeNow,
                'gateway_balance_change_at' => $expectedTimeNow,
            ]
        ];

        $basDetailsCore = $this->app->make('RZP\Models\BankingAccountStatement\Details\Core');
        $result = $basDetailsCore->handleDualWrite($input);
        $this->assertEquals(['success' => 'true'], $result);

        /** @var BasDetails $bas */
        $basDetailsFromDb = $this->getDbLastEntity('banking_account_statement_details');

        $this->assertNotEmpty($basDetailsFromDb);
        $this->assertEquals(200, $basDetailsFromDb->getGatewayBalance());
        $this->assertEquals($expectedTimeNow, $basDetailsFromDb->getBalanceLastFetchedAt());
        $this->assertEquals($expectedTimeNow, $basDetailsFromDb->toArray()['gateway_balance_change_at']);
    }

    public function testBankingAccountStatementDetailsHandleDualWriteValidationFailed()
    {
        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID => 'xbas0000000002',
            BasDetails\Entity::MERCHANT_ID => '10000000000000',
            BasDetails\Entity::BALANCE_ID => '100DemoTstBlId',
            BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905',
            BasDetails\Entity::CHANNEL => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::yesterday()->timestamp,
            BasDetails\Entity::BALANCE_LAST_FETCHED_AT => Carbon::yesterday()->timestamp,
        ]);

        $expectedTimeNow = Carbon::now()->timestamp;
        $input = [
            [
                'merchant_id' => '10000000000000',
                'balance_id' => '100DemoTstBlId',
                'account_number' => '2224440041626905',
                'channel' => 'icici',
                'balance_last_fetched_at' => $expectedTimeNow,
                'gateway_balance_change_at' => $expectedTimeNow,
            ]
        ];

        $basDetailsCore = $this->app->make('RZP\Models\BankingAccountStatement\Details\Core');
        $result = $basDetailsCore->handleDualWrite($input);
        $this->assertEquals(['success' => 'true'], $result);

        /** @var BasDetails $bas */
        $basDetailsFromDb = $this->getDbLastEntity('banking_account_statement_details');

        $this->assertNotEmpty($basDetailsFromDb);
        $this->assertEquals(100, $basDetailsFromDb->getGatewayBalance());
    }

    public function testHandleDualWriteWithInvalidInput()
    {
        $input = [
            [
                'merchant_id' => '10000000000000',
                'balance_id' => '100DemoTstBlId',
                'account_number' => '2224440041626905',
                'channel' => 'icici',
                'gateway_balance' => 'invalid_balance',
                'balance_last_fetched_at' => 'invalid_timestamp',
                'gateway_balance_change_at' => 'invalid_timestamp',
            ]
        ];

        $basDetailsCore = $this->app->make('RZP\Models\BankingAccountStatement\Details\Core');
        $result = $basDetailsCore->handleDualWrite($input);
        $this->assertEquals(['success' => 'true'], $result);
    }

    public function testHandleDualWriteWithOlderTimestamp()
    {
        $existingTimestamp = Carbon::now()->timestamp;
        $inputTimestamp = Carbon::now()->subDay()->timestamp;

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID => 'xbas0000000002',
            BasDetails\Entity::MERCHANT_ID => '10000000000000',
            BasDetails\Entity::BALANCE_ID => '100DemoTstBlId',
            BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905',
            BasDetails\Entity::CHANNEL => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE => 200,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT => $existingTimestamp,
            BasDetails\Entity::BALANCE_LAST_FETCHED_AT => $existingTimestamp,
        ]);

        $input = [
            [
                'merchant_id' => '10000000000000',
                'balance_id' => '100DemoTstBlId',
                'account_number' => '2224440041626905',
                'channel' => 'icici',
                'gateway_balance' => 100,
                'balance_last_fetched_at' => $inputTimestamp,
                'gateway_balance_change_at' => $inputTimestamp,
            ]
        ];

        $basDetailsCore = $this->app->make('RZP\Models\BankingAccountStatement\Details\Core');
        $result = $basDetailsCore->handleDualWrite($input);
        $this->assertEquals(['success' => 'true'], $result);

        /** @var BasDetails $bas */
        $basDetailsFromDb = $this->getDbLastEntity('banking_account_statement_details');
        $this->assertNotEmpty($basDetailsFromDb);
        $this->assertEquals(200, $basDetailsFromDb->getGatewayBalance());
        $this->assertEquals($existingTimestamp, $basDetailsFromDb->getBalanceLastFetchedAt());
    }

    public function testHandleDualWriteRollbackOnSaveOrFailException()
    {
        $input = [
            [
                'merchant_id' => '10000000000000',
                'balance_id' => '100DemoTstBlId',
                'account_number' => '2224440041626905',
                'channel' => 'icici',
                'gateway_balance' => 200,
                'balance_last_fetched_at' => Carbon::now()->timestamp,
                'gateway_balance_change_at' => Carbon::now()->timestamp,
            ]
        ];

        $basDetailEntity = new BasDetails\Entity;
        $basDetailEntity->setGatewayBalance(100);
        $basDetailEntity->setBalanceLastFetchedAt(Carbon::yesterday()->timestamp);
        $basDetailEntity->setGatewayBalanceLastChangedAt(Carbon::yesterday()->timestamp);
        $basDetailEntity->setBalanceId('100DemoTstBlId');
        $basDetailEntity->setMerchantId('10000000000000');

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $basdMock = Mockery::mock('\RZP\Models\BankingAccountStatement\Details\Repository', [$this->app])->makePartial();
        $basdMock->shouldReceive('getAccountStatementDetailsByBalanceIds')->andReturn([$basDetailEntity]);

        $repoMock->shouldReceive('driver')->with('banking_account_statement_details')->andReturn($basdMock);
        $repoMock->shouldReceive('beginTransaction')->once();
        $repoMock->shouldReceive('saveOrFail')->andThrow(new \Exception('Simulated save failure'));
        $repoMock->shouldReceive('rollBack')->once();
        $repoMock->shouldNotHaveReceived('commit');

        $this->app->instance('repo', $repoMock);

        $basDetailsCore = new BasDetails\Core();
        $result = $basDetailsCore->handleDualWrite($input);
        $this->assertEquals(['success' => 'true'], $result);
    }
}
