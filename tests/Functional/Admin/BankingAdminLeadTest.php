<?php

namespace RZP\Tests\Functional\Admin;

use Mail;

use RZP\Mail\Admin\MerchantInvitation as MerchantInvitationMail;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\MocksSplitz;


class BankingAdminLeadTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;
    use MocksSplitz;

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
        if($orgId===OrgEntity::CURLEC_ORG_ID)
        {
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

    // sets razorx mock based on input array of key value pair of features and their expected values
    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                            ->will($this->returnCallback(
                              function ($mid, $feature) use ($razorxTreatment, $defaultBehaviour)
                              {
                                if (array_key_exists($feature, $razorxTreatment) === true)
                                {
                                    return $razorxTreatment[$feature];
                                }

                                return strtolower($defaultBehaviour);
                            }));
    }

    public function testInviteMerchantMailViaMailgun()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $this->startTest();

        Mail::assertQueued(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();

            $this->assertArrayHasKey('invitation', $data);
            $this->assertArrayHasKey('adminName', $data);

            $this->assertEquals($shouldSendEmailViaStork, false);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

    public function testInviteMerchantMailViaStork()
    {
        Mail::fake();

        $this->mockOrgCreation(OrgEntity::RAZORPAY_ORG_ID);

        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable', ]]]);

        $this->startTest();

        Mail::assertQueued(MerchantInvitationMail::class, function ($mail)
        {
            $data = $mail->viewData;
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();
            $getParamsForStork = $mail->getParamsForStork();

            $this->assertArrayHasKey('invitation', $data);
            $this->assertArrayHasKey('adminName', $data);
            $this->assertArrayHasKey('template_name', $getParamsForStork);
            $this->assertArrayHasKey('template_namespace', $getParamsForStork);
            $this->assertArrayHasKey('org_id', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('sign_up_url', $getParamsForStork['params']);
            $this->assertArrayHasKey('login_url', $getParamsForStork['params']);
            $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']);

            $this->assertEquals($shouldSendEmailViaStork, true);

            return true;
        });

        $adminLead = $this->getLastEntity('admin_lead', true);

        return $adminLead;
    }

}
