<?php

namespace Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Balance\Type as Type;
use RZP\Models\Counter\Entity as CounterEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Balance\Entity as Balance;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Balance\AccountType as AccountType;

class BalanceTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BalanceTestData.php';

        parent::setUp();

    }

    public function testGetBalance()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest($this->testData['testCreateCapitalBalance']);

        $this->testData[__FUNCTION__]['request']['url'] .= $response['id'];

        $this->startTest();
    }

    public function testGetBalanceForGraphQL()
    {
        $balance = $this->fixtures->create('balance', [
            Balance::TYPE         => Type::PRIMARY,
            Balance::BALANCE      => 900,
        ]);

        $config = \Config::get('applications.frontend_graphql');

        $pwd = $config['secret'];

        $this->ba->appAuth('rzp_test_'. $balance['merchant_id'] , $pwd);

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $balance['id'],]);

        $this->startTest();
    }

    public function testGetBalanceMultiple()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $principal = $this->fixtures->create('balance', [
            Balance::MERCHANT_ID => '10000000000000',
            Balance::TYPE        => Type::PRINCIPAL,
            Balance::BALANCE     => 100000,
        ]);

        $interest = $this->fixtures->create('balance', [
            Balance::MERCHANT_ID => '10000000000000',
            Balance::TYPE        => Type::INTEREST,
            Balance::BALANCE     => 900,
        ]);

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->testData[__FUNCTION__]['request']['content']['ids'] = [$principal['id'], $interest['id']];

        $response = $this->startTest();

        $balances = $response['items'];

        $firstBalance = $balances[0];

        if ($firstBalance[Balance::TYPE] === Type::INTEREST)
        {
            $this->assertEquals(900, $firstBalance[Balance::BALANCE]);
            $this->assertEquals(100000, $balances[1][Balance::BALANCE]);
            $this->assertEquals(Type::PRINCIPAL, $balances[1][Balance::TYPE]);
        }
        else
        {
            $this->assertEquals(100000, $firstBalance[Balance::BALANCE]);
            $this->assertEquals(900, $balances[1][Balance::BALANCE]);
            $this->assertEquals(Type::INTEREST, $balances[1][Balance::TYPE]);
        }
    }

    public function testCreateCapitalBalance()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();
        $this->assertNotNull($response['id']);
    }

    public function testCreateCapitalBalanceWithDefaultBalance()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->startTest();
    }

    public function testCreateCapitalBalanceWithNegativeBalance()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->startTest();
    }

    public function testUpdateFreePayoutsCount()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $counter = $this->getDbEntity('counter', [
            CounterEntity::BALANCE_ID   => $balance[Balance::ID],
            CounterEntity::ACCOUNT_TYPE => AccountType::SHARED
        ])->toArray();

        $this->assertNotNull($counter);
    }

    public function testUpdateFreePayoutsCountAndMode()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $counter = $this->getDbEntity('counter', [
            CounterEntity::BALANCE_ID   => $balance[Balance::ID],
            CounterEntity::ACCOUNT_TYPE => AccountType::SHARED
        ])->toArray();

        $this->assertNotNull($counter);
    }

    public function testUpdateFreePayoutsMode()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithoutModeAndCount()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithDuplicateModeInArray()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithInvalidModeInArray()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testUpdateFreePayoutsWithModeArrayEmpty()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testUpdateFreePayoutsWithModeArrayNull()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithInvalidCount()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance[Balance::ID] . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithInvalidBalanceId()
    {
        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . 'gsdglddggjlgldjdlg' . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithInvalidBalanceType()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::PRIMARY,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance->getId() . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithNegativeCount()
    {
        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/' . $balance->getId() . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFailUpdateFreePayoutsWithBalanceIdNotPresentInDb()
    {
        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/balance/FIF0eRkA4FVj8H/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }
}
