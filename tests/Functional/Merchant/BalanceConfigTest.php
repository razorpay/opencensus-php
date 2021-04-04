<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BalanceConfigTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/BalanceConfigTestData.php';

        parent::setUp();
    }


    public function testCreateBalanceConfigInvalidAutoSmallerNegativeLimit()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreatePrimaryBalanceConfig()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreateBankingBalanceConfig()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreateBalanceConfigInvalidGreaterNegativeLimit()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreatePrimaryBalanceConfigInvalidNegativeTransactionFlow()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreatePrimaryBalanceConfigNoNegativeTransactionFlow()
    {
        $this->setUpCreateRequestFixtures();

        $this->startTest();
    }

    public function testCreateBalanceConfigAlreadyExists()
    {
        $this->setUpCreateRequestFixturesForExistingBalanceConfig();

        $this->startTest();
    }

    public function testCreateBalanceForNonExistingBalance()
    {
        $this->setUpCreateRequestFixturesForNonExistingBalance();

        $this->startTest();
    }

    public function testGetBalanceConfigsByMerchantId()
    {
        $this->setUpGetRequestFixtures();

        $this->startTest();
    }

    public function testGetBalanceConfigsByMerchantIdLiveMode()
    {
        $this->setUpGetRequestFixtures(Mode::LIVE);

        $this->startTest();
    }

    public function testGetBalanceConfigsByMerchantIdLiveModeActivatedMerchant()
    {
        $this->setUpGetRequestFixtures(Mode::LIVE);

        $this->fixtures->merchant->edit('100cq000cq00cq', ['activated' => true]);

        $this->startTest();
    }

    public function testGetBalanceConfigByConfigId()
    {
        $this->setUpGetRequestFixtures();

        $this->startTest();
    }

    public function testEditBalanceConfig()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();

        $balanceConfig = $this->getDbEntityById('balance_config', '100yz000yz00yz');

        $this->assertEquals(5000000, $balanceConfig['negative_limit_auto']);

        $this->assertEquals(6000000, $balanceConfig['negative_limit_manual']);

        $this->assertEquals(['transfer', 'refund', 'payment'], $balanceConfig['negative_transaction_flows']);
    }

    public function testEditBalanceConfigForZeroAutoAndManualLimits()
    {
        $this->setUpEditRequestFixtures();

        $this->fixtures->base->editEntity('balance_config', '100yz000yz00yz',
            [
                'negative_limit_manual'    => 0,
                'negative_limit_auto'      => 0
            ]
        );

        $this->startTest();

        $balanceConfig = $this->getDbEntityById('balance_config', '100yz000yz00yz');

        $this->assertEquals(0, $balanceConfig['negative_limit_auto']);

        $this->assertEquals(6000000, $balanceConfig['negative_limit_manual']);

        $this->assertEquals(['transfer', 'refund', 'payment'], $balanceConfig['negative_transaction_flows']);
    }


    public function testEditBalanceConfigForDifferentBalanceType()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();
    }

    public function testEditBalanceConfigInvalidNegativeLimit()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();
    }

    public function testEditBalanceConfigInvalidNegativeTransactionFlowForPrimary()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();
    }

    public function testEditBalanceConfigInvalidNegativeTransactionFlowForBanking()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();
    }

    public function testEditBalanceConfigForRemoveNegativeTransactionFlows()
    {
        $this->setUpEditRequestFixtures();

        $this->startTest();

        $balanceConfig = $this->getDbEntityById('balance_config', '100yz000yz00yz');

        $this->assertEquals(['transfer', 'payment'], $balanceConfig['negative_transaction_flows']);
    }

    private function setUpCreateRequestFixtures()
    {
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $balanceDataPrimary = [
            'id'                => '100abc000abc00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'primary',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $balanceDataBanking = [
            'id'                => '100def000def00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '278495738293068',
            'account_type'      => 'shared',
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance', $balanceDataBanking);

        $this->fixtures->create('balance', $balanceDataPrimary);

        $this->ba->adminAuth();
    }

    private function setUpCreateRequestFixturesForNonExistingBalance()
    {
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $balanceDataBanking = [
            'id'                => '100def000def00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '278495738293068',
            'account_type'      => 'shared',
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance', $balanceDataBanking);

        $this->ba->adminAuth();
    }

    private function setUpCreateRequestFixturesForExistingBalanceConfig()
    {
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00']);

        $balanceDataPrimary = [
            'id'                => '100abc000abc00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'primary',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance', $balanceDataPrimary);

        $this->fixtures->create(
            'balance_config',
            [
                'balance_id'                    => '100abc000abc00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['payment'],
                'negative_limit_auto'           => 6000000,
                'negative_limit_manual'         => 0
            ]
        );

        $this->ba->adminAuth();
    }

    private function setUpGetRequestFixtures($mode = Mode::TEST)
    {
        $this->fixtures->on($mode)->create('merchant', ['id' => '100cq000cq00cq']);

        $balanceDataPrimary = [
            'id'                => '100mn000mn00mn',
            'merchant_id'       => '100cq000cq00cq',
            'type'              => 'primary',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $balanceDataBanking = [
            'id'                => '100qr000qr00qr',
            'merchant_id'       => '100cq000cq00cq',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '278495738293068',
            'account_type'      => 'shared',
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->on($mode)->create('balance', $balanceDataPrimary);

        $this->fixtures->on($mode)->create('balance', $balanceDataBanking);

        $this->fixtures->on($mode)->create(
            'balance_config',
            [
                'id'                            => '100op000op00op',
                'balance_id'                    => '100mn000mn00mn',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['refund'],
                'negative_limit_auto'           => 5000000,
                'negative_limit_manual'         => 5000000
            ]
        );

        $this->fixtures->on($mode)->create(
            'balance_config',
            [
                'id'                            => '100st000st00st',
                'balance_id'                    => '100qr000qr00qr',
                'type'                          => 'banking',
                'negative_transaction_flows'   => ['payout'],
                'negative_limit_auto'          => 0,
                'negative_limit_manual'        => 5000000
            ]
        );

        $user = $this->fixtures->user->createUserForMerchant('100cq000cq00cq', [], 'owner', $mode);

        $this->ba->proxyAuth('rzp_'.$mode.'_100cq000cq00cq', $user->getId());
    }

    private function setUpNonExistingBalanceConfigFixtures()
    {
        $this->fixtures->create('merchant', ['id' => '100su000su00su']);

        $balanceDataPrimary = [
            'id'                => '100cp000cp00cp',
            'merchant_id'       => '100su000su00su',
            'type'              => 'primary',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $balanceDataBanking = [
            'id'                => '100sy000sy00sy',
            'merchant_id'       => '100su000su00su',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '278495738293068',
            'account_type'      => 'shared',
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance', $balanceDataPrimary);
        $this->fixtures->create('balance', $balanceDataBanking);

        $this->fixtures->create(
            'balance_config',
            [
                'id'                            => '100sb000sb00sb',
                'balance_id'                    => '100cp000cp00cp',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['refund'],
                'negative_limit_auto'           => 5000000,
                'negative_limit_manual'         => 5000000
            ]
        );

        $user = $this->fixtures->user->createUserForMerchant('100su000su00su', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_100su000su00su', $user->getId());
    }

    private function setUpEditRequestFixtures()
    {
        $this->fixtures->create('merchant', ['id' => '100pqr000pqr00']);

        $balanceDataPrimary = [
            'id'                => '100stu000stu00',
            'merchant_id'       => '100pqr000pqr00',
            'type'              => 'primary',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $balanceDataBanking = [
            'id'                => '100vwx000vwx00',
            'merchant_id'       => '100pqr000pqr00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '278495738293068',
            'account_type'      => 'shared',
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance', $balanceDataBanking);
        $this->fixtures->create('balance', $balanceDataPrimary);

        $this->fixtures->create(
            'balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100stu000stu00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['refund'],
                'negative_limit_manual'        => 5000000,
                'negative_limit_auto'          => 5000000
            ]
        );

        $this->fixtures->create(
            'balance_config',
            [
                'id'                            => '100ab000ab00ab',
                'balance_id'                    => '100vwx000vwx00',
                'type'                          => 'banking',
                'negative_transaction_flows'   => ['payout'],
                'negative_limit_auto'          => 0,
                'negative_limit_manual'        => 5000000
            ]
        );

        $this->ba->adminAuth();
    }

    public function testFetchSharedBankingBalances()
    {
        $this->fixtures->create('balance',
                                     [
                                         'merchant_id'    => '10000000000000',
                                         'type'           => 'primary',
                                         'account_type'   => 'shared',
                                         'balance'        => 100000,
                                         'account_number' => '2224440041626905',
                                     ]);

        $this->fixtures->create('balance',
                                     [
                                         'merchant_id'    => '10000000000000',
                                         'type'           => 'banking',
                                         'account_type'   => 'shared',
                                         'balance'        => 100000,
                                         'account_number' => '2224440041626906',
                                     ]);

        $this->fixtures->create('balance',
                                     [
                                         'merchant_id'    => '10000000000000',
                                         'type'           => 'banking',
                                         'account_type'   => 'direct',
                                         'balance'        => 100000,
                                         'account_number' => '2224440041626907',
                                     ]);

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();
    }
}
