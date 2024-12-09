<?php

namespace Unit\Edge;

use Mockery;
use RZP\Constants\Product;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Http\BasicAuth\Type;
use RZP\Http\Middleware\AdminAccess;
use RZP\Http\Middleware\MerchantIpFilter;
use RZP\Http\RequestHeader;
use ApiResponse;
use RZP\Models\Admin\Group\Core;
use RZP\Models\Merchant\Entity;
use RZP\Models\User\Role;
use RZP\Services\Edge\Service;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;
use RZP\Tests\TestCase;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\User\Entity as UserEntity;

class ServiceTest extends TestCase
{

    use PrivateMethodTrait;
    private mixed $sampleInput1, $sampleInput2, $dashboardRequest1Info, $dashboardRequest2Info;

    // Mocks
    private mixed
        $baMock, $keyAuthCredsMock, $repoMock, $merchantRepoMock,
        $orgRepoMock, $adminAccessMock, $traceMock, $adminGroupCore,
        $roleAccessPolicyMapServiceMock, $merchantIpFilterMock;


    use HasRequestCases;

    public function __construct()
    {
        parent::__construct();

        $this->sampleInput1 = [
            'dashboard' => [
                'key' => 'rzp_live',
                'secret' => 'RANDOM_DASH_PASSWORD',
                'account_id' => 'Mh73OcxLsoSqIl',
                'org_id' => '100000razorpay',
                'headers' => [
                    'X-Admin-Token' => 'qsKKZ5bvrmOWezO2bp9MMgwiQLfKdIHf7U',
                    'X-Dashboard-Merchant' => 'BT73OPxLsoSqIl',
                ],
                'route_params' => [
                    'merchant_id' => 'PT1qOPYrsoSqIl'
                ]
            ],
            'auth' => 'internal',
            'admin_token_required' => true,
            'apps' => ['dashboard', 'admin_dashboard'],
        ];

        $this->dashboardRequest1Info =  & $this->sampleInput1['dashboard'];

        $this->sampleInput2 = [
            'dashboard' => [
                'key' => 'rzp_live_PT1qOPYrsoSqIl',
                'secret' => 'RANDOM_DASH_PASSWORD',
                'account_id' => 'Mh73OcxLsoSqIl',
                'headers' => [
                    RequestHeader::X_DASHBOARD_USER_ID => 'XYZ123userSqIP',
                    RequestHeader::X_REQUEST_ORIGIN => 'https://x.razorpay.com',
                    RequestHeader::X_Creator_Id => '',
                    RequestHeader::X_Creator_Type => '',
                    RequestHeader::X_DASHBOARD_IP => '1.1.1.1'
                ]
            ],
            'auth' => 'proxy',
            'admin_token_required' => false,
            'apps' => ['dashboard', 'admin_dashboard'],
        ];

        $this->dashboardRequest2Info = & $this->sampleInput2['dashboard'];


        putenv('BANKING_SERVICE_URL=https://x.razorpay.com');
        putenv('BANK_LMS_BANKING_SERVICE_URL=https://partner-lms.razorpay.com');
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
        $this->roleAccessPolicyMapServiceMock = $this->mockRoleAccessPolicyMapServiceMock();
        $this->merchantIpFilterMock = $this->mockMerchantIpFilterMock();
    }

    ////////// appAuth() test cases start //////////


