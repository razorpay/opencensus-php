<?php

namespace Unit\Edge;

use Mockery;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Http\BasicAuth\Type;
use RZP\Http\Middleware\AdminAccess;
use RZP\Http\RequestHeader;
use ApiResponse;
use RZP\Models\Admin\Group\Core;
use RZP\Models\Merchant\Entity;
use RZP\Services\Edge\Service;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;
use RZP\Tests\TestCase;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

class ServiceTest extends TestCase
{

    use PrivateMethodTrait;
    private mixed $sampleInput;

    // Mocks
    private mixed $baMock, $keyAuthCredsMock, $repoMock, $merchantRepoMock, $orgRepoMock, $adminAccessMock, $traceMock, $adminGroupCore;


    use HasRequestCases;

    public function __construct()
    {
        parent::__construct();
        $this->sampleInput = [
            'key' => 'rzp_live',
            'secret' => 'RANDOM_DASH_PASSWORD',
            'auth' => 'internal',
            'admin_token_required' => true,
            'apps' => ['dashboard', 'admin_dashboard'],
            'account_id' => 'Mh73OcxLsoSqIl',
            'org_id' => 'org_100000razorpay',
            'headers' => [
                'X-Admin-Token' => 'qsKKZ5bvrmOWezO2bp9MMgwiQLfKdIHf7U',
                'X-Dashboard-Merchant' => 'BT73OPxLsoSqIl',
            ],
            'route_params' => [
                'merchant_id' => 'PT1qOPYrsoSqIl'
            ]
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->baMock = $this->mockBasicAuth();
        $this->keyAuthCredsMock = $this->mockKeyAuthCreds();
        $this->repoMock = $this->mockRepo();
        $this->merchantRepoMock = $this->mockMerchantRepo();
        $this->orgRepoMock = $this->mockOrgRepo();
        $this->adminAccessMock = $this->mockAdminAccess();
        $this->adminGroupCore = $this->mockAdminGroupCore();
        $this->traceMock = $this->mockTrace();
    }

    ////////// appAuth() test cases start //////////


    public function testAppAuthHappyCase()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->sampleInput['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($this->sampleInput['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertNull($res);
    }


    public function testAppAuthWhenKeyIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id'])
            ->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('isKeyBlank');
        $this->baMock->expects($this->never())->method('verifyInternalAppSecret');
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_CREDENTIALS_FAILED);

        $res = (new Service())->appAuth($this->sampleInput);

