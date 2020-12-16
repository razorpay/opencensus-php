<?php

namespace Unit\Models\Merchant\Detail;

use Mockery;

use Tests\Unit\TestCase;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Detail\Service as MerchantService;

class DetailServiceTest extends TestCase
{

    protected $merchantService;
    protected $coreMock;
    protected $merchantEntityMock;
    protected $userEntityMock;
    protected $deviceEntityMock;
    protected $merchantDetailEntityMock;
    protected $merchantRepoMock;
    protected $merchantMethodsMock;
    protected $merchantDocumentCoreMock;
    protected $merchantDetailValidator;

    public function setUp()
    {

        parent::setUp();
        $this->createTestDependencyMocks();
        $this->merchantService = new MerchantService($this->coreMock);
    }

    public function testFetchMerchantAndServiceDetails()
    {
        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->merchantDetailEntityMock->shouldReceive('load')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('merchant')->andReturn($this->merchantEntityMock);

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

        $response = $this->merchantService->fetchMerchantDetails();

        $verification = $response['verification'];

        $this->assertEquals('disabled', $verification['status']);

        $this->assertEquals(false, $response['live']);

        $this->assertEquals(false, $response['international']);
    }

    public function testGetDisabledBanksMoreInfo()
    {
        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchant')->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('id')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantDetailEntityMock->shouldReceive('getAttribute')->with('merchant')->andReturn($this->merchantEntityMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchantDetail')->andReturn($this->merchantRepoMock);

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
        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isLinkedAccount')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('offsetExists')->andReturn(true);

        $this->merchantDetailEntityMock->shouldReceive('offsetGet')->andReturn(false);

        $response = $this->merchantService->fetchActivationFiles("100002Razorpay");

        $filesArray = $response['files'];

        $this->assertEquals("paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf", $filesArray['business_proof']);
    }

    public function testSaveMerchantDetailForPreSignUp()
    {
        $merchantData = [
        'merchant_id'           => '1cXSLlUU8V9sXl',
        'product'               => 'banking',
        'role'                  => 'manager',
        'action'                => 'edit'];

        $this->createMerchantTestDependencyMocks();

        $this->coreMock->shouldReceive('setModeAndDefaultConnection')->andReturn();

        $diagMock = Mockery::mock('RZP\Services\DiagClient');

        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->app->instance('diag', $diagMock);

        $this->hubspotMock->shouldReceive('trackPreSignupEvent')->andReturn([]);

        $this->app->instance('hubspot', $this->hubspotMock);

        $response = $this->merchantService->saveMerchantDetailForPreSignUp($merchantData);

        $this->assertEquals([], $response);
    }

    public function testSaveMerchantDetailsForActivation()
    {
        $merchantData = [
            'merchant_id'           => '1cXSLlUU8V9sXl',
            'product'               => 'banking',
            'role'                  => 'manager',
            'action'                => 'edit'];

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->createMerchantTestDependencyMocks();

        $this->hubspotMock->shouldReceive('trackL2ContactProperties')->andReturn([]);

        $this->app->instance('hubspot', $this->hubspotMock);

        $diagMock = Mockery::mock('RZP\Services\DiagClient');

        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->app->instance('diag', $diagMock);

        $response = $this->merchantService->saveMerchantDetailsForActivation($merchantData);

        $this->assertEquals([], $response);
    }

    public function createMerchantTestDependencyMocks()
    {
        $this->basicAuthMock->shouldReceive('getLiveConnection')->andReturn('test');

        $this->basicAuthMock->shouldReceive('setModeAndDbConnection')->andReturn('test');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantDetailEntityMock->shouldReceive('getValidator')->andReturn($this->merchantDetailValidator);

        $this->merchantDetailValidator->shouldReceive('validateIsNotLocked')->andReturn();

        $this->merchantDetailValidator->shouldReceive('blockInstantActivationCriticalFields')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('edit')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isRazorpayOrgId')->andReturn(false);

        $this->merchantDetailEntityMock->shouldReceive('setPoiVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('getBusinessTypeValue')->andReturn(0);

        $this->merchantDetailEntityMock->shouldReceive('setCompanyPanVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setGstinVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setShopEstbVerificationStatus')->andReturn();

        $this->merchantDetailEntityMock->shouldReceive('setCinVerificationStatus')->andReturn();

        $this->repoMock->shouldReceive('transactionOnLiveAndTest')->andReturn([]);
    }

    public function createTestDependencyMocks()
    {
        // User Entity Mocking
        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity')->makePartial()->shouldAllowMockingProtectedMethods();

        // Merchant Mocking
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        // Merchant Repo mocking
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        // Merchant Mocking
        $this->merchantDetailEntityMock = Mockery::mock('RZP\Models\Merchant\Detail\Entity');

        // Merchant Mocking
        $this->merchantDocumentCoreMock = Mockery::mock('RZP\Models\Merchant\Document\Core');

        // Device Entity Mocking
        $this->deviceEntityMock = Mockery::mock('RZP\Models\Device\Entity');

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
    }
}
