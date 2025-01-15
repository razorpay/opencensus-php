<?php

namespace RZP\Tests\Functional\Merchant\Account;

use Mail;

use RZP\Constants\Mode;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Account\Entity as AccountEntity;
use RZP\Models\Merchant\Account\Repository as AccountRepo;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Services\Dcs\Features\Service;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;
use \Illuminate\Database\Query\Builder as QueryBuilder;
class AccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use HeimdallTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);
        $this->fixtures->merchant->addFeatures(['beta_la_create_enable']);

        $this->ba->privateAuth();

        $this->mockApachePinot();

    }

    public function testForAddQueryParamId()
    {

        $queryMock = $this->createMock(QueryBuilder::class);

        $params = [AccountEntity::ID => 'acc_123456'];

        $queryMock->expects($this->once())
            ->method('where')
            ->with($this->equalTo(AccountEntity::ID), $this->equalTo('='), $this->equalTo('123456'));


        $repo = new AccountRepo();

        // Use Reflection to access the protected method
        $reflection = new \ReflectionClass($repo);
        $method = $reflection->getMethod('addQueryParamId');
        $method->setAccessible(true);

        // Invoke the protected method with the mock and parameters
        $method->invoke($repo, $queryMock, $params);
    }

    private function mockApachePinot()
    {
        $pinotService = $this->getMockBuilder(ApachePinotClient::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('apache.pinot', $pinotService);

        $pinotService->method('getDataFromPinot')
                     ->willReturn(null);
    }

    public function mockDCS()
    {
        $dcsMock = $this->getMockBuilder(\RZP\Services\Dcs\Features\Service::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['editFeature'])
            ->getMock();

        $this->app->instance('dcs', $dcsMock);

        $dcsMock->expects($this->any())->method('editFeature')->willReturn(null);

        return $dcsMock;
    }

    public function testCreateLinkedAccountForInactiveMerchantInTestMode()
    {
        $this->createLinkedAccount(Mode::TEST, false);
    }

    public function testCreateLinkedAccountForActiveMerchantInTestMode()
    {
        $this->createLinkedAccount(Mode::TEST, true);
    }

    public function testCreateLinkedAccountForActiveMerchantInLiveMode()
    {
        $this->createLinkedAccount(Mode::LIVE, true);
    }

    public function testCreateLinkedAccountValidationFailure()
    {
        $this->startTest();
    }

    public function testCreateLinkedAccountInvalidBusinessType()
    {
        $this->startTest();
    }

    public function testCreateLinkedAccountIndividual()
    {
        $response = $this->startTest();
    }

    public function testCreateLinkedAccountWithCode()
    {
        $this->fixtures->merchant->addFeatures(['route_code_support']);

        $this->startTest();
    }

    public function testCreateLinkedAccountBetaSuccessIfReverseShadowEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createAccountsOnEvent')
            ->times(2)
            ->andReturn([
                'body' => [
                    "accounts" => [
                        "pg_merchant_onboarding" => null
                    ]
                ],
                'code' => 200
            ]);

        $this->mockDCS();

        $mockLedger->shouldReceive('updateAccountByEntitiesAndMerchantID')
            ->times(5)
            ->andReturn([
                'body' => [
                    "balance" => 12000
                ],
                'code' => 200
            ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ],
                [
                    'body' => [
                        "balance" => 0
                    ],
                    'code' => 200
                ]);

        $this->createLinkedAccount(Mode::TEST, true);

        $lastAccount = $this->getDbLastEntityPublic('merchant');

        $featuresArray = $this->getDbEntity('feature',
            [
                'entity_id' => $lastAccount['id'],
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        $this->assertContains(FeatureConstants::PG_LEDGER_REVERSE_SHADOW, $featuresArray);
    }

    public function testCreateLinkedAccountBetaSuccessIfReverseShadowNotEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->createLinkedAccount(Mode::TEST, true);

        $lastAccount = $this->getDbLastEntityPublic('merchant');

        $featuresArray = $this->getDbEntity('feature',
            [
                'entity_id' => $lastAccount['id'],
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        $this->assertNotContains(FeatureConstants::PG_LEDGER_REVERSE_SHADOW, $featuresArray);
    }

    public function testCreateLinkedAccountBetaFailureIfReverseShadowEnabledForParent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();

        $this->app->instance('ledger', $mockLedger);

        $this->mockDCS();

        $mockLedger->shouldReceive('createAccountsOnEvent')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'not found',
                    ],
                ]
            ));

        try
        {
            $this->createLinkedAccount(Mode::TEST, true);
        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);
        }
    }

    public function testCreateLinkedAccountWithInvalidCode()
    {
        $this->fixtures->merchant->addFeatures(['route_code_support']);

        $this->startTest();
    }


    public function testCreateLinkedAccountWithCodeAlreadyInUse()
    {
        $this->fixtures->merchant->addFeatures(['route_code_support']);

        $testData = $this->testData['testCreateLinkedAccountWithCode'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);

                $this->runRequestResponseFlow($testData);
            },
            BadRequestException::class,
            'This code is already in use, please try another.'
        );
    }

    public function testFetchLinkedAccountByCode()
    {
        $this->testCreateLinkedAccountWithCode();

        $response = $this->startTest();

        $accountId = $this->getDbLastEntity('account')->getId();
        $account = $response['items'][0];
        $this->assertEquals('acc_' . $accountId, $account['id']);
    }

    public function testFetchLinkedAccountByCodeProxyAuth()
    {
        $this->testCreateLinkedAccountWithCode();

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $accountId = $this->getDbLastEntity('account')->getId();
        $account = $response['items'][0];
        $this->assertEquals('acc_' . $accountId, $account['id']);
    }

    public function testUpdateBankAccountForLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures('la_bank_account_update');

        $response = $this->runRequestResponseFlow($this->testData['createLinkedAccount']);

        $id = $response['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/' . $id . '/bank_account';

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->assertEquals('Emma Stone', $bankAccount['beneficiary_name']);

        $this->assertEquals('123412341234', $bankAccount['account_number']);

        $this->assertEquals('SBIN0000004', $bankAccount['ifsc_code']);

        $linkedAccount = $this->getDbLastEntity('merchant');

        $this->assertFalse($linkedAccount['hold_funds']);
    }

    public function testRetrieveAccount()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->startTest();
    }

    public function testRetrieveAccountViaDashboardApiWhenAccountIsSuspended()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->edit('merchant', '10000000000001', ['suspended_at' => 1642901927]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->startTest();
    }

    public function testRetrieveAccounts()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->startTest();
    }

    public function testRetrieveLinkedAccounts()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $merchant->getEmail();

        $this->startTest($testData);
    }

    public function testRetrieveLinkedAccountsViaDashboardApiWhenAccountIsSuspended()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->edit('merchant', '10000000000001', ['suspended_at' => 1642901927]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $merchant->getEmail();

        $this->startTest($testData);
    }

    public function testSettlementDestinations()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $accountId = Account\Entity::getSignedId($merchant['id']);

        Mail::fake();

        $testData = $this->testData['addSettlementDestination'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/bank_accounts';

        $this->startTest($testData);

        Mail::assertNotSent(BankAccountChangeMail::class);

        $testData = $this->testData['fetchSettlementDestinations'];

        $testData['request']['url'] = '/beta/accounts/' . $accountId . '/settlement_destinations';

        $this->startTest($testData);
    }

    protected function createLinkedAccount(string $mode, bool $activate)
    {
        //
        // The fixture for ScheduleTask entity in Test mode is already seeded.
        // When a merchant is created through the API, ScheduleTask entity is
        // created in both the modes.
        //
        $this->fixtures->on('live')->create('merchant:schedule_task',
            [
                'merchant_id' => '10000000000000',
                'schedule'    => [
                    'interval' => 1,
                    'delay'    => 2,
                    'hour'     => 0,
                ],
            ]);

        if ($mode === Mode::LIVE)
        {
            // The merchant account must be activated to make requests in the Live mode
            $this->fixtures->merchant->activate('10000000000000');

            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }
        else
        {
            if ($activate === true)
            {
                $this->fixtures->merchant->activate('10000000000000');
            }

            $this->ba->privateAuth();
        }

        $account = $this->startTest();

        $lastAccount = $this->getDbLastEntityPublic('merchant');

        $accountId = Account\Entity::getSignedId($lastAccount['id']);

        $this->assertEquals($account['id'], $accountId);

        $this->assertEquals('10000000000000', $lastAccount['parent_id']);

        $this->assertNotNull($account['fund_transfer']['destination']);

        $bankAccount = $this->getDbLastEntity('bank_account', 'test');
        $this->assertEquals('RZPB0000000', $bankAccount['ifsc_code']);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');
        $this->assertEquals('0002020000304030434', $bankAccount['account_number']);
    }

    protected function addPermissionToBaAdmin(string $permissionName): void
    {
        $admin = $this->ba->getAdmin();

        if ($admin->hasPermission($permissionName) === true)
        {
            return;
        }

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

    public function testRetrieveLinkedAccountsForMerchantId()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',  ['id' => '10000000000001']);

        // below segment of code may not be actually required. I added it because it was not working without it.

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->settlementsAuth();

        $this->startTest($testData);
    }

    public function testRetrieveLinkedAccountsForMerchantIdAdminDashboard()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',  ['id' => '10000000000001']);

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => $merchant['id'],
                                    'submitted'   => true,
                                    'locked'      => true
                                ]);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('view_merchant');

        $this->startTest($testData);
    }

    public function testRetrieveLinkedAccountsForMerchantIdWithoutLa()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->settlementsAuth();

        $this->startTest($testData);
    }

    public function testRetrieveLinkedAccountsForMerchantIdWithPagination()
    {
        $merchant1 = $this->fixtures->create('merchant:marketplace_account',  ['id' => '10000000000001']);

        // below segment of code may not be actually required. I added it because it was not working without it.

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant1['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->settlementsAuth();

        $this->startTest($testData);
    }

    public function testSuspendStatusPropagationToLinkedAccountWhenMerchantIsSuspended()
    {
        $parentMerchant = $this->getLastEntity('merchant', true);

        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000002', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000003', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000004', 'parent_id' => $parentMerchant['id']]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $parentMerchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $suspendedMerchantIds = [
            $parentMerchant['id'], '10000000000001', '10000000000002', '10000000000003', '10000000000004'
        ];

        foreach ($suspendedMerchantIds as $suspendedMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $suspendedMerchantId);

            $this->assertNotNull($merchant['suspended_at']);
            $this->assertTrue($merchant['hold_funds']);
            $this->assertFalse($merchant['live']);

            if ($suspendedMerchantId !== $parentMerchant['id'])
            {
                $this->assertEquals('account_suspended_due_to_parent_merchant_suspension', $merchant['hold_funds_reason']);
            }
        }
    }

    public function testUnsuspendStatusPropagationToLinkedAccountWhenMerchantIsUnsuspended()
    {
        $parentMerchant = $this->getLastEntity('merchant', true);

        $this->fixtures->edit('merchant', $parentMerchant['id'],
            [
                'suspended_at' => time(), 'hold_funds' => true, 'live' => false
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000001', 'parent_id' => $parentMerchant['id'], 'suspended_at' => time(),
                'live' => false, 'hold_funds' => true, 'hold_funds_reason' => 'suspended due to XYZ'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000002', 'parent_id' => $parentMerchant['id'], 'suspended_at' => time(),
                'live' => false, 'hold_funds' => true, 'hold_funds_reason' => 'suspended due to XYZ'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000003', 'parent_id' => $parentMerchant['id'], 'suspended_at' => time(),
                'live' => false, 'hold_funds' => true, 'hold_funds_reason' => 'account_suspended_due_to_parent_merchant_suspension'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000004', 'parent_id' => $parentMerchant['id'], 'suspended_at' => time(),
                'live' => false, 'hold_funds' => true, 'hold_funds_reason' => 'account_suspended_due_to_parent_merchant_suspension'
            ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $parentMerchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $unsuspendedMerchantIds = [
            $parentMerchant['id'], '10000000000003', '10000000000004'
        ];
        $suspendedMerchantIds = [
            '10000000000001', '10000000000002'
        ];

        foreach ($unsuspendedMerchantIds as $unsuspendedMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $unsuspendedMerchantId);

            $this->assertNull($merchant['suspended_at']);
            $this->assertFalse($merchant['hold_funds']);
            $this->assertTrue($merchant['live']);
            $this->assertNull($merchant['hold_funds_reason']);
        }

        foreach ($suspendedMerchantIds as $suspendedMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $suspendedMerchantId);

            $this->assertNotNull($merchant['suspended_at']);
            $this->assertTrue($merchant['hold_funds']);
            $this->assertFalse($merchant['live']);
            $this->assertEquals('suspended due to XYZ', $merchant['hold_funds_reason']);
        }
    }

    public function testLiveDisablePropagationToLinkedAccountWhenMerchantIsLiveDisabled()
    {
        $parentMerchant = $this->getLastEntity('merchant', true);

        $this->fixtures->merchant->activate($parentMerchant['id']);

        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000002', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000003', 'parent_id' => $parentMerchant['id']]);
        $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000004', 'parent_id' => $parentMerchant['id']]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $parentMerchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $liveDisabledMerchantIds = [
            $parentMerchant['id'], '10000000000001', '10000000000002', '10000000000003', '10000000000004'
        ];

        foreach ($liveDisabledMerchantIds as $liveDisabledMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $liveDisabledMerchantId);

            $this->assertNull($merchant['suspended_at']);
            $this->assertFalse($merchant['hold_funds']);
            $this->assertFalse($merchant['live']);

            if ($liveDisabledMerchantId !== $parentMerchant['id'])
            {
                $this->assertEquals('live_disabled_as_parent_merchant_live_disabled', $merchant['live_disable_reason']);
            }
        }
    }

    public function testLiveEnablePropagationToLinkedAccountWhenMerchantIsLiveEnabled()
    {
        $parentMerchant = $this->getLastEntity('merchant', true);

        $this->fixtures->merchant->activate($parentMerchant['id']);

        $this->fixtures->edit('merchant', $parentMerchant['id'],
            [
                'live' => false
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000001', 'parent_id' => $parentMerchant['id'],
                'live' => false, 'hold_funds' => false, 'live_disable_reason' => 'live disabled due to XYZ'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000002', 'parent_id' => $parentMerchant['id'],
                'live' => false, 'hold_funds' => false, 'live_disable_reason' => 'live disabled due to XYZ'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000003', 'parent_id' => $parentMerchant['id'],
                'live' => false, 'hold_funds' => false, 'live_disable_reason' => 'live_disabled_as_parent_merchant_live_disabled'
            ]);
        $this->fixtures->create('merchant:marketplace_account',
            [
                'id' => '10000000000004', 'parent_id' => $parentMerchant['id'],
                'live' => false, 'hold_funds' => false, 'live_disable_reason' => 'live_disabled_as_parent_merchant_live_disabled'
            ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $parentMerchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $liveEnabledMerchantIds = [
            $parentMerchant['id'], '10000000000003', '10000000000004'
        ];
        $liveDisabledMerchantIds = [
            '10000000000001', '10000000000002'
        ];

        foreach ($liveEnabledMerchantIds as $liveEnabledMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $liveEnabledMerchantId);

            $this->assertNull($merchant['suspended_at']);
            $this->assertFalse($merchant['hold_funds']);
            $this->assertTrue($merchant['live']);
            $this->assertNull($merchant['hold_funds_reason']);
            $this->assertNull($merchant['live_disable_reason']);
        }

        foreach ($liveDisabledMerchantIds as $liveDisabledMerchantId)
        {
            $merchant = $this->getDbEntityById('merchant', $liveDisabledMerchantId);

            $this->assertNull($merchant['suspended_at']);
            $this->assertFalse($merchant['hold_funds']);
            $this->assertFalse($merchant['live']);
            $this->assertNull($merchant['hold_funds_reason']);
            $this->assertEquals('live disabled due to XYZ', $merchant['live_disable_reason']);
        }
    }


    public function testCreateLinkedAccountBetaFailIfBetaEnableFlagNotEnabledForParent()
    {
        $this->fixtures->merchant->removeFeatures(['beta_la_create_enable']);

        $this->startTest();

    }

    public function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }
}
