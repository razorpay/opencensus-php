<?php

namespace Functional\Partner\Activation;

use DB;
use Mail;
use RZP\Services\RazorXClient;
use Illuminate\Support\Facades\Artisan;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Mail\Merchant\PartnerActivationRejection;
use RZP\Mail\Merchant\PartnerActivationConfirmation;
use RZP\Mail\Merchant\PartnerNeedsClarificationEmail;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;

class PartnerActivationTest extends OAuthTestCase
{
    use PartnerTrait;
    use BatchTestTrait;
    use HeimdallTrait;
    use WorkflowTrait;

    const MERCHANT_ID = '1cXSLlUU8V9sXl';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerActivationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->createWorkflowForPartnerActivation();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->ba->privateAuth();
    }

    public function testFetchPartnerActivationForNonRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testFetchPartnerActivationFromEs()
    {
        Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);
        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);

        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $testData = $this->testData['testFetchPartnerActivationForNonRegisteredBusiness'];

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'admin_fetch_merchants']);

        $roleOfAdmin->permissions()->attach($perm->getId());

        $this->startTest();
    }

    public function testSavePartnerActivationForNonRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationForRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, true, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSubmitPartnerActivationForNonRegisteredBusinessActivated()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fillStatusForRequirements(self::MERCHANT_ID, false, 'verified');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();

        $state = $this->getDbEntity('action_state');

        $this->assertEquals(self::MERCHANT_ID, $state['merchant_id']);

        $this->assertEquals('activated', $state['name']);

        $this->assertEquals('partner_activation', $state['entity_type']);
    }

    public function testSubmitPartnerActivationForNonRegisteredBusinessUnderReview()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fillStatusForRequirements(self::MERCHANT_ID, false, 'pending');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();

        $state = $this->getDbEntity('action_state');

        $this->assertEquals(self::MERCHANT_ID, $state['merchant_id']);

        $this->assertEquals('under_review', $state['name']);

        $this->assertEquals('partner_activation', $state['entity_type']);
    }

    public function testSubmitPartnerActivationWhenMerchantActivationLocked()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'under_review');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fixtures->merchant_detail->onLive()->edit(self::MERCHANT_ID, ['locked' => true]);
        $this->fixtures->merchant_detail->onTest()->edit(self::MERCHANT_ID, ['locked' => true]);

        $this->fillStatusForRequirements(self::MERCHANT_ID, false, 'pending');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationWhenMerchantActivationLocked()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'under_review');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fixtures->merchant_detail->onLive()->edit(self::MERCHANT_ID, ['locked' => true]);
        $this->fixtures->merchant_detail->onTest()->edit(self::MERCHANT_ID, ['locked' => true]);

        $this->fillStatusForRequirements(self::MERCHANT_ID, false, 'pending');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testFetchPartnerActivationForNonPartner()
    {
        $this->createMerchant(self::MERCHANT_ID, false , null, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationForNonPartner()
    {
        $this->createMerchant(self::MERCHANT_ID, false , null, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testActivatePartnerFromUnderReview()
    {
        Mail::fake();

        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData = $this->testData['testActivatePartnerFromUnderReview'];
        $testData['request']['url'] = '/partner/activation/'. self::MERCHANT_ID . '/status';
        $this->runRequestResponseFlow($testData);
        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']); // for partner_activation entity
        $this->assertEquals('partner_activation', $actionStates->get(0)['entity_type']); // for partner_activation entity
        $this->assertEquals('open', $actionStates->get(1)['name']); // for workflow_action entity
        $this->assertEquals('workflow_action', $actionStates->get(1)['entity_type']); // for workflow_action entity

        // Mail::assertQueued(PartnerActivationConfirmation::class);
    }

    public function testPartnerNeedsClarification()
    {
        Mail::fake();

        $this->updatePartnerActivationToNeedsClarification();

        $partnerActivation = $this->getDbEntity('partner_activation');
        $this->assertNotNull($partnerActivation['submitted_at']);
        $this->assertNull($partnerActivation['activated_at']);

        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']);
        $this->assertEquals('needs_clarification', $actionStates->get(1)['name']);

        Mail::assertQueued(PartnerNeedsClarificationEmail::class);
    }

    public function testPartnerNeedsClarificationResponded()
    {
        Mail::fake();

        $this->updatePartnerActivationToNeedsClarification();

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        // clearing content merc
        $testData['request']['content'] = [];
        $response = $this->runRequestResponseFlow($testData);
        $this->assertTrue($response['partner_activation']['submitted']);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($response['partner_activation']['submitted']);

        $workflowAction = $this->getDbEntity('workflow_action');
        $this->assertEquals('partner_activation', $workflowAction['entity_name']);
        $this->assertEquals(self::MERCHANT_ID, $workflowAction['entity_id']);
    }

    public function testBulkAssignReviewer()
    {
        $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('assign_partner_activation_reviewer');

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $this->startTest();
    }

    protected function addPermissionToBaAdmin(string $permissionName): void
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

    public function testPartnerRejected()
    {
        Mail::fake();

        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testUpdatePartnerActivationToRejected'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);

        $partnerActivation = $this->getDbEntity('partner_activation');
        $this->assertNotNull($partnerActivation['submitted_at']);
        $this->assertNull($partnerActivation['activated_at']);
        $this->assertTrue($partnerActivation['hold_funds']);

        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']);
        $this->assertEquals('rejected', $actionStates->get(1)['name']);

        $rejectedActionState = $actionStates->get(1);
        $reasons = $this->getDbEntities('state_reason');
        $this->assertEquals(2, count($reasons));
        $this->assertEquals($rejectedActionState->getId(), $reasons->get(0)['state_id']);
        $this->assertEquals($rejectedActionState->getId(), $reasons->get(1)['state_id']);

        Mail::assertQueued(PartnerActivationRejection::class);
    }

    public function testInvalidExtraFieldsPartnerDetailsFormSave()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testInvalidStatusChange()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testInvalidStatusChange'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);

    }

    public function testHoldCommissionsAction()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $testData = $this->testData['testFetchPartnerActivationForNonRegisteredBusiness'];
        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testHoldCommissionsAction'];
        $testData['request']['url'] = '/partner/' . self::MERCHANT_ID. '/action';
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testHoldCommissionsActionInvalidAction'];
        $testData['request']['url'] = '/partner/' . self::MERCHANT_ID. '/action';
        $this->runRequestResponseFlow($testData);
    }

    public function testReleaseCommissionsAction()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $testData = $this->testData['testFetchPartnerActivationForNonRegisteredBusiness'];
        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testHoldCommissionsAction'];
        $testData['request']['url'] = '/partner/' . self::MERCHANT_ID. '/action';
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testReleaseCommissionsAction'];
        $testData['request']['url'] = '/partner/' . self::MERCHANT_ID. '/action';
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testReleaseCommissionsActionInvalidAction'];
        $testData['request']['url'] = '/partner/' . self::MERCHANT_ID. '/action';
        $this->runRequestResponseFlow($testData);

    }

    private function updatePartnerActivationToNeedsClarification()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $response = $this->runRequestResponseFlow($testData);
        $this->assertFalse($response['partner_activation']['submitted']);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testPartnerNeedsClarification'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID;
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testUpdatePartnerActivationToNeedsClarification'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);

    }

    private function createWorkflowForPartnerActivation()
    {
        $permission = $this->getPermission();

        $workflow = $this->fixtures->create('workflow', [
            'name'   => 'Activate partner',
            'org_id' => '100000razorpay',
        ]);

        $workflow->permissions()->attach($permission);
    }

    private function getPermission()
    {
        return (new PermissionRepository)->findByOrgIdAndPermission(
            '100000razorpay', 'edit_activate_partner'
        );
    }

    private function createMerchant(string $merchantId, bool $registeredBusinessType, $activationStatus, bool $isPartner = true)
    {
        $businessType = $registeredBusinessType === true ? 4 : 1;

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchantId,
            'business_type'     => $businessType,
            'contact_name'      => 'contact name',
            'contact_mobile'    => '8888888888',
            'activation_status' => $activationStatus
        ]);

        if ($activationStatus === 'activated' or $activationStatus === 'under_review')
        {
            $this->fixtures->edit('merchant_detail', $merchantId, ['locked' => true]);
        }

        $this->fixtures->create('stakeholder',
                                [
                                    'merchant_id' => $merchantId,
                                    'name'        => 'stakeholder1',
                                ]);

        if ($isPartner === true)
        {
            $this->fixtures->edit('merchant', $merchantId, ['partner_type' => 'reseller']);
        }
    }

    private function fillAllRequirements(string $merchantId, bool $registeredBusinessType)
    {
        $payload = [
            'bank_account_name'   => 'User 1',
            'bank_account_number' => '051610000039259',
            'bank_branch_ifsc'    => 'UBIN0805165'
        ];

        if ($registeredBusinessType === true)
        {
            $promoterDetails = ['company_pan' => 'EBPCK8222K'];
            $payload         = array_merge($payload, $promoterDetails);
        }
        else
        {
            $promoterDetails = ['promoter_pan' => 'EBPPK8222K', 'promoter_pan_name' => 'User 1'];
            $payload         = array_merge($payload, $promoterDetails);
        }

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }

    private function fillPartialRequirements(string $merchantId)
    {
        $payload = [
            'bank_account_name'   => 'User 1',
            'bank_account_number' => '051610000039259',
            'bank_branch_ifsc'    => 'UBIN0805165'
        ];

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }


    private function fillStatusForRequirements(string $merchantId, bool $registeredBusinessType, string $status)
    {
        if ($registeredBusinessType === true)
        {
            $payload = [
                'company_pan_verification_status'  => $status,
                'bank_details_verification_status' => $status,
            ];
        }
        else
        {
            $payload = [
                'poi_verification_status'          => $status,
                'bank_details_verification_status' => $status,
            ];
        }

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }
}

