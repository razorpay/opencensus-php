<?php

namespace Functional\Partner\Activation;

use DB;
use Mail;
use RZP\Models\Partner;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant as Merchant;
use Illuminate\Support\Facades\Artisan;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Mail\Merchant\PartnerActivationRejection;
use RZP\Mail\Merchant\PartnerActivationConfirmation;
use RZP\Models\Merchant\Balance\Core as BalanceCore;
use RZP\Mail\Merchant\PartnerNeedsClarificationEmail;
use RZP\Mail\Merchant\PartnerWeeklyActivationSummary;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Partner\Activation\Core as ActivationCore;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;

class PartnerActivationTest extends OAuthTestCase
{
    use PartnerTrait;
    use BatchTestTrait;
    use HeimdallTrait;
    use WorkflowTrait;

    const MERCHANT_ID = '1cXSLlUU8V9sXl';

    const MERCHANT_ID_2 = '2cXSLlUU8V9sXl';

    const RZP_ORG  = '100000razorpay';

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

    public function testSavePartnerActivationForRegisteredBusinessWithoutPOA()
    {
        $this->createMerchant(self::MERCHANT_ID, true, null, true, false);

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

    /**
     * @param        $merchantId
     * @param        $documentType
     * @param string $ocrVerificationStatus
     */
    private function createMerchantDocumentEntries($merchantId, $documentType, $ocrVerificationStatus = 'verified'): void
    {
        $this->fixtures->create(
            'merchant_document',
            [
                'merchant_id'   => $merchantId,
                'document_type' => $documentType,
                'ocr_verify'    => $ocrVerificationStatus,
            ]);
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

    public function testSegmentEventPushForPartnerHavingCommissionBalance()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $this->createMerchant(self::MERCHANT_ID, false, 'needs_clarification');

        $this->fixtures->merchant->edit(self::MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $merchant = (new Merchant\Repository())->fetchMerchantFromId(self::MERCHANT_ID);

        $partnerActivation = (new ActivationCore())->createOrFetchPartnerActivationForMerchant($merchant,false);

        $balance = (new BalanceCore())->createOrFetchCommissionBalance($merchant,'test');

        $this->fixtures->base->editEntity('balance', $balance->getId(),
           [
               'balance'     => 1001,
           ]
       );

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        (new Partner\Service)->sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC();
    }

    public function testSegmentEventSkipIfPartnersHaveNoCommissionBalance()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $this->createMerchant(self::MERCHANT_ID, false, 'needs_clarification');

        $this->fixtures->merchant->edit(self::MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $merchant = (new Merchant\Repository())->fetchMerchantFromId(self::MERCHANT_ID);

        $partnerActivation = (new ActivationCore())->createOrFetchPartnerActivationForMerchant($merchant,false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        (new Partner\Service)->sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC();
    }

    public function testSegmentEventPushSkipForActivePartnerHavingCommissionBalance()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fixtures->merchant->edit(self::MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $merchant = (new Merchant\Repository())->fetchMerchantFromId(self::MERCHANT_ID);

        $partnerActivation = (new ActivationCore())->createOrFetchPartnerActivationForMerchant($merchant,true);

        $balance = (new BalanceCore())->createOrFetchCommissionBalance($merchant,'test');

        $this->fixtures->base->editEntity('balance', $balance->getId(),
            [
                'balance'     => 1000,
            ]
        );

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        (new Partner\Service)->sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC();
    }

    public function testSegmentEventSkipForPartnerHavingNegativeCommissionBalance()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $this->createMerchant(self::MERCHANT_ID, false, 'needs_clarification');

        $this->fixtures->merchant->edit(self::MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $merchant = (new Merchant\Repository())->fetchMerchantFromId(self::MERCHANT_ID);

        $partnerActivation = (new ActivationCore())->createOrFetchPartnerActivationForMerchant($merchant,false);

        $balance = (new BalanceCore())->createOrFetchCommissionBalance($merchant,'test');

        $this->fixtures->base->editEntity('balance', $balance->getId(),
            [
                'balance'     => -1001,
            ]
        );

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        (new Partner\Service)->sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC();
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

    private function createMerchant(string $merchantId, bool $registeredBusinessType, $activationStatus, bool $isPartner = true, bool $submitPOADoc = true)
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

        $this->fixtures->user->createUserMerchantMapping(
            [
                'merchant_id' => $merchantId,
                'user_id'     => User::MERCHANT_USER_ID,
                'role'        => 'owner',
            ]);

        if($submitPOADoc === true)
        {
            $this->createMerchantDocumentEntries(self::MERCHANT_ID, 'aadhar_front');

            $this->createMerchantDocumentEntries(self::MERCHANT_ID, 'aadhar_back');
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
            $promoterDetails = [
                'company_pan'                    => 'EBPCK8222K',
                'business_registered_address'    => '507, Koramangala 1st block',
                'business_registered_pin'        => '560034',
                'business_registered_city'       => 'Bengaluru',
                'business_registered_state'      => 'KA'
            ];
            $payload         = array_merge($payload, $promoterDetails);
        }
        else
        {
            $promoterDetails = [
                'promoter_pan'                  => 'EBPPK8222K',
                'promoter_pan_name'             => 'User 1',
                'business_operation_address'    => '507, Koramangala 1st block',
                'business_operation_pin'        => '560034',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_state'      => 'KA'
            ];
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
                'poa_verification_status'          => $status,
            ];
        }
        else
        {
            $payload = [
                'poi_verification_status'          => $status,
                'bank_details_verification_status' => $status,
                'poa_verification_status'          => $status,
            ];
        }

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function createDummyMerchantsForWeeklyActivationSummary($partnerMerchant)
    {
        $merchantIds          = [];
        $activationStatusRows = [];
        $merchantsData        = $this->testData[__FUNCTION__]['merchantsData'];
        $expectedFilteredIds  = $this->testData[__FUNCTION__]['expectedFilteredIds'];

        foreach ($merchantsData as $merchantId => $data)
        {
            $this->fixtures->create('merchant', $data['merchant']);
            $this->fixtures->merchant_detail->createEntityInTestAndLive('merchant_detail', $data['merchant_detail']);
            $this->fixtures->user->createUserForMerchant($merchantId);
            $this->fixtures->merchant_access_map->createEntityInTestAndLive('merchant_access_map', [
                'entity_type'     => 'application',
                'merchant_id'     => $merchantId,
                'entity_owner_id' => $partnerMerchant->getId(),
            ]);

            if (empty($data['action_state']) === false)
            {
                $this->fixtures->create('action_state', $data['action_state']);
            }

            if (in_array($merchantId, $expectedFilteredIds))
            {
                $clarificationReasons              = $data['expected_clarification_reasons'] ?? [];
                $activationStatus = $data['merchant_detail']['activation_status'];
                $activationStatusRows[$merchantId] = [
                    'merchant_id'             => $merchantId,
                    'merchant_name'           => $data['merchant']['name'],
                    'activation_status'       => $activationStatus,
                    'activation_status_label' => PartnerConstants::$subMActivationStatusLabels[$activationStatus],
                    'clarification_reasons'   => $clarificationReasons
                ];
            }

            $merchantIds[] = $merchantId;
        }

        $expectedData = [
            'countKYCNotInitiatedInTwoMonths' => 1,
            'isMerchantCountCapped'           => false,
            'activationStatusRows'            => $activationStatusRows,
            'partner_email'                   => $partnerMerchant->getEmail()
        ];

        return [$expectedFilteredIds, $expectedData];
    }

    public function testSendPartnerWeeklyActivationSummaryEmails()
    {
        Mail::fake();

        $partnerCore = new PartnerCore();

        list($partnerMerchant, $_app) = $this->createPartnerAndApplication(['partner_type' => 'aggregator', 'email' => 'test1@razorpay.com']);

        list($expectedFilteredIds, $expectedData) = $this->createDummyMerchantsForWeeklyActivationSummary($partnerMerchant);

        $merchantCountCap = 10;
        // Test that submerchants were correctly filtered.
        $filteredMerchantIds = $partnerCore->getSubmerchantIdsForWeeklyActivationSummaryEmail($partnerMerchant->getId(), $merchantCountCap);
        sort($filteredMerchantIds);
        $this->assertEquals($expectedFilteredIds, $filteredMerchantIds);

        // Test that correct payload data is created
        $data = $partnerCore->getPayloadForPartnerWeeklyActivationSummaryEmail($partnerMerchant, $filteredMerchantIds, $merchantCountCap);
        $this->assertEquals($expectedData, $data);

        // Test that email was queued and received correct data
        $partnerCore->sendPartnerWeeklyActivationSummaryEmails($partnerMerchant->getId());
        Mail::assertQueued(PartnerWeeklyActivationSummary::class);
    }
}

