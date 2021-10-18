<?php

namespace Functional\Merchant;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Merchant\Balance\Type as Type;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Balance\SubBalanceMap\Entity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class SubBalanceTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SubBalanceTestData.php';

        parent::setUp();
    }

    public function testCreateSubBalance()
    {
        /** @var BalanceEntity $balance */
        $balance = $this->fixtures->create('balance',
                                           [
                                               BalanceEntity::ACCOUNT_TYPE => AccountType::SHARED,
                                               BalanceEntity::TYPE         => Type::BANKING,
                                           ]);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['parent_balance_id'] = $balance->getId();

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();
        $response = $this->startTest();
        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['sub_balance', 'sub_balance_map', 'config_key']);

        /** @var BalanceEntity $subBalance */
        $subBalance = $this->getDbLastEntity('balance');
        $this->assertEquals($subBalance->getId(), $response['sub_balance']['id']);
        $this->assertEquals($balance->getAccountType(), $subBalance->getAccountType());
        $this->assertNull($subBalance->getAccountNumber());
        $this->assertEquals($balance->getChannel(), $subBalance->getChannel());
        $this->assertEquals($balance->getMerchantId(), $subBalance->getMerchantId());

        /** @var Entity $subBalanceMap */
        $subBalanceMap = $this->getDbLastEntity('sub_balance_map');
        $this->assertEquals($balance->getMerchantId(), $subBalanceMap->getMerchantId());
        $this->assertEquals($balance->getId(), $subBalanceMap->getParentBalanceId());
        $this->assertEquals($subBalance->getId(), $subBalanceMap->getChildBalanceId());

        $subBalanceMapChildBalances = $this->getDbEntities('sub_balance_map');
        $this->assertCount(3, $subBalanceMapChildBalances->toArray());

        $childBalanceIds = [];

        /** @var Entity $subBalanceMapChildBalance */
        foreach ($subBalanceMapChildBalances as $subBalanceMapChildBalance)
        {
            array_push($childBalanceIds, $subBalanceMapChildBalance->getChildBalanceId());
        }

        $adminService = new AdminService;

        $subBalanceMapConfig = $adminService->getConfigKey(['key' => ConfigKey::SUB_BALANCES_MAP]);

        $this->assertArraySelectiveEquals([$balance->getId() => $childBalanceIds], $subBalanceMapConfig);
    }
}
