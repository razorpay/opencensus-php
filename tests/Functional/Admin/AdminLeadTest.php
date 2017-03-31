<?php

namespace RZP\Tests\Functional\Admin;

use Mail;
use Mockery;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\TestCase;

class AdminLeadTest extends TestCase
{
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminLeadTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken);
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
        $fields = $this->getDefaultFields();

        $role = $this->ba->getAdmin()->roles()->get()[0];

        $this->storeFieldsForEntity(
            $this->org->getPublicId(),
            'admin_lead',
            $fields, $this->authToken);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        Mail::shouldReceive('queue')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                    {
                        $this->assertArrayHasKey('invitation', $data);

                        $this->assertArrayHasKey('adminName', $data);

                        return true;
                    }),
                    Mockery::any()
                );

        $this->startTest();

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testSelfInviteProhibited()
    {
        $fields = $this->getDefaultFields();

        $role = $this->ba->getAdmin()->roles()->get()[0];

        $adminEmail = $this->ba->getAdmin()->getEmail();

        $this->storeFieldsForEntity(
            $this->org->getPublicId(), 'admin_lead',
            $fields, $this->authToken);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $this->testData[__FUNCTION__]['request']['content']['contact_email'] = $adminEmail;

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

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
}
