<?php

namespace RZP\Tests\Functional\Counter;

use RZP\Models\Counter;
use RZP\Models\Merchant\Balance;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class CounterTest extends TestCase
{
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CounterTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateCountersFunctionWithBalanceId()
    {
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $merchant = $this->getDbEntityById('merchant', '100ghi000ghi00');

        $balance = $this->fixtures->create('balance',
            [
                Balance\Entity::ID                 => '100xy000xy00xy',
                Balance\Entity::MERCHANT_ID        => '100ghi000ghi00',
                Balance\Entity::ACCOUNT_TYPE       => Balance\AccountType::SHARED
            ]
        );

        $data = [
            Balance\FreePayout::FREE_PAYOUTS_COUNT  => 1,
        ];

        (new Balance\Service)->createCounterForBalance(
            $balance,
            $data
        );

        $counters = $this->getDbEntities('counter');
        $this->assertEquals(1, count($counters));

        $counterWithBalanceId = $this->getDbEntity('counter',
            [
                Counter\Entity::BALANCE_ID => $balance[Balance\Entity::ID]
            ]
        );

        $this->assertEquals($balance[Balance\Entity::ID], $counterWithBalanceId->getBalanceId());
        $this->assertEquals(Balance\AccountType::SHARED, $counterWithBalanceId->getAccountType());
    }
}
