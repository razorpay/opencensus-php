<?php

namespace RZP\Tests\Functional\Helpers\Payout;

use DB;
use Config;

use RZP\Models\Admin;
use RZP\Models\Merchant;
use RZP\Models\Payout\Core;
use RZP\Models\Pricing\Fee;
use RZP\Services\Mock\Mozart;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Models\Workflow\Step\Entity;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Models\Admin\Org\Repository as OrgRepository;

trait PayoutTrait
{
    protected function makePayoutSummaryRequest()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function processFeeRecoveryCron()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/fee_recovery/process',
            'content' => []
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayouts($mode = 'test')
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process/new',
        ];

        $this->ba->cronAuth($mode);

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayoutsOld()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process',
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayoutsWithBlacklist(string $balanceId)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process/new',
            'content' => [
                'balance_ids_not' => [$balanceId]
            ]
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayoutsWithWhitelist(string $balanceId)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process/new',
            'content' => [
                'balance_ids' => [$balanceId]
            ]
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function mockMozartResponseForFetchingBalanceFromRblGateway($amount): void
    {
        $this->app->forgetInstance('mozart');

        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           'data' => [
                                               'success' => true,
                                               Rbl\Fields::GET_ACCOUNT_BALANCE => [
                                                   Rbl\Fields::BODY => [
                                                       Rbl\Fields::BAL_AMOUNT => [
                                                           Rbl\Fields::AMOUNT_VALUE => $amount
                                                       ]
                                                   ]
                                               ]
                                           ]
                                       ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function retryPayout($id)
    {
        $request = [
            'url' => "/payouts/$id/retry",
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['id']);
        $this->assertNotEquals($id, $response['id']);
    }

    public function createPayoutWorkflowWithBankingUsersLiveMode($isBulkApproveAsyncEnabled = false)
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Merchant\Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $workflow = $this->setupWorkflowForLiveMode();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($isBulkApproveAsyncEnabled)
                {
                    if ($feature === Merchant\RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_DISABLED)
                    {
                        return 'on';
                    }

                    if ($feature === Merchant\RazorxTreatment::PAYOUT_BULK_APPROVE_ASYNC && $isBulkApproveAsyncEnabled === true)
                    {
                        return 'on';
                    }

                    return 'control';
                }));

        $steps = $workflow->steps()->get()->toArrayPublic();

        // Creating Owner role corresponding to banking owner role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::OWNER_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Owner',
        ]);

        // Creating Banking Admin role corresponding to banking admin role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::BANKING_ADMIN_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Banking Admin',
        ]);

        // Creating Finance L1 role corresponding to banking finance_l1 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L1_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L1',
        ]);

        // Creating Finance L2 role corresponding to banking finance_l2 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L2_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L2',
        ]);

        // Creating Finance L3 role corresponding to banking finance_l3 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L3_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L3',
        ]);

        // Hardcoding here because default workflow array is known and fixed
        $stepId = $steps['items'][1]['id'];

        Entity::verifyIdAndStripSign($stepId);

        // Changing Checker role to Owner role because only users with banking roles can approve payouts
        $this->fixtures->on('live')->edit(
            'workflow_step',
            $stepId,
            [
                'role_id'      => 'RzpOwnerRoleId'
            ]);

        // Hardcoding here because default workflow array is known and fixed
        $stepId = $steps['items'][2]['id'];

        Entity::verifyIdAndStripSign($stepId);

        // Changing Maker role to Finance L3 role because only users with banking roles can approve payouts
        $this->fixtures->on('live')->edit(
            'workflow_step',
            $stepId,
            [
                'role_id'      => 'RzpFinL3RoleId'
            ]);

        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner','live');

        // add pg mapping as well to catch edge cases
        $this->fixtures->on('live')->create('user:user_merchant_mapping', [
            'merchant_id' => '10000000000000',
            'user_id'     => $this->ownerRoleUser->id,
            'role'        => 'manager',
            'product'     => 'primary',
        ]);

        $this->finL3RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l3','live');

        // add pg mapping as well to catch edge cases
        $this->fixtures->on('live')->create('user:user_merchant_mapping', [
            'merchant_id' => '10000000000000',
            'user_id'     => $this->finL3RoleUser->id,
            'role'        => 'manager',
            'product'     => 'primary',
        ]);

        // The default bank account getting created has ifsc code prefix 'RAZR' even in live mode, which is modified here
        $this->fixtures->on('live')->edit(
            'bank_account',
            '1000000lcustba',
            [
                'ifsc_code'      => 'YESB0CMSNOC'
            ]);

        return $workflow;
    }

    /**
     * This is just so bad way of doing this, All the fixtures here are hardcoded and none of them can
     * be reused. For Ex : I would ideally want to use createPayoutWorkflowWithBankingUsersLiveMode to
     * handle this for me, but that function just knows too much about the workflow and if I change it
     * a lot of depenedent tests fail, hence creating this 😔
     */
    public function buildRolesRequiredForWorkflow() {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Merchant\Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        // Creating Owner role corresponding to banking owner role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::OWNER_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Owner',
        ]);

        // Creating Banking Admin role corresponding to banking admin role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::BANKING_ADMIN_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Banking Admin',
        ]);

        // Creating Finance L1 role corresponding to banking finance_l1 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L1_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L1',
        ]);

        // Creating Finance L2 role corresponding to banking finance_l2 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L2_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L2',
        ]);

        // Creating Finance L3 role corresponding to banking finance_l3 role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::FINANCE_L3_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Finance L3',
        ]);

        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->finL1RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l1', 'live');
        $this->finL2RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l2', 'live');

        $this->finL3RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l3', 'live');

        // The default bank account getting created has ifsc code prefix 'RAZR' even in live mode, which is modified here
        $this->fixtures->on('live')->edit(
            'bank_account',
            '1000000lcustba',
            [
                'ifsc_code' => 'YESB0CMSNOC'
            ]);

    }

    protected function createPayoutWithWorkflow($payoutAttributes = [], $authKey = null)
    {
        $this->disableWorkflowMocks();

        return $this->createQueuedOrPendingPayout($payoutAttributes, $authKey);
    }

    protected function createQueuedOrPendingPayout(array $attributes = [], string $authKey = null)
    {
        $content = [
            'account_number'        => $attributes['account_number'] ?? '2224440041626905',
            'amount'                => $attributes['amount'] ?? 10000,
            'currency'              => 'INR',
            'purpose'               => $attributes['purpose'] ?? 'refund',
            'fund_account_id'       => $attributes['fund_account_id'] ?? 'fa_100000000000fa',
            'mode'                  => $attributes['mode'] ?? 'NEFT',
            'queue_if_low_balance'  => $attributes['queue_if_low_balance'] ?? 0,
        ];

        if (isset($attributes['scheduled_at']))
        {
            $content['scheduled_at'] = $attributes['scheduled_at'];
        }

        if (isset($attributes['notes']))
        {
            $content['notes'] = $attributes['notes'];
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => $content,
        ];

        $this->ba->privateAuth($authKey);

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function createPayoutWithOtpWithWorkflow($payoutAttributes = [], $authKey = null, $merchantUser = null)
    {
        $this->disableWorkflowMocks();

        return $this->createQueuedPendingOrScheduledPayoutWithOtp($payoutAttributes, $authKey, $merchantUser);
    }

    protected function createQueuedPendingOrScheduledPayoutWithOtp(array $attributes = [],
                                                                   string $authKey = null,
                                                                   $merchantUser = null)
    {
        $content = [
            'account_number'        => $attributes['account_number'] ?? '2224440041626905',
            'amount'                => $attributes['amount'] ?? 10000,
            'currency'              => 'INR',
            'purpose'               => 'refund',
            'fund_account_id'       => $attributes['fund_account_id'] ?? 'fa_100000000000fa',
            'mode'                  => $attributes['mode'] ?? 'NEFT',
            'queue_if_low_balance'  => $attributes['queue_if_low_balance'] ?? 0,
            'otp'                   => '0007',
            'token'                 => 'BUIj3m2Nx2VvVj',
        ];

        if (isset($attributes['scheduled_at']))
        {
            $content['scheduled_at'] = $attributes['scheduled_at'];
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts_with_otp',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => $content,
        ];

        $this->ba->proxyAuth($authKey, $merchantUser);

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function disableWorkflowMocks()
    {
        $this->app['config']->set('heimdall.workflows.mock', false);

        $this->app['config']->set('heimdall.permissions.payouts.create_payout.assignable', true);
    }

    protected function setupWorkflowForLiveMode(array $workflow = null)
    {
        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_DISABLED)
                    {
                        return 'on';
                    }

                    return 'control';
                }));

        // $permission = $this->fixtures->on('live')->create('permission',
        //     [
        //         'name'      => 'create_payout',
        //         'category'  => 'payouts'
        //     ]
        // );

        // DB::connection('live')->table('permission_map')->insert(
        // // maker role
        //     [
        //         'entity_id'     => Org::RZP_ORG,
        //         'entity_type'   => 'org',
        //         'permission_id' => $permission->getId(),
        //     ]);

        $org = (new OrgRepository)->getRazorpayOrg();

        // $this->fixtures->create('role', [
        //     'id'     => Org::ADMIN_ROLE,
        //     'org_id' => Org::RZP_ORG,
        //     'name'   => Config::get('heimdall.default_role_name'),
        // ]);

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $workflow = $this->createWorkflow(is_null($workflow) ? $this->getDefaultWorkflowParams() : $workflow,
            'live');

        $attributes = [
            'merchant_id' => '10000000000000',
            'min_amount'  => 0,
            'max_amount'  => 5000000,
            'workflow_id' => $workflow->getId(),
        ];

        $this->fixtures->on('live')->create('workflow_payout_amount_rules', $attributes);

        $this->createWorkflowCheckerRoleUser();

        return $workflow;
    }

    private function getDefaultWorkflowParams(){
        return [
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['create_payout'],
        ];
    }

    protected function createWorkflowCheckerRoleUser()
    {
        // Create Checker Role User
        $checkerRole = $this->getDbEntityById('role', Org::CHECKER_ROLE, 'live');

        $this->checkerRoleUser = $this->fixtures->on('live')->user->createUserForMerchant('10000000000000', [], Org::CHECKER_ROLE);

        DB::connection('live')->table('role_map')->insert(
            [
                'role_id'     => $checkerRole->getId(),
                'entity_type' => 'user',
                'entity_id'   => $this->checkerRoleUser->getId(),
            ]);
    }

    public function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBankingLive(true, 10000000);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);
    }

    protected function runBalanceFetchCron()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/rbl/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function runBankingAccountStatementFetchCron()
    {
        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function liveSetUpForRbl()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBankingLive(true,
                                                   10000000,
                                                   AccountType::DIRECT,
                                                   Channel::RBL);

        $this->fixtures->on('live')->merchant->edit('10000000000000',
                                                    [
                                                        'pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID
                                                    ]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->fixtures->on('live')->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);
    }

    protected function validateStorkWebhookFireEvent($event, $testData, $storkPayload, $mode='test')
    {
        $this->assertEquals('rx-' . $mode, $storkPayload['event']['service']);
        $this->assertEquals($event, $storkPayload['event']['name']);
        $this->assertEquals('merchant', $storkPayload['event']['owner_type']);
        $this->assertEquals('10000000000000', $storkPayload['event']['owner_id']);
        $this->assertArraySelectiveEquals($testData, json_decode($storkPayload['event']['payload'], true));
    }

    protected function approvePayoutWithRole($payoutId, $authKey, $merchantUser)
    {
        $this->ba->proxyAuth($authKey, $merchantUser);

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/' . $payoutId . '/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createOnHoldPayoutWhenBeneBankIsDown($attributes = ['mode' => 'IMPS'])
    {
        $this->ba->privateAuth();

        $benebankConfig =
            [
                "BENEFICIARY"=> [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "RZPB" => [
                        "status" => "started",
                    ],
                    "default"=> "started",
                ]
            ];

        $defaultContents = [
            'account_number'  => '2224440041626905',
            'amount'          => 2000000,
            'currency'        => 'INR',
            'purpose'         => 'refund',
            'narration'       => 'Batman',
            'mode'            => 'IMPS',
            'fund_account_id' => 'fa_100000000000fa',
            'notes'           => [
                'abc' => 'xyz',
            ]
        ];

        $content = array_merge($defaultContents, $attributes);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT =>$benebankConfig]);

        $request = [
        'method'  => 'POST',
        'url'     => '/payouts',
        'content' => $content,
    ];

        if ($content['mode'] === 'IMPS') {
            $this->expectWebhookEvent('payout.queued');
        }

        $this->makeRequestAndGetContent($request);
    }

    protected function createOnHoldPayoutPartnerBankDown($partnerBankDowntimeData)
    {
        $this->ba->privateAuth();
        $this->setDowntimeInformationForOnHold($partnerBankDowntimeData);
        $defaultContents = [
            'account_number'  => '2224440041626905',
            'amount'          => 2000000,
            'currency'        => 'INR',
            'narration'       => 'Batman',
            'mode'            => 'IMPS',
            'fund_account_id' => 'fa_100000000000fa',
            'purpose'         => 'payout',
            'notes'           => [
                'abc'         => 'xyz',
            ],
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => $defaultContents,
        ];

        $this->makeRequestAndGetContent($request);
    }

    protected function setDowntimeInformationForOnHold($partnerBankDowntimeData)
    {
        $this->ba->privateAuth();
        $redis = $this->app['redis'];

        $channel = $partnerBankDowntimeData['payload']['channel'];
        $accountType = $partnerBankDowntimeData['payload']['account_type'];
        $mode = $partnerBankDowntimeData['payload']['mode'];

        $configKey = strtolower($accountType."_"."$channel"."_".$mode);
        $redis->hset(Core::PARTNER_BANK_HEALTH_REDIS_KEY, $configKey, json_encode($partnerBankDowntimeData['payload']));
    }

    protected function createOnHoldPayoutWhenBeneBankIsDownWithQueueIfLowBalanceFlagTrue()
    {
        $this->ba->privateAuth();

        $benebankConfig =
            [
                "BENEFICIARY"=> [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "RZPB" => [
                        "status" => "started",
                    ],
                    "default"=> "started",
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT =>$benebankConfig]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'queue_if_low_balance' => true,
            ],
        ];

        $this->expectWebhookEvent('payout.queued');

        $this->makeRequestAndGetContent($request);
    }

    protected function updateFtaAndSource($payout_id, $status, $utr = '928337183', $mode = 'test')
    {
        $this->ba->ftsAuth($mode);

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payout_id,
                'source_type'         => 'payout',
                'status'              => $status,
                'utr'                 => $utr,
                'source_account_id'   => 111111111,
                'bank_account_type'   => 'current'
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }


    protected function getPayoutStatusAPI($payoutId)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/'. $payoutId,
        ];

        $this->ba->privateAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function createSkipWorkflowForPayoutFeature()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::IMPS_MODE_PAYOUT_FILTER))
                {
                    return 'yesbank';
                }
                return 'on';
            });

        //
        // Here workflows are enabled for create payouts,
        // However user wants to disable the workflow for API request
        //
        $this->fixtures->merchant->addFeatures([Constants::SKIP_WF_AT_PAYOUTS]);

        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
    }

    protected function setUpExperimentForNWFSAndCAC()
    {
        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on', // just sey this on, leave everything as default
            'on',
            'on',
            'off',
            'control',
            'on',
            'off',
            'control',
            'off',
            'control',
            'on',
            'on'
        );
    }

    protected function setUpExperimentForNWFS()
    {
        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on' // just sey this on, leave everything as default
        );
    }

    public function expectStorkSendSmsRequest($storkMock, $templateName, $destination, $expectedParams = [])
    {
        $storkMock->shouldReceive('sendSms')
                  ->withArgs(
                      function($mode,
                               $actualPayload,
                               $mockInTestMode)
                      use (
                          $templateName,
                          $destination,
                          $expectedParams
                      ) {
                          if (isset($actualPayload['contentParams']) === true)
                          {
                              $this->assertArraySelectiveEquals($expectedParams, $actualPayload['contentParams']);
                          }

                          if (($templateName !== $actualPayload['templateName']) or
                              ($destination !== $actualPayload['destination']))
                          {
                              return false;
                          }

                          return true;
                      }
                  )
                  ->andReturn(
                      ['success' => true]
                  );

        return $storkMock;
    }
}
