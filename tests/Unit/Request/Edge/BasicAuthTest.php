<?php

namespace RZP\Tests\Unit\Request\Edge;

use Exception;
use Razorpay\Edge\Passport;
use RZP\Models\Merchant\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\AuthCreds;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Http\Middleware\Authenticate;
use RZP\Http\Route;
use RZP\Models\User\Role;
use RZP\Services\Edge\Service;
use RZP\Tests\Functional\Helpers\Edge\PassportTrait;
use RZP\Tests\TestCase;
use \Mockery;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use RZP\Models\Merchant\MerchantUser\Entity as MerchantUserEntity;
use RZP\Trace\TraceCode;

class BasicAuthTest extends TestCase
{

    use HasRequestCases;
    use PassportTrait;

    protected $merchantRepoMock = null;

    protected function setUp(): void
    {
        parent::setUp();
        restore_error_handler();
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['canModifyPassport','isEzetapApiApp'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    protected function mockRoute()
    {
        $mock = $this->getMockBuilder(Route::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['isInternalAuthWithPassportRoutes'])
            ->getMock();
        $this->app->instance('api.route', $mock);

        return $mock;
    }

    protected function mockTrace()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info','warning'])
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    /**
     * testShouldLogWarningOnModificationOfPassportAttributes
     * it checks if PASSPORT_GENERATION_NOT_ALLOWED is logged in case of passport alteration is restricted,
     */
    public function testShouldLogWarningOnModificationOfPassportAttributes(){
        // init mocks
        $trace = $this->mockTrace();

        //expectations
        $trace->expects($this->once())
            ->method('warning')
            ->with(
                TraceCode::PASSPORT_GENERATION_NOT_ALLOWED,
                ['passportAlterationPath'=>['setPassportConsumerClaims']]
            );

        //run test
        $ba = new BasicAuth($this->app);
        $ba->init();
        $ba->setPassportModificationNotAllowed();
        $ba->setPassportConsumerClaims('merchant','100000000',true);

        $ba->getPassportJwt('merchant');
        $passportAlterationPath = $ba->getPassportAlterationPath();
        self::assertEquals(['setPassportConsumerClaims'],$passportAlterationPath);

        $passport = $ba->getPassport();
        self::assertEquals(true,$passport['authenticated']);
        self::assertEquals('100000000',$passport['consumer']['id']);
    }


    /**
     * testShouldNotLogWarningOnModificationOfPassportAttributes
     * it checks if PASSPORT_GENERATION_NOT_ALLOWED is not logged in case of passport alteration is restricted
     */
    public function testShouldNotLogWarningOnModificationOfPassportAttributes(){
        // init mocks
        $trace = $this->mockTrace();

        //expectations
        $trace->expects($this->never())
            ->method('warning')
            ->with(
                TraceCode::PASSPORT_GENERATION_NOT_ALLOWED,[]
            );

        //run test
        $ba = new BasicAuth($this->app);
        $ba->init();
        $ba->setPassportConsumerClaims('merchant','100000000',true);

        $ba->getPassportJwt('merchant');
        $passportAlterationPath = $ba->getPassportAlterationPath();
        self::assertEquals([],$passportAlterationPath);

        $passport = $ba->getPassport();
        self::assertEquals(true,$passport['authenticated']);
        self::assertEquals('100000000',$passport['consumer']['id']);
    }


    /**
     * testPassportAlterationPathShouldNotAddMoreThanXValues
     * should not collect more than x calls in the list passportAlterationPath
     */
    public function testPassportAlterationPathShouldNotAddMoreThanXValues(){
        //run test
        $ba = new BasicAuth($this->app);
        $ba->init();
        $ba->setPassportModificationNotAllowed();
        $ba->setPassportConsumerClaims('merchant','100000000',true);
        $ba->setPassportMode('test');
        $ba->setPassportOAuthClaims('merchant','100000000','100000001','100000002','dev');
        $ba->setPassportImpersonationClaims('merchant','100000000','partner');
        $ba->setPassportCredentialClaims('100000000','rzp_test_TheTestAuthKey');
        $ba->setPassportAuthenticated(true);
        $ba->setPassportDomain('razorpay');
        $ba->setPassportRoles(['legacy-key-role']);
        $ba->setPassportRoles(['legacy-key-role-2']);
        $ba->setPassport($this->getDummyMerchantAuthPassport());
        $passportAlterationPath = $ba->getPassportAlterationPath();
        self::assertEquals(9,sizeof($passportAlterationPath));
    }

    public function testSetUserRoleIsAdminReadOnlyWhenAdminLoggedInAsMerchant()
    {
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $mock = $this->getMockBuilder(BasicAuth::class)
                     ->setConstructorArgs([$this->app])
                     ->onlyMethods(['isAdminLoggedInAsMerchantOnDashboard', 'getAdminIdHeader','isProxyAuth','isProductBanking','getAdminPermissions'])
                     ->getMock();
        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('driver')->
        with('merchant')->andReturn($this->merchantRepoMock);

        $mock->expects($this->once())
             ->method('isAdminLoggedInAsMerchantOnDashboard')
             ->willReturn(true);

        $mock->expects($this->once())
             ->method('getAdminPermissions')
             ->willReturn([
                              'hasReadPermission'               => true,
                              'hasLoginPermission'              => true,
                              'hasEditPermission'               => false,
                              'hasNonActivatedEditPermission'   => false,
                              'hasActivatedEditPermission'      => false,
                          ]);

        $mock->expects($this->once())
             ->method('getAdminIdHeader')
             ->willReturn('admi_1234567890123');

        $mock->expects($this->exactly(1))
             ->method('isProductBanking')
             ->willReturn(false);
        $mock->init();
        $authClas = KeyAuthCreds::class;
        $authCreds = new $authClas($this->app);
        $authCreds->creds['key_id'] = '1000000razorpay';
        $mock->setAuthCreds($authCreds);

        $mock->setUserRole('user_id');

        $role = $mock->getUserRole();

        self::assertEquals(Role::ADMIN_READONLY, $role);

    }

    public function testSetUserRoleIsNonActivatedEditWhenAdminLoggedInAsMerchant()
    {
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantUserMapping =  new MerchantUserEntity();

        $merchantUserMapping->setAttribute(MerchantUserEntity::ROLE, Role::OWNER);

        $mock = $this->getMockBuilder(BasicAuth::class)
                     ->setConstructorArgs([$this->app])
                     ->onlyMethods(['isAdminLoggedInAsMerchantOnDashboard', 'getAdminIdHeader','isProxyAuth','isProductBanking','getAdminPermissions','wasMerchantEverActivated', 'getUserRoleFromEntity'])
                     ->getMock();
        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('driver')->
        with('merchant')->andReturn($this->merchantRepoMock);

        $mock->expects($this->exactly(2))
             ->method('isAdminLoggedInAsMerchantOnDashboard')
             ->willReturn(true);

        $mock->expects($this->exactly(1))
             ->method('getUserRoleFromEntity')
             ->willReturn(Role::OWNER);

        $mock->expects($this->once())
             ->method('getAdminPermissions')
             ->willReturn([
                              'hasReadPermission'               => true,
                              'hasLoginPermission'              => true,
                              'hasEditPermission'               => false,
                              'hasNonActivatedEditPermission'   => true,
                              'hasActivatedEditPermission'      => false,
                          ]);

        $mock->expects($this->exactly(2))
             ->method('isProxyAuth')
             ->willReturn(true);

        $mock->expects($this->once())
             ->method('getAdminIdHeader')
             ->willReturn('admi_1234567890123');

        $mock->expects($this->once())
             ->method('wasMerchantEverActivated')
             ->willReturn(false);

        $this->merchantRepoMock
            ->shouldReceive('getMerchantUserMapping')
            ->andReturn($merchantUserMapping);

        $mock->expects($this->exactly(2))
             ->method('isProductBanking')
             ->willReturn(false);

        $mock->init();
        $authClas = KeyAuthCreds::class;
        $authCreds = new $authClas($this->app);
        $authCreds->creds['key_id'] = '1000000razorpay';
        $mock->setAuthCreds($authCreds);

        $mock->setUserRole('user_id');

        $role = $mock->getUserRole();

        self::assertEquals(Role::OWNER, $role);

    }

    public function testSetUserRoleIsActivatedEditWhenAdminLoggedInAsMerchant()
    {
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantUserMapping =  new MerchantUserEntity();

        $merchantUserMapping->setAttribute(MerchantUserEntity::ROLE, Role::OWNER);

        $mock = $this->getMockBuilder(BasicAuth::class)
                     ->setConstructorArgs([$this->app])
                     ->onlyMethods(['isAdminLoggedInAsMerchantOnDashboard', 'getAdminIdHeader','isProxyAuth','isProductBanking','getAdminPermissions','wasMerchantEverActivated', 'getUserRoleFromEntity'])
                     ->getMock();
        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('driver')->
        with('merchant')->andReturn($this->merchantRepoMock);

        $mock->expects($this->exactly(2))
             ->method('isAdminLoggedInAsMerchantOnDashboard')
             ->willReturn(true);

        $mock->expects($this->exactly(1))
             ->method('getUserRoleFromEntity')
             ->willReturn(Role::OWNER);

        $mock->expects($this->once())
             ->method('getAdminPermissions')
             ->willReturn([
                              'hasReadPermission'               => true,
                              'hasLoginPermission'              => true,
                              'hasEditPermission'               => false,
                              'hasNonActivatedEditPermission'   => false,
                              'hasActivatedEditPermission'      => true,
                          ]);

        $mock->expects($this->exactly(2))
             ->method('isProxyAuth')
             ->willReturn(true);

        $mock->expects($this->once())
             ->method('getAdminIdHeader')
             ->willReturn('admi_1234567890123');

        $mock->expects($this->once())
             ->method('wasMerchantEverActivated')
             ->willReturn(true);

        $this->merchantRepoMock
            ->shouldReceive('getMerchantUserMapping')
            ->andReturn($merchantUserMapping);

        $mock->expects($this->exactly(2))
             ->method('isProductBanking')
             ->willReturn(false);

        $mock->init();
        $authClas = KeyAuthCreds::class;
        $authCreds = new $authClas($this->app);
        $authCreds->creds['key_id'] = '1000000razorpay';
        $mock->setAuthCreds($authCreds);

        $mock->setUserRole('user_id');

        $role = $mock->getUserRole();

        self::assertEquals(Role::OWNER, $role);

    }

    /*
     * activated merchants
     * */
    public function testSetUserRoleAvctivatedMerchant()
    {
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantUserMapping =  new MerchantUserEntity();

        $merchantUserMapping->setAttribute(MerchantUserEntity::ROLE, Role::OWNER);

        $mock = $this->getMockBuilder(BasicAuth::class)
                     ->setConstructorArgs([$this->app])
                     ->onlyMethods(['isAdminLoggedInAsMerchantOnDashboard', 'getAdminIdHeader','isProxyAuth','isProductBanking','getAdminPermissions','wasMerchantEverActivated', 'getUserRoleFromEntity'])
                     ->getMock();
        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('driver')->
        with('merchant')->andReturn($this->merchantRepoMock);

        $mock->expects($this->exactly(2))
             ->method('isAdminLoggedInAsMerchantOnDashboard')
             ->willReturn(true);

        $mock->expects($this->exactly(1))
             ->method('getUserRoleFromEntity')
             ->willReturn(Role::OWNER);

        $mock->expects($this->once())
             ->method('getAdminPermissions')
             ->willReturn([
                              'hasReadPermission'               => true,
                              'hasLoginPermission'              => true,
                              'hasEditPermission'               => false,
                              'hasNonActivatedEditPermission'   => true,
                              'hasActivatedEditPermission'      => false,
                          ]);

        $mock->expects($this->exactly(2))
             ->method('isProxyAuth')
             ->willReturn(true);

        $mock->expects($this->once())
             ->method('getAdminIdHeader')
             ->willReturn('admi_1234567890123');

        $mock->expects($this->once())
             ->method('wasMerchantEverActivated')
             ->willReturn(false);

        $this->merchantRepoMock
            ->shouldReceive('getMerchantUserMapping')
            ->andReturn($merchantUserMapping);

        $mock->expects($this->exactly(2))
             ->method('isProductBanking')
             ->willReturn(false);

        $mock->init();
        $authClas = KeyAuthCreds::class;
        $authCreds = new $authClas($this->app);
        $authCreds->creds['key_id'] = '1000000razorpay';
        $mock->setAuthCreds($authCreds);

        $mock->setUserRole('user_id');

        $role = $mock->getUserRole();

        self::assertEquals(Role::OWNER, $role);

    }

    public function testSetUserRoleIsBankingReadOnlyWhenAdminIsLoggedInAsMerchant()
    {
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');
        $merchantUserMapping = Mockery::mock('RZP\Models\Merchant\MerchantUser\Entity');

        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['isAdminLoggedInAsMerchantOnDashboard','isProxyAuth','isProductBanking'])
            ->getMock();

        $this->app->instance('repo', $this->repoMock);
        $this->repoMock->shouldReceive('driver')->
        with('merchant')->andReturn($this->merchantRepoMock);

        $mock->expects($this->exactly(2))
            ->method('isAdminLoggedInAsMerchantOnDashboard')
            ->willReturn(true);

        $mock->expects($this->exactly(1))
            ->method('isProxyAuth')
            ->willReturn(true);

        $this->merchantRepoMock
            ->shouldReceive('getMerchantUserMapping')
            ->andReturn($merchantUserMapping);


        $mock->expects($this->exactly(1))
            ->method('isProductBanking')
            ->willReturn(true);

        $mock->init();
        $authClas = KeyAuthCreds::class;
        $authCreds = new $authClas($this->app);
        $authCreds->creds['key_id'] = '1000000razorpay';
        $mock->setAuthCreds($authCreds);


        $mock->setUserRole('user_id');

        $role = $mock->getUserRole();


        self::assertEquals(Role::BANKING_READONLY, $role);
        Mockery::close();
    }
}
