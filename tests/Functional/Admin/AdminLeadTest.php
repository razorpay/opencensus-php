<?php

namespace RZP\Tests\Functional\Admin;

use Mail;

use RZP\Mail\Admin\MerchantInvitation as MerchantInvitationMail;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AdminLeadTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminLeadTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id'    => $this->org->getId(),
            'hostname'  => 'dashboard.sampleorg.dev',
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    protected function getDefaultFields()
    {
        return [
            'channel_code',
            'contact_email',
            'contact_name',
        ];
    }

    public function testCreateAdminLead()
    {
        Mail::fake();

        $fields = $this->getDefaultFields();

        $role = $this->ba->getAdmin()->roles()->get()[0];

        $this->storeFieldsForEntity(
            $this->org->getPublicId(),
            'admin_lead',
            $fields, $this->authToken);

        $this->startTest();

        Mail::assertSent(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('invitation', $data);

            $this->assertArrayHasKey('adminName', $data);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testSelfInviteProhibited()
    {
        $fields = $this->getDefaultFields();

        $role = $this->ba->getAdmin($this->authToken)->roles()->get()[0];

        $adminEmail = $this->ba->getAdmin($this->authToken)->getEmail();

        $this->storeFieldsForEntity(
            $this->org->getPublicId(), 'admin_lead',
            $fields, $this->authToken);

        $this->testData[__FUNCTION__]['request']['content']['contact_email'] = $adminEmail;

        $this->startTest();
    }

    public function testVerifyAdminLead()
    {
        $adminLead = $this->testCreateAdminLead();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $adminLead['token']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPutAdminLead()
    {
        $adminLead = $this->testCreateAdminLead();

        $orgId = $adminLead['org_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $adminLead['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $orgId);

        $result = $this->startTest();

        $this->assertNotNull($result['signed_up_at']);
    }
}