        $this->assertEquals($expectedError, $res);
    }

    public function testAppAuthWhenKeyIsNotBlank()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(false);
        $this->baMock->expects($this->never())->method('verifyInternalAppSecret');
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_KEY_NOT_BLANK);

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }


    public function testAppAuthWhenSecretIsIncorrect()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(false);
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED), $res);
    }

    public function testAppAuthWhenAppIsIncorrect()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn('frontend_graphql'); // Return a different app name as compared to the one in sample input
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED), $res);
    }

    public function testAppAuthWhenAdminTokenIsNotSet()
    {
        $input = $this->sampleInput;
        $input['headers'][RequestHeader::X_ADMIN_TOKEN] = '';


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($input['key'], $input['secret'], $input['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($input['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service())->appAuth($input);
        $this->assertNull($res);
    }

    public function testAppAuthWhenAdminTokenIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ADMIN_AUTH_FAILED);

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    public function testAppAuthWhenAccountIdIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->sampleInput['headers'][RequestHeader::X_ADMIN_TOKEN]);

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($this->sampleInput['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with()->willReturn($expectedError);

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ACCOUNT_SCOPE_FAILED);

        $res = (new Service())->appAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    ////////// appAuth() test cases end //////////
    ////////// proxyAuth() test cases start //////////
    public function testProxyAuthWithAdminTokenHappyCase()
    {
        $merchantEntity = new Entity();

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->sampleInput['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($this->sampleInput['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertNull($res);
    }

    public function testProxyAuthWithoutAdminTokenHappyCase()
    {
        $merchantEntity = new Entity();
        $input = $this->sampleInput;
        unset($input['headers'][RequestHeader::X_ADMIN_TOKEN]);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($input['key'], $input['secret'], $input['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($input['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service())->proxyAuth($input);
        $this->assertNull($res);
    }

    public function testProxyAuthWithoutAdminTokenAndAccountIdHappyCase()
    {
        $merchantEntity = new Entity();
        $input = $this->sampleInput;
        unset($input['headers'][RequestHeader::X_ADMIN_TOKEN]);
        unset($input['account_id']);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($input['key'], $input['secret'], null);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with($input['headers']);
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service())->proxyAuth($input);
        $this->assertNull($res);
    }

    public function testProxyAuthWhenKeyIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id'])
            ->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('verifyInternalAppSecret');
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->keyAuthCredsMock->expects($this->never())->method('getKey');
        $this->merchantRepoMock->shouldNotReceive('find');
        $this->keyAuthCredsMock->expects($this->never())->method('setMerchant');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_CREDENTIALS_FAILED);

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenSecretIsIncorrect()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->willReturn(false);
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->keyAuthCredsMock->expects($this->never())->method('getKey');
        $this->merchantRepoMock->shouldNotReceive('find');
        $this->keyAuthCredsMock->expects($this->never())->method('setMerchant');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAppIsIncorrect()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->willReturn('frontend_graphql'); // returns a different app name as compared to what's mentioned in sample input
        $this->keyAuthCredsMock->expects($this->never())->method('getKey');
        $this->merchantRepoMock->shouldNotReceive('find');
        $this->keyAuthCredsMock->expects($this->never())->method('setMerchant');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAdminTokenIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $merchantEntity = new Entity();
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ADMIN_AUTH_FAILED);

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAccountIdIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $merchantEntity = new Entity();
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput['key'], $this->sampleInput['secret'], $this->sampleInput['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->sampleInput['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders');
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with()->willReturn($expectedError);

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ACCOUNT_SCOPE_FAILED);

        $res = (new Service())->proxyAuth($this->sampleInput);
        $this->assertEquals($expectedError, $res);
    }

    /// ////////// proxyAuth() test cases end //////////
    ////////// authorizeExceptRBAC test cases start //////////

    private function authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $merchantId, $merchantEntity)
    {
        $this->baMock->expects($this->once())->method('setOrgId')->with($orgId);
        $this->adminAccessMock->expects($this->once())->method('setOrgType')->with($orgId);
        $this->baMock->expects($this->once())->method('getAdmin')->willReturn($adminEntity);
        $this->adminAccessMock->expects($this->once())->method('getMerchant')->with($merchantId)->willReturn($merchantEntity);

        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::EDGE_THIRD_PARTY_ADMIN_AUTHORIZE, [
            'org_id' => $orgId,
            'admin_id' => $adminEntity->getId(),
            'admin_public_org_id' => $adminEntity->getPublicOrgId(),
            'merchant_id' => $merchantEntity ? $merchantEntity->getId() : null
        ]);
    }


    public function testAuthorizeExceptRBACAdminAuthHappyCase()
    {
        $orgId = $this->sampleInput['org_id'];
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId);

        $merchantEntity = $this->getMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($strippedOrgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->sampleInput['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(true);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$this->sampleInput, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthUsingOrgHostnameHappyCase()
    {
        $input = $this->sampleInput;
        $orgId = $input['org_id'];
        unset($input['org_id']);

        // Set Org hostname header
        $input['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY] = 'dummy_hostname';

        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId);

        $merchantEntity = $this->getMerchantEntity();

        $this->adminAccessMock->expects($this->once())->method('resolveOrgIdFromHostname')->with($input['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY])->willReturn($orgId);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $input['route_params']['merchant_id'], $merchantEntity);

        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(true);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$input, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthMerchantNullHappyCase()
    {
        $orgId = $this->sampleInput['org_id'];
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId);

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($strippedOrgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->sampleInput['route_params']['merchant_id'], null);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$this->sampleInput, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthAdminLocked()
    {
        $orgId = $this->sampleInput['org_id'];
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId, isLocked: true);

        $merchantEntity = $this->getMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($strippedOrgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->sampleInput['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$this->sampleInput, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthAdminDisabled()
    {
        $orgId = $this->sampleInput['org_id'];
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId, isDisabled: true);

        $merchantEntity = $this->getMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($strippedOrgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->sampleInput['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$this->sampleInput, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthOrgNull()
    {
        $input = $this->sampleInput;
        $orgId = $input['org_id'];
        unset($input['org_id']);
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId);

        $merchantEntity = $this->getMerchantEntity();

        $this->authorizeExceptRBACAdminAuthCommonExpectations(null, $adminEntity, $input['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$input, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_INVALID_ORG_ID), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthGroupCheckFail()
    {
        $orgId = $this->sampleInput['org_id'];
        $strippedOrgId = $orgId;
        \RZP\Models\Admin\Org\Entity::verifyIdAndStripSign($strippedOrgId);
        $adminEntity = $this->getAdminEntity($strippedOrgId);
        $merchantEntity = $this->getMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($strippedOrgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->sampleInput['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(false);

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service(), [$this->sampleInput, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED), $res);
    }











    // ////////// authorizeExceptRBAC test cases end //////////










    protected function mockKeyAuthCreds()
    {
        $mock = $this->getMockBuilder(KeyAuthCreds::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['setMerchant', 'getKey'])
            ->getMock();

        $this->baMock->authCreds = $mock;

        return $mock;
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['setType', 'setAppAuth', 'isKeyBlank', 'setCredentials', 'verifyInternalAppSecret',
                'getInternalApp', 'fetchAndSetAdminUsingToken', 'setDashboardHeaders', 'checkAndSetAccountScope', 'setProxyTrue', 'isAdminAuth', 'setOrgId', 'getAdmin'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    protected function mockRepo()
    {
        $mock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mock);
        return $mock;
    }

    protected function mockMerchantRepo()
    {
        $mock = Mockery::mock('\RZP\Models\Merchant\Repository', [$this->app]);
        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($mock);
        return $mock;
    }

    protected function mockOrgRepo()
    {
        $mock = Mockery::mock('\RZP\Models\Admin\Org\Repository', [$this->app]);
        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($mock);
        return $mock;
    }

    protected function mockAdminAccess()
    {
        return $this->getMockBuilder(AdminAccess::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['setOrgType', 'getMerchant', 'resolveOrgIdFromHostname'])
            ->getMock();
    }

    protected function mockTrace()
    {
        $mock = Mockery::mock('\Razorpay\Trace\Logger');
        $this->app->instance('trace', $mock);
        return $mock;
    }

    protected function mockAdminGroupCore()
    {
        return $this->getMockBuilder(Core::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['groupCheck'])
            ->getMock();
    }

    protected function getMerchantEntity()
    {
        $entity = new Entity();
        $entity->setAttribute(Entity::ID, '10000000000000');
        return $entity;
    }

    protected function getAdminEntity(string $orgId, bool $isLocked = false, bool $isDisabled = false)
    {
        $entity = new AdminEntity();
        $entity->setAttribute(AdminEntity::LOCKED, $isLocked);
        $entity->setAttribute(AdminEntity::DISABLED, $isDisabled);
        $entity->setAttribute(AdminEntity::ORG_ID, $orgId);
        return $entity;
    }

}