    public function testAppAuthHappyCase()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->dashboardRequest1Info['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($this->dashboardRequest1Info['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);
        $this->assertNull($res);
    }


    public function testAppAuthWhenKeyIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id'])
            ->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('isKeyBlank');
        $this->baMock->expects($this->never())->method('verifyInternalAppSecret');
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_CREDENTIALS_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);


        $this->assertEquals($expectedError, $res);
    }

    public function testAppAuthWhenKeyIsNotBlank()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(false);
        $this->baMock->expects($this->never())->method('verifyInternalAppSecret');
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_KEY_NOT_BLANK);

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);
        $this->assertEquals($expectedError, $res);
    }


    public function testAppAuthWhenSecretIsIncorrect()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(false);
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED), $res);
    }

    public function testAppAuthWhenAppIsIncorrect()
    {
        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->exactly(2))->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn('frontend_graphql'); // Return a different app name as compared to the one in sample input
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED), $res);
    }

    public function testAppAuthWhenAdminTokenIsNotSet()
    {
        $input = $this->sampleInput1;
        $input['dashboard']['headers'][RequestHeader::X_ADMIN_TOKEN] = '';


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($input['dashboard']['key'], $input['dashboard']['secret'], $input['dashboard']['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($input['dashboard']['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service($input['dashboard']['headers']))->appAuth($input);

        $this->assertNull($res);
    }

    public function testAppAuthWhenAdminTokenIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ADMIN_AUTH_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    public function testAppAuthWhenAccountIdIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);


        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVILEGE_AUTH);
        $this->baMock->expects($this->once())->method('setAppAuth')->with(true);
        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('isKeyBlank')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->dashboardRequest1Info['headers'][RequestHeader::X_ADMIN_TOKEN]);

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($this->dashboardRequest1Info['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with()->willReturn($expectedError);

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ACCOUNT_SCOPE_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->appAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    ////////// appAuth() test cases end //////////
    ////////// proxyAuth() test cases start //////////
    public function testProxyAuthWithAdminTokenHappyCase()
    {
        $merchantEntity = new Entity();

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);

        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->dashboardRequest1Info['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($this->dashboardRequest1Info['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertNull($res);
    }

    public function testProxyAuthWithoutAdminTokenHappyCase()
    {
        $merchantEntity = new Entity();

        $input = $this->sampleInput1;
        unset($input['dashboard']['headers'][RequestHeader::X_ADMIN_TOKEN]);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();
        $this->baMock->expects($this->once())->method('setCredentials')->with($input['dashboard']['key'], $input['dashboard']['secret'], $input['dashboard']['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($input['dashboard']['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service($input['dashboard']['headers']))->proxyAuth($input);

        $this->assertNull($res);
    }

    public function testProxyAuthWithoutAdminTokenAndAccountIdHappyCase()
    {
        $merchantEntity = new Entity();

        $input = $this->sampleInput1;
        unset($input['dashboard']['headers'][RequestHeader::X_ADMIN_TOKEN]);
        unset($input['dashboard']['account_id']);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($input['dashboard']['key'], $input['dashboard']['secret'], null);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->with()->willReturn($input['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');

        $this->baMock->expects($this->once())->method('setDashboardHeaders')->with(array_change_key_case($input['dashboard']['headers']));
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with();

        $res = (new Service($input['dashboard']['headers']))->proxyAuth($input);

        $this->assertNull($res);
    }

    public function testProxyAuthWhenKeyIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->sampleInput1['dashboard']['key'], $this->sampleInput1['dashboard']['secret'], $this->sampleInput1['dashboard']['account_id'])
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

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenSecretIsIncorrect()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->willReturn(false);
        $this->baMock->expects($this->never())->method('getInternalApp');
        $this->keyAuthCredsMock->expects($this->never())->method('getKey');
        $this->merchantRepoMock->shouldNotReceive('find');
        $this->keyAuthCredsMock->expects($this->never())->method('setMerchant');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAppIsIncorrect()
    {
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->willReturn(true);
        $this->baMock->expects($this->once())->method('getInternalApp')->willReturn('frontend_graphql'); // returns a different app name as compared to what's mentioned in sample input
        $this->keyAuthCredsMock->expects($this->never())->method('getKey');
        $this->merchantRepoMock->shouldNotReceive('find');
        $this->keyAuthCredsMock->expects($this->never())->method('setMerchant');
        $this->baMock->expects($this->never())->method('fetchAndSetAdminUsingToken');
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAdminTokenIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $merchantEntity = new Entity();
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);

        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);
        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->willReturn($expectedError);
        $this->baMock->expects($this->never())->method('setDashboardHeaders');
        $this->baMock->expects($this->never())->method('checkAndSetAccountScope');

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ADMIN_AUTH_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    public function testProxyAuthWhenAccountIdIsInvalid()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $merchantEntity = new Entity();
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);

        $this->baMock->expects($this->once())->method('setType')->with(Type::PRIVATE_AUTH);
        $this->baMock->expects($this->once())->method('setProxyTrue')->with();

        $this->baMock->expects($this->once())->method('setCredentials')->with($this->dashboardRequest1Info['key'], $this->dashboardRequest1Info['secret'], $this->dashboardRequest1Info['account_id']);
        $this->baMock->expects($this->once())->method('verifyInternalAppSecret')->with()->willReturn(true);
        $this->baMock->expects($this->atLeastOnce())->method('getInternalApp')->with()->willReturn($this->sampleInput1['apps'][0]);
        $this->keyAuthCredsMock->expects($this->once())->method('getKey')->with()->willReturn('10000000000011');
        $this->merchantRepoMock->shouldReceive('find')->andReturn($merchantEntity);
        $this->keyAuthCredsMock->expects($this->once())->method('setMerchant')->with($merchantEntity);

        $this->baMock->expects($this->once())->method('fetchAndSetAdminUsingToken')->with($this->dashboardRequest1Info['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->baMock->expects($this->once())->method('setDashboardHeaders');
        $this->baMock->expects($this->once())->method('checkAndSetAccountScope')->with()->willReturn($expectedError);

        $this->traceMock->shouldReceive('error')->once()->with(TraceCode::EDGE_THIRD_PARTY_SET_ACCOUNT_SCOPE_FAILED);

        $res = (new Service($this->dashboardRequest1Info['headers']))->proxyAuth($this->sampleInput1);

        $this->assertEquals($expectedError, $res);
    }

    /// ////////// proxyAuth() test cases end //////////
    ////////// authorizeAdminAccessExceptRBAC test cases start //////////


    private function authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $merchantId, $merchantEntity)
    {
        $signedOrgId = is_null($orgId) ? null: "org_" . $orgId;
        $this->baMock->expects($this->once())->method('setOrgId')->with($signedOrgId);
        $this->adminAccessMock->expects($this->once())->method('setOrgType')->with($signedOrgId);
        $this->baMock->expects($this->once())->method('getAdmin')->willReturn($adminEntity);
        $this->adminAccessMock->expects($this->once())->method('getMerchant')->with($merchantId)->willReturn($merchantEntity);

        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::EDGE_THIRD_PARTY_ADMIN_AUTHORIZE, [
            'org_id' => $signedOrgId,
            'admin_id' => $adminEntity->getId(),
            'admin_public_org_id' => $adminEntity->getPublicOrgId(),
            'merchant_id' => $merchantEntity ? $merchantEntity->getId() : null
        ]);
    }


    public function testAuthorizeExceptRBACAdminAuthHappyCase()
    {

        $orgId = $this->dashboardRequest1Info['org_id'];
        $adminEntity = $this->getAdminEntity($orgId);

        $merchantEntity = $this->getMockMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($orgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->dashboardRequest1Info['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(true);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$this->sampleInput1, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthUsingOrgHostnameHappyCase()
    {

        $input = $this->sampleInput1;
        $orgId = $input['dashboard']['org_id'];
        unset($input['dashboard']['org_id']);

        // Set Org hostname header
        $input['dashboard']['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY] = 'dummy_hostname';

        $adminEntity = $this->getAdminEntity($orgId);

        $merchantEntity = $this->getMockMerchantEntity();

        $this->adminAccessMock->expects($this->once())->method('resolveOrgIdFromHostname')->with($input['dashboard']['headers'][AdminAccess::ORG_HOSTNAME_HEADER_KEY])->willReturn("org_" . $orgId);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $input['dashboard']['route_params']['merchant_id'], $merchantEntity);

        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(true);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$input, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthMerchantNullHappyCase()
    {
        $orgId = $this->dashboardRequest1Info['org_id'];
        $adminEntity = $this->getAdminEntity($orgId);

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($orgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->dashboardRequest1Info['route_params']['merchant_id'], null);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$this->sampleInput1, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertNull($res);
    }

    public function testAuthorizeExceptRBACAdminAuthAdminLocked()
    {

        $orgId = $this->dashboardRequest1Info['org_id'];
        $adminEntity = $this->getAdminEntity($orgId, isLocked: true);

        $merchantEntity = $this->getMockMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($orgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->dashboardRequest1Info['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$this->sampleInput1, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthAdminDisabled()
    {

        $orgId = $this->dashboardRequest1Info['org_id'];
        $adminEntity = $this->getAdminEntity($orgId, isDisabled: true);

        $merchantEntity = $this->getMockMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($orgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->dashboardRequest1Info['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$this->sampleInput1, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthOrgNull()
    {

        $input = $this->sampleInput1;
        $orgId = $input['dashboard']['org_id'];
        unset($input['dashboard']['org_id']);
        $adminEntity = $this->getAdminEntity($orgId);

        $merchantEntity = $this->getMockMerchantEntity();

        $this->authorizeExceptRBACAdminAuthCommonExpectations(null, $adminEntity, $input['dashboard']['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->never())->method('groupCheck');

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($input['dashboard']['headers']), [$input, $this->adminAccessMock, $this->adminGroupCore]);

        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_INVALID_ORG_ID), $res);
    }

    public function testAuthorizeExceptRBACAdminAuthGroupCheckFail()
    {
        $orgId = $this->dashboardRequest1Info['org_id'];
        $adminEntity = $this->getAdminEntity($orgId);
        $merchantEntity = $this->getMockMerchantEntity();

        $this->orgRepoMock->shouldReceive('isValidOrg')->once()->with($orgId)->andReturn(true);
        $this->authorizeExceptRBACAdminAuthCommonExpectations($orgId, $adminEntity, $this->dashboardRequest1Info['route_params']['merchant_id'], $merchantEntity);
        $this->adminGroupCore->expects($this->once())->method('groupCheck')->with($adminEntity, $merchantEntity)->willReturn(false);

        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeAdminAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest1Info['headers']), [$this->sampleInput1, $this->adminAccessMock, $this->adminGroupCore]);
        $this->assertEquals(ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED), $res);
    }

    // ////////// authorizeAdminAccessExceptRBAC test cases end //////////
    // ////////// authorizeUserAccessExceptRBAC test cases start //////////
    private function authorizeUserAccessExceptRBACCommonExpectations($product, $isLms, $userId, $isCACEnabled)
    {
        $merchantEntity = $this->getMockMerchantEntity();
        $userEntity = $this->getUserEntity($userId);
        $this->baMock->expects($this->once())->method('setUserAndRoles')->with($userId);
        $this->baMock->expects($this->once())->method('getMerchant')->with()->willReturn($merchantEntity);
        $merchantEntity->expects($this->once())->method('isCACEnabled')->with()->willReturn($isCACEnabled);
        $this->baMock->expects($this->once())->method('getUser')->with()->willReturn($userEntity);
        $this->baMock->expects($this->once())->method('getMerchantId')->with()->willReturn($merchantEntity->getId());

        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::EDGE_THIRD_PARTY_USER_AUTHORIZE, [
            'product' => $product,
            'is_lms' => $isLms,
            'user_id' => $userId,
            'merchant_id' => $merchantEntity->getId(),
            'is_cac_enabled' => $isCACEnabled,
            'client_ip' => $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_IP]
        ]);
    }

    public function testAuthorizeUserAccessExceptRBACBankingHappyCase()
    {
        $userId = $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID];

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn(['user_id' => $userId]);
        $this->baMock->expects($this->exactly(2))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::BANKING, false, $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID], true);

        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')->with($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_IP]);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest2Info['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertNull($res);
    }

    public function testAuthorizeUserAccessExceptRBACBankingWithoutCACHappyCase()
    {
        $userId = $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID];

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn(['user_id' => $userId]);
        $this->baMock->expects($this->exactly(3))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::BANKING, false, $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID], false);

        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')->with($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_IP]);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest2Info['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertNull($res);
    }

    public function testAuthorizeUserAccessExceptRBACLMSHappyCase()
    {
        $input = $this->sampleInput2;
        $input['dashboard']['headers'][RequestHeader::X_REQUEST_ORIGIN] = 'https://partner-lms.razorpay.com';
        $userId = $input['dashboard']['headers'][RequestHeader::X_DASHBOARD_USER_ID];

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn(['user_id' => $userId]);
        $this->baMock->expects($this->exactly(3))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::BANKING, true, $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID], true);

        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')->with($input['dashboard']['headers'][RequestHeader::X_DASHBOARD_IP]);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($input['dashboard']['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertNull($res);
    }

    public function testAuthorizeUserAccessExceptRBACPrimaryHappyCase()
    {
        $input = $this->sampleInput2;
        $input['dashboard']['headers'][RequestHeader::X_REQUEST_ORIGIN] = 'https://dashboard.razorpay.com';
        $userId = $input['dashboard']['headers'][RequestHeader::X_DASHBOARD_USER_ID];

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn(['user_id' => $userId]);
        $this->baMock->expects($this->exactly(3))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::PRIMARY, false, $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID], true);
        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')->with($input['dashboard']['headers'][RequestHeader::X_DASHBOARD_IP]);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($input['dashboard']['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertNull($res);
    }

    public function testAuthorizeUserAccessExceptRBACCreatorHeadersHappyCase()
    {
        $userId = $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID];
        unset($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID]);
        $this->dashboardRequest2Info['headers'][RequestHeader::X_Creator_Id] = $userId;
        $this->dashboardRequest2Info['headers'][RequestHeader::X_Creator_Type] = 'user';

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn([]);
        $this->baMock->expects($this->exactly(2))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::BANKING, false, $userId, true);

        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')->with($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_IP]);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest2Info['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertNull($res);
    }

    public function testAuthorizeUserAccessExceptRBACCreatorHeadersAdmin()
    {
        $merchantEntity = $this->getMockMerchantEntity();
        $userId = $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID];
        unset($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID]);
        $this->dashboardRequest2Info['headers'][RequestHeader::X_Creator_Id] = $userId;
        $this->dashboardRequest2Info['headers'][RequestHeader::X_Creator_Type] = 'admin';

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn([]);
        $this->baMock->expects($this->never())->method('setUserAndRoles');
        $this->baMock->expects($this->never())->method('getUserRole');
        $this->roleAccessPolicyMapServiceMock->expects($this->never())->method('getAuthzRolesForRoleId');
        $this->baMock->expects($this->atLeastOnce())->method('getMerchant'); // all calls are made by unauthorized()
        $merchantEntity->expects($this->never())->method('isCACEnabled');
        $this->baMock->expects($this->never())->method('getUser');
        $this->baMock->expects($this->never())->method('getMerchantId');

        $this->traceMock->shouldReceive('info')->never()->with(TraceCode::EDGE_THIRD_PARTY_USER_AUTHORIZE, Mockery::any());
        $this->traceMock->shouldReceive('info')->twice()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());

        $this->merchantIpFilterMock->expects($this->never())->method('authenticateIpForProxyAuth');

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest2Info['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $expectedRes = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        $this->assertEquals($expectedRes, $res);
    }


    public function testAuthorizeUserAccessExceptRBACNonWhitelistedIp()
    {
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::ERROR_RESPONSE_DATA, Mockery::any());
        $expectedError = ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_DASHBOARD_IP_NOT_WHITELISTED);
        $userId = $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID];

        $this->baMock->expects($this->once())->method('getDashboardHeaders')->with()->willReturn(['user_id' => $userId]);
        $this->baMock->expects($this->exactly(2))->method('getUserRole')->with()->willReturn(Role::OWNER);
        $this->roleAccessPolicyMapServiceMock->expects($this->once())->method('getAuthzRolesForRoleId')->with(Role::OWNER);

        $this->authorizeUserAccessExceptRBACCommonExpectations(Product::BANKING, false, $this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_USER_ID], true);

        $this->merchantIpFilterMock->expects($this->once())->method('authenticateIpForProxyAuth')
            ->with($this->dashboardRequest2Info['headers'][RequestHeader::X_DASHBOARD_IP])
            ->willReturn($expectedError);

        $method = $this->getPrivateMethod('RZP\Services\Edge\Service', 'authorizeUserAccessExceptRBAC');
        $res = $method->invokeArgs(new Service($this->dashboardRequest2Info['headers']), [$this->merchantIpFilterMock, $this->roleAccessPolicyMapServiceMock]);
        $this->assertEquals($expectedError, $res);
    }

    // ////////// authorizeUserAccessExceptRBAC test cases end //////////


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
                'getInternalApp', 'fetchAndSetAdminUsingToken', 'setDashboardHeaders', 'checkAndSetAccountScope', 'setProxyTrue', 'isAdminAuth', 'setOrgId', 'getAdmin',
                'getDashboardHeaders', 'setUserAndRoles', 'getUserRole', 'getMerchant', 'getUser', 'getMerchantId'])
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

    protected function mockRoleAccessPolicyMapServiceMock()
    {
        return $this->getMockBuilder(\RZP\Models\RoleAccessPolicyMap\Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getAuthzRolesForRoleId'])
            ->getMock();
    }

    protected function mockMerchantIpFilterMock()
    {
        return $this->getMockBuilder(MerchantIpFilter::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['authenticateIpForProxyAuth'])
            ->getMock();
    }

    protected function getMockMerchantEntity()
    {
        $mock = $this->getMockBuilder(Entity::class)
            ->setConstructorArgs([])
            ->setMethods(['isCACEnabled'])
            ->getMock();

        $mock->setAttribute(Entity::ID, '10000000000000');
        return $mock;

    }

    protected function getAdminEntity(string $orgId, bool $isLocked = false, bool $isDisabled = false)
    {
        $entity = new AdminEntity();
        $entity->setAttribute(AdminEntity::LOCKED, $isLocked);
        $entity->setAttribute(AdminEntity::DISABLED, $isDisabled);
        $entity->setAttribute(AdminEntity::ORG_ID, $orgId);
        return $entity;
    }

    protected function getUserEntity(string $id)
    {
        $entity = new UserEntity();
        $entity->setAttribute(Entity::ID, $id);
        return $entity;
    }


}
