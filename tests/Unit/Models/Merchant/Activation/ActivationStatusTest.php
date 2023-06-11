<?php


namespace Unit\Models\Merchant\Activation;

use DB;
use Illuminate\Support\Facades\Mail;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Mail\Merchant\NeedsClarificationEmail;

use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action\Constants;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Merchant\MerchantTest;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AccessMap as MerchantAccessMap;
use RZP\Models\Workflow\Action\Differ;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Mail\Merchant\SubMerchantNCStatusChanged as SubMerchantNCStatusChangedEmail;

class ActivationStatusTest extends OAuthTestCase
{
    use DbEntityFetchTrait;
    use MocksSplitz;

    const DEFAULT_MERCHANT_ID    = '10000000000000';

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

        $this->fixtures->create('org_hostname', [
            'org_id'    => OrgEntity::RAZORPAY_ORG_ID,
            'hostname'  => 'testing.testing.com'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'activation_status' => $activationStatus,
            'kyc_clarification_reasons'        => [
                'clarification_reasons' => [
                    'contact_email' => [
                        [
                            'from'        => 'admin',
                            'reason_type' => 'predefined',
                            'field_value' => 'adnakdad',
                            'reason_code' => 'provide_poc',
                        ]
                    ],
                ],
            ],
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
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

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

    public function testMailsOnNeedsClarificationNoPartner()
    {

        Mail::fake();

        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $splitzMock  = $this->getSplitzMock();
        $splitzInput = [
            "experiment_id" => "JbkwT9fC4Jn7it",
            "id"            => "10000000000000",
        ];
        $splitzMock->shouldReceive('evaluateRequest')->atMost()->times(0)->with($splitzInput);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        // Change to NC
        $input = [
            'activation_status' => Detail\Status::NEEDS_CLARIFICATION
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        // check status changed successfully
        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals($merchantDetail->getActivationStatus(), Detail\Status::NEEDS_CLARIFICATION);

        // check email sent to merchant
        Mail::assertQueued(NeedsClarificationEmail::class);

        // check email not sent as no partner
        Mail::assertNotQueued(SubMerchantNCStatusChangedEmail::class);
    }

    public function testMailsOnNeedsClarificationForAggregator()
    {
        Mail::fake();

        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
            'merchant_id'     => $merchant->getId(),
            'entity_type'     => 'application',
            'entity_id'       => $managedApp->getId()
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $splitzInput = [
            "experiment_id" => "JbkwT9fC4Jn7it",
            "id"            => "10000000000000",
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        // Change to NC
        $input = [
            'activation_status' => Detail\Status::NEEDS_CLARIFICATION
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        // check status changed successfully
        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals($merchantDetail->getActivationStatus(), Detail\Status::NEEDS_CLARIFICATION);

        // check email sent to merchant
        Mail::assertQueued(NeedsClarificationEmail::class);

        // check email sent to partner
        Mail::assertQueued(SubMerchantNCStatusChangedEmail::class);
    }

    public function testSmsOnNeedsClarification()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $this->fixtures->user->createUserForMerchant(
            $merchant->getId(), ['contact_mobile' => '+919123456789', 'contact_mobile_verified' => true ], 'owner');

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
            'merchant_id'     => $merchant->getId(),
            'entity_type'     => 'application',
            'entity_id'       => $managedApp->getId()
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $splitzInput = [
            "experiment_id" => "JbkwT9fC4Jn7it",
            "id"            => "10000000000000",
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        // Change to NC
        $input = [
            'activation_status' => Detail\Status::NEEDS_CLARIFICATION
        ];

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $merchantTestUtil = new MerchantTest();
        $merchantTestUtil->expectStorkSmsRequest($storkMock, 'Sms.Partner.Submerchant.Needs_clarification', '+919123456789', []);

        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        // check status changed successfully
        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals($merchantDetail->getActivationStatus(), Detail\Status::NEEDS_CLARIFICATION);
    }

    public function testMailsOnNeedsClarificationForReseller()
    {
        Mail::fake();

        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $referredApp = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller'], true);

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
            'merchant_id'     => $merchant->getId(),
            'entity_type'     => 'application',
            'entity_id'       => $referredApp->getId()
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $splitzMock  = $this->getSplitzMock();
        $splitzInput = [
            "experiment_id" => "JbkwT9fC4Jn7it",
            "id"            => "10000000000000",
        ];
        $splitzMock->shouldReceive('evaluateRequest')->atMost()->times(0)->with($splitzInput);

        // Change to NC
        $input = [
            'activation_status' => Detail\Status::NEEDS_CLARIFICATION
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        // check status changed successfully
        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals($merchantDetail->getActivationStatus(), Detail\Status::NEEDS_CLARIFICATION);

        // check email sent statuses
        Mail::assertQueued(NeedsClarificationEmail::class);

        Mail::assertNotQueued(SubMerchantNCStatusChangedEmail::class);
    }

    public function testStatusUpdateToActivatedKycPending()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $this->fixtures->merchant->addFeatures(['no_doc_onboarding']);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        // check status changed successfully
        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $merchant = $merchantDetail->merchant;

        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());
        $this->assertFalse($merchantDetail->isLocked());
        $this->assertContains('No_doc_partially_activated', $merchant->tagNames());
    }

    public function testCheckIfPaymentsRemainEnabledForPartiallyActivatedNoDocMerchant()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $this->fixtures->merchant->addFeatures(['no_doc_onboarding']);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $input = [
            'activation_status' => Detail\Status::UNDER_REVIEW
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::UNDER_REVIEW, $merchantDetail->getActivationStatus());

        $merchant = $merchantDetail->merchant;

        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());
        $this->assertTrue($merchantDetail->isLocked());
        $this->assertContains('No_doc_partially_activated', $merchant->tagNames());
    }

    public function testActivatedStateForPartiallyActivatedNoDocMerchant()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $this->fixtures->merchant->addFeatures(['no_doc_onboarding']);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $input = [
            'activation_status' => Detail\Status::UNDER_REVIEW
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::UNDER_REVIEW, $merchantDetail->getActivationStatus());


        $input = [
            'activation_status' => Detail\Status::ACTIVATED
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED, $merchantDetail->getActivationStatus());

        $merchant = $merchantDetail->merchant;

        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());
        $this->assertTrue($merchantDetail->isLocked());
        $this->assertContains('No_doc_partially_activated', $merchant->tagNames());

        $isNoDocOnboardingFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::NO_DOC_ONBOARDING], $merchant->getId());
        $this->assertEquals(false, $isNoDocOnboardingFeatureEnabled);
    }

    public function testRejectedStateForPartiallyActivatedNoDocMerchant()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $this->fixtures->merchant->addFeatures(['no_doc_onboarding']);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $input = [
            'activation_status' => Detail\Status::UNDER_REVIEW
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::UNDER_REVIEW, $merchantDetail->getActivationStatus());


        $input = [
            'activation_status' => Detail\Status::REJECTED
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::REJECTED, $merchantDetail->getActivationStatus());

        $merchant = $merchantDetail->merchant;

        $this->assertFalse($merchant->isLive());
        $this->assertTrue($merchant->getHoldFunds());
        $this->assertContains('No_doc_partially_activated', $merchant->tagNames());

        $isNoDocOnboardingFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::NO_DOC_ONBOARDING], $merchant->getId());
        $this->assertEquals(false, $isNoDocOnboardingFeatureEnabled);
    }

    public function testActivatedMccPendingStateForNoDocMerchant()
    {
        $fixtures       = $this->createAndFetchFixtures(Detail\Status::UNDER_REVIEW);
        $merchantDetail = $fixtures['merchantDetail'];
        $merchant       = $merchantDetail->merchant;
        $admin          = $fixtures['admin'];

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $this->fixtures->merchant->addFeatures(['no_doc_onboarding']);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $input = [
            'activation_status' => Detail\Status::UNDER_REVIEW
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::UNDER_REVIEW, $merchantDetail->getActivationStatus());

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_MCC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $admin);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_MCC_PENDING, $merchantDetail->getActivationStatus());

        $merchant = $merchantDetail->merchant;

        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());
        $this->assertContains('No_doc_partially_activated', $merchant->tagNames());

        $isNoDocOnboardingFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::NO_DOC_ONBOARDING], $merchant->getId());
        $this->assertEquals(false, $isNoDocOnboardingFeatureEnabled);
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
