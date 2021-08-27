<?php

namespace Unit\Models\Merchant\Tnc;

use DB;
use Mail;
use Hash;
use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TncActivationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    public function testTncNotApplicableWFExecutedMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'       => 4,
            'business_website'    => 'https://razorpay.com',
            'submitted'           => 1,
            'bank_account_name'   => 'Test',
            'bank_account_number' => '111000',
            'bank_branch_ifsc'    => 'SBIN0007105',
        ]);

        $merchant = $merchantDetail->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    /**
     * Scenario:
     *  - merchant hasn't filled website details
     *  - merchant is of Axis org
     *  - merchant hasn't filled TnC details
     * Expectation:
     *  - merchant should get activated even if tnc is not filled
     */
    public function testTncApplicableNotGeneratedWFExecutedMerchantNotActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'    => OrgEntity::AXIS_ORG_ID,
            'hostname'  => 'hdfcbank.in'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => '',
        ]);

        $merchant = $merchantDetail->merchant;
        $merchant->setAttribute('org_id', $org->getId());

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    public function testTncApplicableAndGeneratedWFExecutedMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'test';

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => ''
        ]);

        $this->fixtures->create('merchant_tnc', [
            'merchant_id'           => $merchantDetail->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
        ]);

        $merchant = $merchantDetail->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        (new Merchant\Activate)->activate($merchant);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());
    }

    public function testTncApplicableGenerationExecutedWFExistsMerchantActivated()
    {
        $this->mockRazorxTreatment();

        $this->app['rzp.mode'] = 'live';

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'    => OrgEntity::AXIS_ORG_ID,
            'hostname'  => 'hdfcbank.in'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'    => 4,
            'submitted'        => 1,
            'business_website' => ''
        ]);

        $perm = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::EDIT_ACTIVATE_MERCHANT
        ]);

        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::AXIS_ORG_ID,
            'name'   => "TnC Workflow"
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
            'workflow_id'      => $workflow->getId(),
            'permission_id'    => $perm->getId()
        ]);

        DB::connection('live')->table('permission_map')->insert([
            'entity_id'     => OrgEntity::AXIS_ORG_ID,
            'entity_type'   => 'org',
            'permission_id' => $perm->getId(),
        ]);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => $merchantDetail->getId(),
            'entity_name'   => 'merchant_detail',
            'approved'      => 1,
            'permission_id' => $perm->getId(),
            'workflow_id'   => $workflow->getId()
        ]);

        $merchant = $merchantDetail->merchant;
        $merchant->setAttribute('org_id', $org->getId());
        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            'merchant_id'           => $merchantDetail->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
            'support_email'         => 'boom@example.com'
        ];

        (new Merchant\Detail\Service)->saveMerchantTnc($input);

        $merchant = $this->getDbLastEntity('merchant');

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertTrue($merchant->isActivated());

        $this->assertEquals('activated' , $merchantDetail->getActivationStatus());
    }
}
