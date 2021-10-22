<?php


namespace RZP\Tests\Functional\Merchant;

use DB;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Workflow\Action\Repository as ActionRepository;
use RZP\Models\Workflow\Action;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class NeedsClarificationRespondedTest extends OAuthTestCase
{
    use TerminalTrait;
    use PartnerTrait;
    use EntityActionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use FundAccountValidationTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NeedsClarificationRespondedTestData.php';

        parent::setUp();
    }

    protected function createPricingFixture()
    {
        $methods = ['netbanking', 'card', 'wallet', 'bank_transfer'];
        $pricing = null;

        foreach ($methods as $method){
            $pricingPlan = [
                'plan_name'      => 'Zero pricing plan',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                "min_fee"        => 0,
                'org_id'         => '100000razorpay',
                'type'           => 'pricing',
                'product'        => 'primary',
                "feature"        => 'fund_account_validation',
                'payment_method' => $method,
                'international'  => 0
            ];
            $pricing = $this->fixtures->create('pricing', $pricingPlan);
        }
        return $pricing;
    }

    private function createStateFixture($state, $merchantId, $adminId)
    {
        $this->fixtures->create('state', [
            'entity_id'   => $merchantId,
            'entity_type' => 'merchant_detail',
            'name'        => $state,
            'admin_id'    => $adminId
        ]);
    }

    private function getPermission(){
        return (new PermissionRepository)->findByOrgIdAndPermission(
            '100000razorpay', 'edit_activate_merchant'
        );
    }

    private function createFixtures()
    {
        $plan = $this->createPricingFixture();
        $merchant = $this->fixtures->create('merchant', [
            'pricing_plan_id' => $plan->getPlanId(),
        ]);
        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => $merchant->getId()
        ]);
        $attribute = [
            'activation_status' => 'needs_clarification',
            'merchant_id'       => $merchant->getId(),
            'business_category'           => 'ecommerce',
            'business_subcategory'        => 'fashion_and_lifestyle',
        ];

        $this->fixtures->create('merchant_detail:valid_fields', $attribute);

        $permission = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::NEEDS_CLARIFICATION_RESPONDED
        ]);

        // Creating workflow
        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
            'name'   => "NC Workflow"
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
            'workflow_id'      => $workflow->getId(),
            'permission_id'    => $permission->getId()
        ]);
        DB::connection('live')->table('permission_map')->insert([
            'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
            'entity_type'   => 'org',
            'permission_id' => $permission->getId(),
        ]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);
        return [$merchant->getId(), $permission->getId(), $admin];
    }

    public function testNeedsClarificationRespondedFirstTime()
    {
        $this->markTestSkipped();

        [$merchantId, $permissionId, $admin] = $this->createFixtures();
        $this->createStateFixture('needs_clarification', $merchantId, $admin->getId());

        $this->ba->proxyAuth('rzp_live_' .$merchantId);

        $testData = $this->testData['needsClarificationResponded'];
        $this->startTest($testData);

        $actions = (new ActionRepository)->fetchWorkflowAction(
            $merchantId, 'merchant_detail', $permissionId
        );

        $this->assertNotEmpty($actions);
    }

    public function testNeedsClarificationRespondedSecondTime()
    {
        $this->markTestSkipped();
        [$merchantId, $permissionId, $admin] = $this->createFixtures();

        $this->createStateFixture('needs_clarification', $merchantId, $admin->getId());
        $this->createStateFixture('under_review', $merchantId, $admin->getId());
        $this->createStateFixture('needs_clarification', $merchantId, $admin->getId());

        $this->ba->proxyAuth('rzp_live_' .$merchantId);

        $testData = $this->testData['needsClarificationResponded'];
        $this->startTest($testData);

        $actions = (new ActionRepository)->fetchWorkflowAction(
            $merchantId, 'merchant_detail', $permissionId
        );

        $this->assertNotEmpty($actions);
    }

    // assert that workflow is not created in this case
    public function testActivationBeforeNeedsClarification()
    {
        [$merchantId, $permissionId, $admin] = $this->createFixtures();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' .$merchantId, $merchantUser['id']);

        $testData = $this->testData['needsClarificationResponded'];
        $this->startTest($testData);

        $actions = (new ActionRepository)->fetchWorkflowAction(
            $merchantId, 'merchant_detail', $permissionId
        );

        $this->assertEmpty($actions);
    }
}
