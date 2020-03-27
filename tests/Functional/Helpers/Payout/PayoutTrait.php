<?php

namespace RZP\Tests\Functional\Helpers\Payout;

use DB;
use Config;

use RZP\Models\Admin;
use RZP\Models\Merchant;
use RZP\Models\Pricing\Fee;
use RZP\Models\Feature\Constants;
use RZP\Models\Workflow\Step\Entity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
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

    protected function dispatchQueuedPayouts()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process',
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
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

    public function createPayoutWorkflowWithBankingUsersLiveMode()
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Merchant\Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $workflow = $this->setupWorkflowForLiveMode();

        $steps = $workflow->steps()->get()->toArrayPublic();

        // Creating Owner role corresponding to banking owner role
        $this->fixtures->on('live')->create('role', [
            'id'     => Org::OWNER_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => 'Owner',
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

        // Changing Checker role to Owner role because only users with banking roles can approve payouts
        $this->fixtures->on('live')->edit(
            'workflow_step',
            $stepId,
            [
                'role_id'      => 'RzpFinL3RoleId'
            ]);

        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner','live');

        $this->finL3RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l3','live');

        // The default bank account getting created has ifsc code prefix 'RAZR' even in live mode, which is modified here
        $this->fixtures->on('live')->edit(
            'bank_account',
            '1000000lcustba',
            [
                'ifsc_code'      => 'YESB0CMSNOC'
            ]);

        return $workflow;
    }



    protected function createPayoutWithWorkflow($workflow, $payoutAttributes = [])
    {
        $this->app['config']->set('heimdall.workflows.mock', false);
        $this->app['config']->set('heimdall.permissions.payouts.create_payout.assignable', true);

        $workflowDefaultPermissions = (new Admin\Permission\Repository())
                                       ->retrieveIdsByNames([Admin\Permission\Name::CREATE_PAYOUT]);

        // Attach permissions to the default workflow
        $workflow->permissions()->sync($workflowDefaultPermissions);

        return $this->createQueuedOrPendingPayout($payoutAttributes);
    }

    protected function createQueuedOrPendingPayout(array $attributes = [], string $authKey = null)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => $attributes["account_number"] ?? '2224440041626905',
                'amount'                => $attributes["amount"] ?? 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'IMPS',
                'queue_if_low_balance'  => $attributes["queue_if_low_balance"] ?? 0,
            ],
        ];

        $this->ba->privateAuth($authKey);

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function disableWorkflowMocks()
    {
        $this->app['config']->set('heimdall.workflows.mock', false);

        $this->app['config']->set('heimdall.permissions.payouts.create_payout.assignable', true);
    }

    protected function setupWorkflowForLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $permission = $this->fixtures->on('live')->create('permission',
            [
                'name'      => 'create_payout',
                'category'  => 'payouts'
            ]
        );

        DB::connection('live')->table('permission_map')->insert(
        // maker role
            [
                'entity_id'     => Org::RZP_ORG,
                'entity_type'   => 'org',
                'permission_id' => $permission->getId(),
            ]);

        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->create('role', [
            'id'     => Org::ADMIN_ROLE,
            'org_id' => Org::RZP_ORG,
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $workflow = $this->createWorkflow([
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['create_payout'],
        ], 'live');

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

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => User::MERCHANT_USER_ID,
            'product'     => 'primary',
            'role'        => 'owner',
        ], 'live');
    }
}
