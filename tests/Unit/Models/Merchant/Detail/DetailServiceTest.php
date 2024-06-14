<?php

namespace Unit\Models\Merchant\Detail;

use Mockery;

use Carbon\Carbon;
use RZP\Models\Feature;
use Tests\Unit\TestCase;
use RZP\Models\Bank\IFSC;
use RZP\Models\Admin\Permission\Name;
use RZP\Exception\BadRequestException;
use RZP\Exception\EarlyWorkflowResponse;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Merchant\Detail\Service as MerchantService;
use RZP\Models\Merchant\Detail\Constants as DC;
use RZP\Models\Merchant\Detail\Entity;

class DetailServiceTest extends TestCase
{

    protected $merchantService;
    protected $coreMock;
    protected $merchantEntityMock;
    protected $userEntityMock;
    protected $userDeviceDetailRepositoryMock;
    protected $userDeviceDetailEntityMock;
    protected $deviceEntityMock;
    protected $merchantDetailEntityMock;
    protected $merchantBusinessDetailEntityMock;
    protected $partnerActivationMock;
    protected $stakeholderEntityMock;
    protected $merchantRepoMock;
    protected $merchantAttributeRepoMock;
    protected $m2mReferralRepoMock;
    protected $merchantMethodsMock;
    protected $merchantDocumentCoreMock;
    protected $merchantDetailValidator;
    protected $merchantAccountCore;
    protected $adminEntityMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\RZP\Models\Merchant\Detail\Repository
     */
    private $merchantDetailRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDependencyMocks();
        $this->merchantService = new MerchantService($this->coreMock, $this->merchantDetailValidator, $this->merchantAccountCore);
    }

    public function testFetchMerchantAndServiceDetails()
    {
        $this->markTestSkipped("Skipping the test until 25-01-2021: manual testing done");
        $this->getMerchantEditMocks();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);
        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $response = $this->merchantService->fetchMerchantDetails();

        $verification = $response['verification'];

        $this->assertEquals('disabled', $verification['status']);

        $this->assertEquals(false, $response['live']);

        $this->assertEquals(false, $response['international']);
    }

    public function testGetDisabledBanksMoreInfo()
    {
        $methodRepoMock = Mockery::mock('RZP\Models\Merchant\Methods\Repository');

        $this->repoMock->shouldReceive('driver')->with('methods')->andReturn($methodRepoMock);

        $methodEntityMock = Mockery::mock('RZP\Models\Merchant\Methods\Entity');

        $methodRepoMock->shouldReceive('getMethodsForMerchant')->andReturn($methodEntityMock);

        $disabledBanks = [IFSC::IOBA, IFSC::JAKA, IFSC::KKBK, IFSC::MAHB];

        $enabledBanks = [IFSC::UBIN, IFSC::UTIB, IFSC::YESB];

        $methodEntityMock->shouldReceive('getDisabledBanks')->andReturn($disabledBanks);

        $methodEntityMock->shouldReceive('getEnabledBanks')->andReturn($enabledBanks);

        $response = $this->merchantService->getDisabledBanks();

        $this->assertEquals('Bank of Maharashtra', $response['MAHB']);
    }

    public function testFetchActivationFiles()
    {
        $this->getAdminAndPublicAuthMock();

        $this->getInternalAppMock();

        $this->getDriverAsMerchantMock();

        $this->getFindOrFailPublic();

        $this->getMerchantDetailAttributeMock();

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('offsetExists')->andReturn(true);

        $this->merchantDetailEntityMock->shouldReceive('offsetGet')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $response = $this->merchantService->fetchActivationFiles("100002Razorpay");

        $filesArray = $response['files'];

        $this->assertEquals("paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf", $filesArray['business_proof']);
    }

    public function testSaveMerchantDetailForPreSignUp()
    {
        $this->markTestSkipped('The test case needs mocking of lot of functions. This needs to be restructured');
        $merchantData = [
        'merchant_id'           => '1cXSLlUU8V9sXl',
        'product'               => 'banking',
        'role'                  => 'manager',
        'action'                => 'edit'];

        $this->getDriverAsMerchantMock();

        $this->repoMock->shouldReceive('driver')->with('merchant_business_detail')->andReturn($this->merchantBusinessDetailEntityMock);
        $this->userDeviceDetailRepositoryMock->shouldReceive('fetchByMerchantIdAndUserRole')->withAnyArgs()->andReturn($this->userDeviceDetailEntityMock);
        $this->repoMock->shouldReceive('driver')->with('user_device_detail')->andReturn($this->userDeviceDetailRepositoryMock);
        $this->userDeviceDetailEntityMock->shouldReceive('getSignupCampaign')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');
        $this->merchantDetailEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');
        $this->merchantDetailValidator->shouldReceive('validatePartnerActivationStatus')->andReturn();

        $this->getFindOrFailPublic();

        $this->createMerchantTestDependencyMocks();

        $this->repoMock->shouldReceive('transactionOnLiveAndTestAndAsv')->andReturn([]);

        $this->coreMock->shouldReceive('setModeAndDefaultConnection')->andReturn();

        $this->merchantEntityMock->shouldReceive('getEntity')->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('isNoDocOnboardingEnabled')->andReturn(false);
        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantBusinessDetailEntityMock->shouldReceive('setBlacklistedProductsCategory');

        $this->merchantBusinessDetailEntityMock->shouldReceive('getBusinessDetailsForMerchantId')->andReturn();;

        $this->merchantBusinessDetailEntityMock->shouldReceive('getBlacklistedProductsCategory');

        $this->merchantEntityMock->shouldReceive('isSignupCampaignAnyOf')->andReturn(false);

        $this->merchantDetailValidator->shouldReceive('validateBusinessSubcategoryForCategoryForEasyOnboarding');

        $this->merchantDetailEntityMock->shouldReceive('getBankDetailsVerificationStatus')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getBankAccountNumber')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getBankBranchIfsc')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('id')->andReturn('1cXSLlUU8V9sXl');
        $this->merchantEntityMock->shouldReceive('getAttribute')->with('id')->andReturn('1cXSLlUU8V9sXl');


        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepositoryMock);
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('isLocked')->andReturn();
        $this->merchantEntityMock->shouldReceive('isRouteNoDocKycEnabledForParentMerchant')->andReturn(false);
        $this->merchantDetailRepositoryMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->merchantDetailEntityMock);
        $this->merchantEntityMock->shouldReceive('getOrgId')->withAnyArgs()->andReturn();
        $this->repoMock->shouldReceive('driver')->with('merchant_attribute')->andReturn($this->merchantAttributeRepoMock);
        $this->merchantAttributeRepoMock->shouldReceive('getKeyValues')->andReturn([]);
        $this->merchantBusinessDetailEntityMock->shouldReceive('getWebsiteDetails')->withAnyArgs()->andReturn(null);

        $response = $this->merchantService->saveMerchantDetailForPreSignUp($merchantData);

        $this->assertEquals([], $response);
    }

    public function testSaveMerchantDetailsForActivation()
    {
        $this->markTestSkipped('The test case needs mocking of lot of functions. This needs to be restructured');
        $merchantData = [
            'merchant_id'           => '1cXSLlUU8V9sXl',
            'product'               => 'banking',
            'role'                  => 'manager',
            'action'                => 'edit'];

        $this->getDriverAsMerchantMock();

        $this->repoMock->shouldReceive('driver')->with('merchant_business_detail')->andReturn($this->merchantBusinessDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantDetailValidator->shouldReceive('validatePartnerActivationStatus')->andReturn();

        $this->getFindOrFailPublic();

        $this->getMerchantIdMock();

        $this->createMerchantTestDependencyMocks();

        $this->repoMock->shouldReceive('transactionOnLiveAndTestAndAsv')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('isNoDocOnboardingEnabled')->andReturn(false);

        $this->merchantBusinessDetailEntityMock->shouldReceive('setBlacklistedProductsCategory');

        $this->merchantBusinessDetailEntityMock->shouldReceive('getBlacklistedProductsCategory');

        $this->merchantBusinessDetailEntityMock->shouldReceive('getBusinessDetailsForMerchantId')->andReturn();

        $this->merchantEntityMock->shouldReceive('isSignupCampaignAnyOf')->andReturn(false);

        $this->merchantDetailValidator->shouldReceive('validateBusinessSubcategoryForCategoryForEasyOnboarding');

        $this->merchantDetailEntityMock->shouldReceive('getBankDetailsVerificationStatus')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getBankAccountNumber')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getBankBranchIfsc')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->andReturn();
        $this->merchantDetailEntityMock->shouldReceive('isLocked')->andReturn();
        $this->merchantEntityMock->shouldReceive('getCountry')->andReturn('IN');
        $this->merchantEntityMock->shouldReceive('isSignupViaEmail')->andReturn(false);
        $this->merchantEntityMock->shouldReceive('isRouteNoDocKycEnabledForParentMerchant')->andReturn(false);
        $this->merchantRepoMock->shouldReceive('findOrFail')->withAnyArgs()->andReturn($this->merchantEntityMock);
        $this->merchantDetailRepositoryMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->merchantDetailEntityMock);
        $this->merchantEntityMock->shouldReceive('getOrgId')->withAnyArgs()->andReturn();
        $this->merchantBusinessDetailEntityMock->shouldReceive('getWebsiteDetails')->withAnyArgs()->andReturn(null);
        $this->userDeviceDetailRepositoryMock->shouldReceive('fetchByMerchantIdAndUserRole')->withAnyArgs()->andReturn($this->userDeviceDetailEntityMock);
        $this->userDeviceDetailEntityMock->shouldReceive('getValueFromMetadata')->withAnyArgs()->andReturn('api');
        $this->userDeviceDetailEntityMock->shouldReceive('getSignupCampaign')->withAnyArgs()->andReturn();

        $org = Mockery::mock('\RZP\Models\Admin\Org\Entity');

        $this->merchantEntityMock->shouldReceive('getAttribute')->withArgs(['org'])->andReturn($org);
        $org->shouldReceive('isFeatureEnabled')->withArgs([Feature\Constants::ORG_PROGRAM_DS_CHECK])->andReturn(false);
        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepositoryMock);
        $this->repoMock->shouldReceive('driver')->with('user_device_detail')->andReturn($this->userDeviceDetailRepositoryMock);

        $actualResponse = $this->merchantService->saveMerchantDetailsForActivation($merchantData);
        $expectedResponse = [
            'lock_common_fields' => [
                'contact_name',
                'contact_mobile',
                'contact_email',
                'business_type',
                'bank_account_name',
                'bank_account_number',
                'bank_branch_ifsc',
                'promoter_pan',
                'promoter_pan_name'
            ]
        ];

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testSaveInstantActivationDetails()
    {
        $input = [
            'business_operation_state' => 'ANDAMAN & NICOBAR ISLANDS',
            'business_registered_state' => 'CHANDIGARH',
        ];

        $this->getDriverAsMerchantMock();

        $this->getFindOrFailPublic();

        $this->getMerchantIdMock();

        $this->getMerchantEmailMock();

        $this->getOrgId();

        $this->isLinkedAccount();

        $this->createMerchantTestDependencyMocks();

        $response = [
            'need_kyc' => 1,
            'linked_account' => true,
            'marketplace_merchant_name' => 'dummy',
            'marketplace_merchant_id' => '1cXSLlUU8V9sXl',
            'verification' => [
                'status'              => 'disabled',
                'disabled_reason'     => 'required_fields',
                'required_fields'     => '',
                'optional_fields'     => '',
                'activation_progress' => 50,
            ]
        ];

        $this->merchantEntityMock->shouldReceive('isNoDocOnboardingEnabled')->andReturn(false);

        $this->repoMock->shouldReceive('transactionOnLiveAndTestAndAsv')->andReturn($response);

        $this->merchantEntityMock->shouldReceive('toArrayEvent')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('isSignupCampaign')->andReturn(false);

        $this->merchantDetailValidator->shouldReceive('validatePartnerActivationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getPoiVerificationStatus')->andReturn();

        $harvesterMock = Mockery::mock('RZP\Services\Harvester\HarvesterClient');

        $harvesterMock->shouldReceive('trackEvents')->andReturn([]);

        $this->app->instance('eventManager', $harvesterMock);

        $this->merchantDetailEntityMock->shouldReceive('getActivationFlow')->andReturn('whitelist');

        $this->merchantEntityMock->shouldReceive('isRouteNoDocKycEnabledForParentMerchant')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->andReturn();

        $response = $this->merchantService->saveInstantActivationDetails($input);

        $this->assertEquals(false, $response['auto_activated']);

    }

    public function testPatchMerchantDetails()
    {
        $merchantData = [
            'merchant_id'           => '1cXSLlUU8V9sXl',
            'product'               => 'banking',
            'role'                  => 'manager',
            'action'                => 'edit'];

        $this->getMerchantDetailAttributeMock();

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getActivated')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('isLive')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn(Carbon::now()->getTimestamp());

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->getFindOrFailPublic();

        $this->merchantDetailEntityMock->shouldReceive('getValidator')->andReturn($this->merchantDetailValidator);

        $this->merchantDetailValidator->shouldReceive('validateBusinessSubcategoryForCategory')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('edit')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessCategory')->andReturn(1);

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('under_review');

        $this->merchantDetailEntityMock->shouldReceive('getBusinessSubcategory')->andReturn(0);

        $this->merchantEntityMock->shouldReceive('getCategory')->andReturn(2);

        $this->merchantEntityMock->shouldReceive('getCountry')->andReturn('IN');

        $this->merchantEntityMock->shouldReceive('getCategory2')->andReturn(2);

        $this->merchantEntityMock->shouldReceive('isSignupCampaignAnyOf')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('isDirty')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $this->merchantEntityMock->shouldReceive('isSignupViaEmail')->andReturn(false);
        $this->merchantRepoMock->shouldReceive('findOrFail')->withAnyArgs()->andReturn($this->merchantEntityMock);
        $this->userDeviceDetailRepositoryMock->shouldReceive('fetchByMerchantIdAndUserRole')->withAnyArgs()->andReturn($this->userDeviceDetailEntityMock);
        $this->userDeviceDetailEntityMock->shouldReceive('getValueFromMetadata')->withAnyArgs()->andReturn('api');
        $this->repoMock->shouldReceive('driver')->with('user_device_detail')->andReturn($this->userDeviceDetailRepositoryMock);

        $response = $this->merchantService->patchMerchantDetails($merchantData);

        $this->assertEquals([], $response);
    }

    public function testUploadActivationFileAdmin()
    {
        $merchantData = [];

        $this->getDriverAsMerchantMock();

        $this->getFindOrFailPublic();

        $returnResp = [
            'need_kyc' => 1,
            'linked_account' => true,
            'marketplace_merchant_name' => 'dummy',
            'marketplace_merchant_id' => '1cXSLlUU8V9sXl',
            'verification' => [
                'status'              => 'pending',
                'disabled_reason'     => 'required_fields',
                'required_fields'     => '',
                'optional_fields'     => '',
                'activation_progress' => 50,
            ],
            'can_submit' => false,
            'activated' => 1,
            'live' => false,
            'international' => true
        ];

        $this->getUploadActivationFileMocks($returnResp);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $response = $this->merchantService->uploadActivationFileAdmin('1cXSLlUU8V9sXl', $merchantData);

        $verification = $response['verification'];

        $this->assertEquals('pending', $verification['status']);

        $this->assertEquals(false, $response['live']);

        $this->assertEquals(true, $response['international']);
    }

    public function testUploadActivationFileMerchant()
    {
        $merchantData = [];

        $returnResp = [
            'linked_account' => true,
            'marketplace_merchant_name' => 'dummy',
            'marketplace_merchant_id' => '1cXSLlUU8V9sXl',
            'can_submit' => false,
            'activated' => 1,
        ];

        $this->getUploadActivationFileMocks($returnResp);

        $this->merchantDetailEntityMock->shouldReceive('getValidator')->andReturn($this->merchantDetailValidator);

        $this->merchantDetailValidator->shouldReceive('validateIsNotLocked')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $response = $this->merchantService->uploadActivationFileMerchant($merchantData);

        $this->assertEquals(true, $response['linked_account']);

        $this->assertEquals('1cXSLlUU8V9sXl', $response['marketplace_merchant_id']);

        $this->assertEquals(1, $response['activated']);
    }

    public function testEditMerchantDetails()
    {
        $this->markTestSkipped("Skipping the test until 25-01-2021: manual testing done");
        $input = [];

        $this->getMerchantEditMocks();

        $this->getFindOrFailPublic();

        $this->getDriverAsMerchantMock();

        $this->merchantDetailEntityMock->shouldReceive('edit')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepositoryMock);

        $this->merchantDetailRepositoryMock->shouldReceive('findByPublicId')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getKycClarificationReasons')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessCategory')->andReturn(1);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessSubcategory')->andReturn(0);

        $this->merchantEntityMock->shouldReceive('getCategory')->andReturn(2);

        $this->merchantEntityMock->shouldReceive('getCategory2')->andReturn(2);

        $this->merchantDetailEntityMock->shouldReceive('isDirty')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessName')->andReturn('dummy-business');

        $this->merchantEntityMock->shouldReceive('getDbaName')->andReturn('dummy-dba');

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getLegalEntityId')->andReturn();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDocuments')->andReturn($this->merchantDetailEntityMock);

        $response = $this->merchantService->editMerchantDetails('100002Razorpay', $input);

        $verification = $response['verification'];

        $this->assertEquals('disabled', $verification['status']);

        $this->assertEquals(false, $response['live']);
    }

    public function testEditMerchantDetailsByPartnerFeature()
    {
        $merchantData = [
            'merchant_id'           => '1cXSLlUU8V9sXa',
            'product'               => 'banking',
            'role'                  => 'manager',
            'action'                => 'edit'];

        $this->merchantEntityMock->shouldReceive('getAccountCore')->andReturn($this->merchantAccountCore);

        $this->merchantAccountCore->shouldReceive('validatePartnerAccess')->andReturn();

        $this->getDriverAsMerchantMock();

        $this->getMerchantDetailAttributeMock();

        $this->getFindOrFailPublic();

        $this->merchantDetailEntityMock->shouldReceive('fill')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('edit')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepositoryMock);

        $this->merchantDetailRepositoryMock->shouldReceive('findByPublicId')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getKycClarificationReasons')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessCategory')->andReturn(1);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessSubcategory')->andReturn(0);

        $this->merchantEntityMock->shouldReceive('getCategory')->andReturn(2);

        $this->merchantEntityMock->shouldReceive('getCategory2')->andReturn(2);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1232');

        $this->merchantEntityMock->shouldReceive('isSignupCampaignAnyOf')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('isDirty')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessName')->andReturn('dummy-business');

        $this->merchantEntityMock->shouldReceive('getDbaName')->andReturn('dummy-dba');

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);
        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepositoryMock);

        $this->merchantDetailRepositoryMock->shouldReceive('findByPublicId')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_type')->andReturn(11);
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_category')->andReturn('ecommerce');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_subcategory')->andReturn('agriculture');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_dba')->andReturn('something');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_name')->andReturn('something');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_website')->andReturn('www.abc.com');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_doe')->andReturn('something');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_model')->andReturn('asdasd');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_operation_state')->andReturn('maharashtra');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_operation_city')->andReturn('mumbai');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_operation_pin')->andReturn(400076);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_registered_state')->andReturn('maharashtra');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_registered_city')->andReturn('mumbai');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('business_registered_pin')->andReturn(400076);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('promoter_pan')->andReturn('AZVGG7840O');
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('promoter_pan_name')->andReturn('something');

        $response = $this->merchantService->editMerchantDetailsByPartner('acc_1cXSLlUU8V9sXa', $merchantData);

        $this->assertEquals([], $response);
    }

    public function testUpdateActivationArchive()
    {
        $merchantData = [
            'merchant_id'           => '1cXSLlUU8V9sXl',
            'product'               => 'banking',
            'role'                  => 'manager',
            'action'                => 'edit'];

        $this->getDriverAsMerchantMock();

        $this->getFindOrFailPublic();

        $this->getMerchantDetailAttributeMock();

        $this->app->instance('basicauth', $this->basicAuthMock);

        $this->basicAuthMock->shouldReceive('getAdmin')->andReturn($this->adminEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getValidator')->andReturn($this->merchantDetailValidator);

        $this->merchantDetailValidator->shouldReceive('validateInput')->andReturn();

        $this->adminEntityMock->shouldReceive('hasMerchantActionPermissionOrFail')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setArchivedAt')->andReturn();

        $workflowService = Mockery::mock('RZP\Services\Workflow\Service');

        $workflowVpaEntity = Mockery::mock('RZP\Models\P2p\Vpa\Entity');

        $workflowVpaEntity->shouldReceive('setPermission')->andReturn($workflowService);

        $workflowService->shouldReceive('handle')->andReturn();

        $this->app->instance('workflow', $workflowVpaEntity);

        $this->getMerchantAttributeMock();

        $this->coreMock->shouldReceive('logActionToSlack')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $response = $this->merchantService->updateActivationArchive('1cXSLlUU8V9sXl', $merchantData);

        $this->assertEquals([], $response);
    }

    public function getDriverAsMerchantMock()
    {
        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('m2m_referral')->andReturn($this->m2mReferralRepoMock);
    }

    public function getAdminAndPublicAuthMock()
    {
        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);
    }

    public function getInternalAppMock()
    {
        $this->basicAuthMock->shouldReceive('isInternalApp')->andReturn(false);;
    }

    public function getMerchantDetailAttributeMock()
    {
        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);
    }

    public function getMerchantAttributeMock()
    {
        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('merchant')->andReturn($this->merchantEntityMock);
    }

    public function getFindOrFailPublic()
    {
        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->merchantEntityMock);
    }

    public function getMerchantIdMock()
    {
        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');
    }

    public function getMerchantEmailMock()
    {
        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('wotever@gmail.com');
    }

    public function getOrgId()
    {
        $this->merchantEntityMock->shouldReceive('getOrgId')->andReturn('100000razorpay');
    }

    public function isLinkedAccount()
    {
        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(true);
    }

    public function getMerchantEditMocks()
    {
        $this->getAdminAndPublicAuthMock();

        $this->getMerchantDetailAttributeMock();

        $this->merchantDetailEntityMock->shouldReceive('load')->andReturn();

        $this->getMerchantAttributeMock();

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('currentActivationState')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('toArray')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessCategory')->andReturn(1);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessType')->andReturn(3);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessSubcategory')->andReturn(1);

        $this->basicAuthMock->shouldReceive('isBatchFlow')->andReturn(false);

        $this->coreMock->shouldReceive('documentCore')->andReturn($this->merchantDocumentCoreMock);

        $this->merchantDocumentCoreMock->shouldReceive('documentResponse')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('getBusinessRegisteredCity')->andReturn("GURUGRAM");

        $this->merchantDetailEntityMock->shouldReceive('getBusinessRegisteredState')->andReturn("UP");

        $this->merchantDetailEntityMock->shouldReceive('getBusinessTypeValue')->andReturn(4);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('poi_verification_status')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('company_pan_verification_status')->andReturn();

        $this->merchantEntityMock->shouldReceive('isActivated')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('isLive')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('isInternational')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $balanceRepoMock = Mockery::mock('RZP\Models\Merchant\Balance\Repository');

        $this->repoMock->shouldReceive('driver')->with('balance')->andReturn($balanceRepoMock);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $balanceEntityMock = Mockery::mock('RZP\Models\Merchant\Balance\Entity');

        $balanceRepoMock->shouldReceive('getMerchantBalanceByTypeAndAccountType')->andReturn($balanceEntityMock);

        $bankingAccountRepoMock = Mockery::mock('RZP\Models\BankingAccount\Repository');

        $this->repoMock->shouldReceive('driver')->with('banking_account')->andReturn($bankingAccountRepoMock);

        $bankingAccountEntityMock = Mockery::mock('RZP\Models\BankingAccount\Entity')->makePartial();

        $bankingAccountRepoMock->shouldReceive('getFromBalanceId')->andReturn($bankingAccountEntityMock);

        $balanceEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->basicAuthMock->shouldReceive('isStrictPrivateAuth')->andReturn(true);

        $this->basicAuthMock->shouldReceive('isProxyAuth')->andReturn(false);

        $creditBalanceRepoMock = Mockery::mock('RZP\Models\Merchant\Credits\Balance\Repository');

        $this->repoMock->shouldReceive('driver')->with('credits')->andReturn($creditBalanceRepoMock);

        $creditBalanceRepoMock->shouldReceive('getTypeAggregatedMerchantCreditsForProductForDashboard')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);
    }

    public function createMerchantTestDependencyMocks()
    {
        $this->basicAuthMock->shouldReceive('getLiveConnection')->andReturn('test');

        $this->basicAuthMock->shouldReceive('setModeAndDbConnection')->andReturn('test');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->getMerchantDetailAttributeMock();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('stakeholder')->andReturn($this->stakeholderEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('businessDetail')->andReturn($this->merchantBusinessDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('partnerActivation')->andReturn($this->partnerActivationMock);

        $this->merchantEntityMock->shouldReceive('load')->andReturn();

        $this->partnerActivationMock->shouldReceive('getEntityName')->with()->andReturn('partner_activation');

        $this->merchantDetailEntityMock->shouldReceive('getBusinessType')->with()->andReturn('not_yet_registered');

        $this->partnerActivationMock->shouldReceive('isLocked')->andReturn(true);

        $this->partnerActivationMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getValidator')->andReturn($this->merchantDetailValidator);

        $this->merchantDetailValidator->shouldReceive('validateIsNotLocked')->andReturn();

        $this->merchantDetailValidator->shouldReceive('blockInstantActivationCriticalFields')->andReturn();

        $this->merchantDetailValidator->shouldReceive('validateCommonFieldsWithPartnerActivation')->andReturn();

        $this->merchantDetailValidator->shouldReceive('validateBankBranchCode')->andReturn();

        $this->merchantDetailValidator->shouldReceive('performInstantActivationValidations')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('edit')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isRazorpayOrgId')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('setPoiVerificationStatus')->andReturn();
        $this->stakeholderEntityMock->shouldReceive('setPoiStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getBusinessTypeValue')->andReturn(0);

        $this->merchantDetailEntityMock->shouldReceive('setCompanyPanVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setGstinVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setShopEstbVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setCinVerificationStatus')->andReturn();
    }

    public function createTestDependencyMocks()
    {
        // User Entity Mocking
        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity')->makePartial()->shouldAllowMockingProtectedMethods();

        // Merchant Mocking
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        // Merchant Repo mocking
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        // Merchant attribute Repo mocking
        $this->merchantAttributeRepoMock  = Mockery::mock("RZP\Models\Merchant\Attribute\Repository");

        // Merchant Attribute Repo mocking
        $this->merchant = Mockery::mock('RZP\Models\Merchant\Attribute\Repository');

        // Merchant Mocking
        $this->merchantDetailEntityMock = Mockery::mock('RZP\Models\Merchant\Detail\Entity');

        // Merchant Business details Mocking
        $this->merchantBusinessDetailEntityMock = Mockery::mock('RZP\Models\Merchant\BusinessDetail\Entity');
        // M2M Referrals Repo mocking
        $this->merchantDetailRepositoryMock = Mockery::mock('RZP\Models\Merchant\Detail\Repository');

        $this->partnerActivationMock = Mockery::mock('RZP\Models\Partner\Activation\Entity');

        // Stakeholder Mocking
        $this->stakeholderEntityMock = Mockery::mock('RZP\Models\Merchant\Stakeholder\Entity');

        // Merchant Mocking
        $this->merchantDocumentCoreMock = Mockery::mock('RZP\Models\Merchant\Document\Core');

        // Device Entity Mocking
        $this->deviceEntityMock = Mockery::mock('RZP\Models\Device\Entity');

        // User Device Detail Repository Mocking
        $this->userDeviceDetailRepositoryMock = Mockery::mock('RZP\Models\DeviceDetail\Repository');

        // User Device Detail Entity Mocking
        $this->userDeviceDetailEntityMock = Mockery::mock('RZP\Models\DeviceDetail\Entity');

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);

        $this->basicAuthMock->shouldReceive('getRequestOriginProduct')->andReturn('banking');

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getOrgId')->andReturn('org_1000000razorpay');

        $this->basicAuthMock->shouldReceive('getOrgHostName')->andReturn('Razorpay');

        $this->basicAuthMock->shouldReceive('getMode')->andReturn('test');

        $this->basicAuthMock->shouldReceive('getProduct')->andReturn('banking');

        $this->basicAuthMock->shouldReceive('getMerchantId')->andReturn('10000000000');

        $this->basicAuthMock->shouldReceive('getDevice')->andReturn($this->deviceEntityMock);

        // Core Mocking Partial
        $this->coreMock = Mockery::mock('RZP\Models\Merchant\Detail\Core', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();;

        $this->merchantMethodsMock = Mockery::mock('RZP\Models\Merchant\Methods\Core');

        $this->repoMock->shouldReceive('saveOrFail')->andReturn([]);

        // merchant detail Validator mocking
        $this->merchantDetailValidator = Mockery::mock('RZP\Models\Merchant\Detail\Validator');

        $this->diagClientMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->hubspotMock->shouldReceive('trackL1ContactProperties')->andReturn([]);

        $this->hubspotMock->shouldReceive('trackL2ContactProperties')->andReturn([]);

        $this->hubspotMock->shouldReceive('trackPreSignupEvent')->andReturn([]);

        $this->merchantAccountCore = Mockery::mock('RZP\Models\Merchant\Account\Core');

        $this->adminEntityMock = Mockery::mock('RZP\Models\Admin\Admin\Entity')->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function getUploadActivationFileMocks(array $returnResp)
    {
        $this->merchantDetailValidator->shouldReceive('validateDocumentUpload')->andReturn();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->getMerchantAttributeMock();

        $this->repoMock->shouldReceive('transactionOnLiveAndTestAndAsv')->andReturn($returnResp);
    }

    public function testMidBelongsToMswipe()
    {
        $testcases = $this->createTestforMidBelongsToMswipe();

        $this->getDriverAsMerchantMock();

        $this->getFindOrFailPublic();

        $this->merchantEntityMock->shouldReceive('toArrayEvent')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        /*
         * to return different values on every subsequent call to the method, we have to provide the sequence of return values
         * $mock->shouldReceive('name_of_method')->andReturn($value1, $value2, ...)
         * The first call to 'name_of_method' will return $value1 and the second call will return $value2.
         * https://docs.mockery.io/en/latest/reference/expectations.html
         */
        $this->merchantDetailEntityMock->shouldReceive('getWebsite')->andReturn($testcases[0]['business_website'], $testcases[1]['business_website'],
                                                                                $testcases[2]['business_website'], $testcases[3]['business_website']
        );

        /*
         * for additional_websites only 2nd and 3rd testcase are used because
         * 'getAdditionalWebsites' will not be called in 0th and 1st testcase (i.e when 'business_website' already have "mswipe.com")
         */
        $this->merchantDetailEntityMock->shouldReceive('getAdditionalWebsites')->andReturn($testcases[2]['additional_websites'],
                                                                                           $testcases[3]['additional_websites']);

        foreach ($testcases as $testcase)
        {
            $output = $this->merchantService->isMidBelongsToMswipe("12234546");

            $expected_output = $testcase['business_website_has_mswipe.com'] || $testcase['additional_websites_have_mswipe.com'];

            self::assertEquals($expected_output, $output);
        }
    }

    public function testPostCommentsInWorkflowSuccess()
    {
        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $input = [
            DC::INPUT => [DC::API_VERSION => DC::WEBSITE_VERSION_V2],
            DC::PERMISSION => Name::UPDATE_MERCHANT_WEBSITE,
            DC::COMMENTS => [
                [
                    DC::IDENTIFIER  => DC::WEBSITE_UPDATE,
                    DC::ENTITY      => "merchant_details",
                    DC::ENTITY_ID   => "10000000000000",
                    DC::PERMISSION  => Name::UPDATE_MERCHANT_WEBSITE,
                    DC::COMMENT     => "test comment",
                ]
            ],
        ];

        $this->coreMock->shouldReceive('postCommentsInWorkflow')->andReturn();

        $result = $this->callProtectedMethod($this->merchantService, 'postCommentsInWorkflow', [$input]);

        $this->assertTrue($result[0][DC::SUCCESS]);
    }

    public function testPostCommentsInWorkflowCommentingError()
    {
        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $input = [
            DC::INPUT => [DC::API_VERSION => DC::WEBSITE_VERSION_V2],
            DC::PERMISSION => Name::UPDATE_MERCHANT_WEBSITE,
            DC::COMMENTS => [
                [
                    DC::IDENTIFIER  => DC::WEBSITE_UPDATE,
                    DC::ENTITY      => "merchant_details",
                    DC::ENTITY_ID   => "10000000000000",
                    DC::PERMISSION  => Name::UPDATE_MERCHANT_WEBSITE,
                    DC::COMMENT     => "test comment",
                ]
            ],
        ];

        $this->coreMock->shouldReceive('postCommentsInWorkflow')->andThrow(new \Exception());

        $result = $this->callProtectedMethod($this->merchantService, 'postCommentsInWorkflow', [$input]);

        $this->assertFalse($result[0][DC::SUCCESS]);
    }

    public function testPostCommentsInWorkflowEmptyComments()
    {
        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $input = [
            DC::INPUT => [DC::API_VERSION => DC::WEBSITE_VERSION_V2],
            DC::PERMISSION => Name::UPDATE_MERCHANT_WEBSITE,
        ];

        $this->coreMock->shouldReceive('postCommentsInWorkflow')->andThrow(new \Exception());

        $result = $this->callProtectedMethod($this->merchantService, 'postCommentsInWorkflow', [$input]);

        $this->assertTrue(empty($result));
    }

    public function testupdateWorkflowParamsMakerTypeMerchant()
    {
        $input = [DC::WORKFLOW_MAKER_TYPE => MakerType::MERCHANT];

        $workflowService = Mockery::mock('RZP\Services\Workflow\Service');

        $workflowService->shouldReceive('setWorkflowMakerType')->with($input[DC::WORKFLOW_MAKER_TYPE])->andReturn($workflowService);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $workflowService->shouldReceive('setWorkflowMaker')->with($this->merchantEntityMock)->andReturn($workflowService);

        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $this->app->instance('workflow', $workflowService);

        $result = $this->callProtectedMethod($this->merchantService, 'updateWorkflowParams', [$input]);

        $this->assertNull($result);
    }

    public function testupdateWorkflowParamsMakerTypeAdmin()
    {
        $input = [
            DC::WORKFLOW_MAKER_TYPE => MakerType::ADMIN,
            DC::ADMIN_EMAIL => "admin@razorpay.com",
            DC::ORG_ID      => "100000razorpay"
        ];

        $workflowService = Mockery::mock('RZP\Services\Workflow\Service');

        $workflowService->shouldReceive('setWorkflowMakerType')->with($input[DC::WORKFLOW_MAKER_TYPE])->andReturn($workflowService);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);
        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $this->app->instance('workflow', $workflowService);

        $adminRepoMock = Mockery::mock('RZP\Models\Admin\Admin\Repository');

        $adminRepoMock->shouldReceive('findByOrgIdAndEmail')->with($input[DC::ORG_ID], $input[DC::ADMIN_EMAIL])->andReturn($this->adminEntityMock);

        $workflowService->shouldReceive('setWorkflowMaker')->with($this->adminEntityMock)->andReturn($workflowService);

        $this->repoMock->shouldReceive('driver')->with('admin')->andReturn($adminRepoMock);

        $result = $this->callProtectedMethod($this->merchantService, 'updateWorkflowParams', [$input]);

        $this->assertNull($result);
    }

    public function testupdateWorkflowParamsSet()
    {
        $input = [
            DC::PERMISSION           => Name::UPDATE_MERCHANT_WEBSITE,
            DC::ROUTE_NAME           => "internal_post_website_update",
            DC::CONTROLLER           => "RZP\Http\Controllers\MerchantController@internalUpdateWebsite",
            DC::IMITATE_PROXY_AUTH   => true,
            DC::MAKER_FROM_AUTH      => false,
            DC::TAGS                 => ["tag1"],
            DC::ROUTE_PARAMS         => ["id" => "10000000000000"],
            DC::ENTITY               => "merchant_details",
            DC::ENTITY_ID            => "10000000000000",
            DC::URI                  => "/v1/api/merchants/10000000000000/details",
            DC::METHOD               => "POST",
            DC::ORIGINAL             => [Entity::BUSINESS_WEBSITE => "https://razorpay.com"],
            DC::DIRTY                => [Entity::BUSINESS_WEBSITE => "https://razorpay.in"],
            DC::INPUT                => [DC::API_VERSION => DC::WEBSITE_VERSION_V2],
        ];

        $workflowService = Mockery::mock('RZP\Services\Workflow\Service');

        $workflowService->shouldReceive('setPermission')->with($input[DC::PERMISSION])->andReturn($workflowService);
        $workflowService->shouldReceive('setRouteName')->with($input[DC::ROUTE_NAME])->andReturn($workflowService);
        $workflowService->shouldReceive('setController')->with($input[DC::CONTROLLER])->andReturn($workflowService);
        $workflowService->shouldReceive('setImitateProxyAuth')->with($input[DC::IMITATE_PROXY_AUTH])->andReturn($workflowService);
        $workflowService->shouldReceive('setMakerFromAuth')->with($input[DC::MAKER_FROM_AUTH])->andReturn($workflowService);
        $workflowService->shouldReceive('setTags')->with($input[DC::TAGS])->andReturn($workflowService);
        $workflowService->shouldReceive('setRouteParams')->with($input[DC::ROUTE_PARAMS])->andReturn($workflowService);
        $workflowService->shouldReceive('setInput')->with($input[DC::INPUT])->andReturn($workflowService);
        $workflowService->shouldReceive('setEntity')->with($input[DC::ENTITY])->andReturn($workflowService);
        $workflowService->shouldReceive('setEntityId')->with($input[DC::ENTITY_ID])->andReturn($workflowService);
        $workflowService->shouldReceive('setUri')->with($input[DC::URI])->andReturn($workflowService);
        $workflowService->shouldReceive('setMethod')->with($input[DC::METHOD])->andReturn($workflowService);
        $workflowService->shouldReceive('setOriginal')->with($input[DC::ORIGINAL])->andReturn($workflowService);
        $workflowService->shouldReceive('setDirty')->with($input[DC::DIRTY])->andReturn($workflowService);

        $this->app->instance('workflow', $workflowService);

        $result = $this->callProtectedMethod($this->merchantService, 'updateWorkflowParams', [$input]);

        $this->assertNull($result);
    }

    public function testInternalCreateWorkFlowSuccess()
    {
        $workflowService = Mockery::mock('RZP\Services\Workflow\Service');

        $workflowService->shouldReceive("handle")->with()->andThrow(
            new EarlyWorkflowResponse(
            200,
            "{\"id\":\"w_action_OM6PuVRySxurUC\",\"entity_id\":\"OLvgQs0ZwGN5mm\",\"entity_name\":\"merchant_detail\",\"workflow_id\":\"workflow_DnKLKiMo2GrPCw\",\"workflow\":{\"id\":\"workflow_DnKLKiMo2GrPCw\",\"name\":\"update_website\",\"created_at\":1575286834,\"updated_at\":1575286834,\"merchant_id\":null},\"permission_id\":\"perm_DnKJoC50IJbOtI\",\"permission\":{\"id\":\"perm_DnKJoC50IJbOtI\",\"name\":\"edit_merchant_website_detail\",\"description\":\"Toggle merchant website \",\"category\":\"merchant_details\",\"assignable\":false},\"state\":\"open\",\"maker_id\":\"OLvgQs0ZwGN5mm\",\"maker_type\":\"merchant\",\"maker\":{\"id\":\"OLvgQs0ZwGN5mm\",\"entity\":\"merchant\",\"name\":\"CHIZRINZ INFOWAY PRIVATE LIMITED\",\"email\":null,\"activated\":false,\"activated_at\":null,\"live\":false,\"hold_funds\":false,\"pricing_plan_id\":\"1In3Yh5Mluj605\",\"parent_id\":null,\"website\":null,\"category\":\"5651\",\"category2\":\"ecommerce\",\"international\":false,\"linked_account_kyc\":false,\"has_key_access\":false,\"fee_bearer\":\"platform\",\"fee_model\":\"prepaid\",\"refund_source\":\"balance\",\"billing_label\":\"CHIZRINZ INFOWAY PRIVATE LIMITED\",\"receipt_email_enabled\":true,\"receipt_email_trigger_event\":\"authorized\",\"transaction_report_email\":[],\"invoice_label_field\":null,\"channel\":\"axis2\",\"convert_currency\":false,\"max_payment_amount\":50000000,\"max_international_payment_amount\":50000000,\"auto_refund_delay\":null,\"auto_capture_late_auth\":false,\"brand_color\":null,\"handle\":null,\"risk_rating\":3,\"risk_threshold\":8,\"partner_type\":null,\"created_at\":1718212761,\"updated_at\":1718213123,\"suspended_at\":null,\"archived_at\":null,\"icon_url\":null,\"logo_url\":null,\"org_id\":\"100000razorpay\",\"notes\":[],\"whitelisted_ips_live\":[],\"whitelisted_ips_test\":[],\"whitelisted_domains\":[],\"fee_credits_threshold\":null,\"amount_credits_threshold\":null,\"refund_credits_threshold\":null,\"balance_threshold\":null,\"display_name\":null,\"activation_source\":\"primary\",\"business_banking\":false,\"second_factor_auth\":false,\"restricted\":false,\"default_refund_speed\":\"normal\",\"partnership_url\":null,\"external_id\":null,\"product_international\":\"0000000000\",\"signup_source\":\"primary\",\"purpose_code\":null,\"signup_via_email\":0,\"country_code\":\"IN\",\"currency\":\"INR\"},\"org_id\":\"org_100000razorpay\",\"approved\":false,\"current_level\":1,\"created_at\":1718250560,\"updated_at\":1718250560}",
            null,
            ['Content-Type' => 'application/json']
        ));

        $this->app->instance('workflow', $workflowService);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantRepoMock->shouldReceive('findOrFail')->andReturn($this->merchantEntityMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantService = $this->assignProtectedVariableThroughReflection($this->merchantService, 'merchant', $this->merchantEntityMock);

        $this->basicAuthMock->shouldReceive('setMerchant')->with($this->merchantEntityMock)->andReturn();
        $this->app->instance('basicauth', $this->basicAuthMock);

        $this->merchantDetailValidator->shouldReceive('validateInput')->andReturn();

        $result = $this->merchantService->internalCreateWorkFlow("10000000000000", []);

        $this->assertTrue($result[DC::SUCCESS]);
        $this->assertNotEmpty($result[DC::WORFLOW]);
        $this->assertEquals("w_action_OM6PuVRySxurUC", $result[DC::WORFLOW]["id"] );
    }

    private function callProtectedMethod($instance, $method, array $args)
    {
        $class  = new \ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);

        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    protected function assignProtectedVariableThroughReflection($instance, $variableName, $value): mixed
    {
        $reflector = new \ReflectionClass($instance);
        $property = $reflector->getProperty($variableName);
        $property->setAccessible( true );
        $property->setValue($instance, $value);

        return $instance;
    }

    private function createTestforMidBelongsToMswipe()
    {
        $testcases = [
            [
                'business_website_has_mswipe.com'     => true,
                'additional_websites_have_mswipe.com' => false,
                'business_website'                    => 'https://www.mswipe.com',
                'additional_websites'                 => [
                    'https://www.website11.com',
                    'https://www.website21.com',
                    'https://www.website31.com',
                ]
            ],
            [
                'business_website_has_mswipe.com'     => true,
                'additional_websites_have_mswipe.com' => true,
                'business_website'                    => 'https://www.mswipe.com',
                'additional_websites'                 => [
                    'https://www.website12.com',
                    'https://www.mswipe.com',
                    'https://www.website32.com',
                ]
            ],
            [
                'business_website_has_mswipe.com'     => false,
                'additional_websites_have_mswipe.com' => true,
                'business_website'                    => 'https://www.website43.com',
                'additional_websites'                 => [
                    'https://www.website13.com',
                    'https://www.mswipe.com',
                    'https://www.website33.com',
                ]
            ],
            [
                'business_website_has_mswipe.com'     => false,
                'additional_websites_have_mswipe.com' => false,
                'business_website'                    => 'https://www.website44.com',
                'additional_websites'                 => [
                    'https://www.website14.com',
                    'https://www.website24.com',
                    'https://www.website34.com',
                ]
            ],
            [
                'business_website_has_mswipe.com'     => false,
                'additional_websites_have_mswipe.com' => false,
                'business_website'                    => 'https://www.website44.com',
                'additional_websites'                 => null
            ],
        ];

        return $testcases;
    }
}
