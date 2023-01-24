<?php

namespace RZP\Tests\Functional\Admin;

use Mail;

use RZP\Models\User\Entity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Mail\Admin\PartnerInvitation as PartnerInvitationMail;
use RZP\Mail\Admin\MerchantInvitation as MerchantInvitationMail;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as OrgEntity;

class AdminLeadTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    /**
     * @var array|mixed
     */
    private $org;

    /**
     * @var string
     */
    private $authToken;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminLeadTestData.php';

        parent::setUp();
    }

    protected function mockOrgCreation($orgId)
    {
        if ($orgId !== OrgEntity::RAZORPAY_ORG_ID)
        {
            $this->org = $this->fixtures->create('org', ['id' => $orgId]);

            $this->fixtures->create('org_hostname', [
                'org_id'   => $this->org->getId(),
                'hostname' => 'dashboard.sampleorg.dev',
            ]);

            $this->authToken = $this->getAuthTokenForOrg($this->org);

            $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

            $fields = $this->getDefaultFields($orgId);

            $role = $this->ba->getAdmin()->roles()->get()[0];

            $this->storeFieldsForEntity(
                $this->org->getPublicId(),
                'admin_lead',
                $fields, $this->authToken);

        }
        else
        {
            $this->org = OrgEntity::find(OrgEntity::RAZORPAY_ORG_ID);

            $this->ba->adminAuth();

            $fields = $this->getDefaultFields($orgId);

            $role = $this->ba->getAdmin()->roles()->get()[0];

            $this->storeFieldsForEntity(
                $this->org->getPublicId(),
                'admin_lead',$fields,null);
        }


    }

    protected function getDefaultFields($orgId)
    {
        if($orgId===OrgEntity::CURLEC_ORG_ID){
            return [
                'channel_code',
                'contact_email',
                'contact_name',
                'country_code'
            ];
        }
        return [
            'channel_code',
            'contact_email',
            'contact_name',
        ];
    }

    public function testCreateAdminLead()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $this->startTest();
    }

    public function testCreateAllowedAdminLead()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $this->startTest();

        Mail::assertQueued(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('invitation', $data);

            $this->assertArrayHasKey('adminName', $data);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testCreateIsDsMerchantAdminLead()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::AXIS_ORG_ID);

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_PROGRAM_DS_CHECK],$this->org->getId());

        $this->startTest();

        Mail::assertQueued(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('invitation', $data);

            $this->assertArrayHasKey('adminName', $data);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testCreateCurlecAdminLead()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::CURLEC_ORG_ID);

        $this->startTest();

        Mail::assertQueued(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('invitation', $data);

            $this->assertArrayHasKey('adminName', $data);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }


    public function testCreatePartnerAdminLead()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $this->startTest();

        Mail::assertQueued(PartnerInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('invitation', $data);

            $this->assertArrayHasKey('adminName', $data);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testExistingEmailInviteProhibited()
    {
        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId()
        ]);

        $email='hello@gmail.com';

        $merchantUser=$this->fixtures->user->createUserForMerchant($merchant->id);

        $this->testData[__FUNCTION__]['request']['content']['contact_email'] = $merchantUser->getEmail();

        $this->startTest();
    }

    public function testSelfInviteProhibited()
    {
        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $adminEmail = $this->ba->getAdmin($this->authToken)->getEmail();

        $this->testData[__FUNCTION__]['request']['content']['contact_email'] = $adminEmail;

        $this->startTest();
    }

    public function testVerifyMerchantInvitation()
    {
        $adminLead = $this->testCreateAllowedAdminLead();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $adminLead['token']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testVerifyAdminLead()
    {
        $adminLead = $this->testCreateAllowedAdminLead();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $adminLead['token']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPutAdminLead()
    {
        $adminLead = $this->testCreateAllowedAdminLead();

        $orgId = $adminLead['org_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $adminLead['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $orgId);

        $result = $this->startTest();

        $this->assertNotNull($result['signed_up_at']);
    }
}
