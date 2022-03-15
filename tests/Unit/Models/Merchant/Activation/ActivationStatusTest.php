<?php


namespace Unit\Models\Merchant\Activation;

use DB;
use RZP\Constants\Mode;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Constants;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Models\Merchant\Detail;
use RZP\Models\Workflow\Action\Differ;

class ActivationStatusTest extends TestCase
{
    use DbEntityFetchTrait;

    public function createAndFetchFixtures($activationStatus)
    {
        // Creating permission
        $perm = $this->fixtures->connection('live')->create('permission', [
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
            'permission_id'    => $perm->getId()
        ]);
        DB::connection('live')->table('permission_map')->insert([
                'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
                'entity_type'   => 'org',
                'permission_id' => $perm->getId(),
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'activation_status' => $activationStatus
        ]);

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $action = $this->fixtures->connection('live')->create('workflow_action', [
            'workflow_id'   => $workflow->getId(),
            'maker_id'      => $admin->getId(),
            'maker_type'    => MakerType::ADMIN,
            'permission_id' => $perm->getId(),
            'entity_id'     => $merchantDetail->getId(),
            'entity_name'   => 'merchant_detail',
            'state'         => 'open'
        ]);

        return [
            'merchantDetail'    => $merchantDetail,
            'admin'             => $admin
        ];
    }

    public function testClosingOnboardingWorkflowOnMerchantRejection()
    {
        $fixtures = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant = $merchantDetail->merchant;
        $admin = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $input = [
            'activation_status' => Detail\Status::REJECTED
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals($merchantDetail->getActivationStatus(), Detail\Status::REJECTED);

        $action = $this->getDbLastEntity('workflow_action', 'live');
        $this->assertEquals($action->getState(), 'closed');
    }

    public function testAutoKycHUF()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                    => '13',
            'poi_verification_status'          => 'verified',
            'company_pan_verification_status'  => 'verified',
            'bank_details_verification_status' => 'verified',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified'
        ]);

        $isAutoKycDone = (new DetailCore)->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }
}
