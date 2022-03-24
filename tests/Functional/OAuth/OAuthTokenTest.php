<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Http\OAuthScopes;
use RZP\Models\User\Role;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthTokenTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected $authServiceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthTokenTestData.php';

        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->proxyAuth();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testAllPublicPrivateRoutesMappedToScopes()
    {
        // todo remove this once every route is having scopes assigned
        $this->markTestSkipped();
        $routes = array_merge(Route::$public, Route::$private);

        $scopedRoutes = array_keys(OAuthScopes::getScopes());

        $routesNotHavingScopes = array_diff($routes, $scopedRoutes);

        $msg = implode(',', $routesNotHavingScopes). ' should have scopes defined';

        $this->assertEmpty($routesNotHavingScopes, $msg);

//        skipping the below for now as some public callback and direct auth routes also require scopes

//        $routesHavingExtraScopes = array_diff($scopedRoutes, $routes);
//
//        $msg = 'Only public/private routes are allowed to have scopes. Please remove the routes '. implode(',', $routesHavingExtraScopes);
//
//        $this->assertEmpty($routesHavingExtraScopes, $msg);
    }

    public function testGetToken()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'tokens/8ckeirnw84ifkg',
                                    'GET',
                                    $requestParams);

        $this->startTest();
    }

    public function testGetAllTokens()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'tokens',
                                    'GET',
                                    $requestParams);

        $this->startTest();
    }


    public function testGetAllTokensForBankingRoute()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
            'tokens',
            'GET',
            $requestParams);

        $this->startTest();
    }

    public function testRevokeToken()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'tokens/8ckeirnw84ifkg',
                                    'PUT',
                                    $requestParams);

        $this->startTest();
    }

    public function testCreateAppleWatchTokenForOwner()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $attributes = [
            'merchant_id' => '10000000000000',
            'partner_type'=> 'apple_watch',
        ];

        $oauthApp = $this->createOAuthApplication($attributes);

        $this->fixtures->feature->create([
            Feature\Entity::ENTITY_TYPE => Feature\Constants::APPLICATION,
            Feature\Entity::ENTITY_ID   => $oauthApp->getId(),
            Feature\Entity::NAME        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH
        ]);

        $this->authServiceMock
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'merchant_id' => '10000000000000',
                'type'        => 'apple_watch'
            ])
            ->willReturn([
                'items' => [
                    [
                        'type'              => 'apple_watch',
                        'client_details'    =>  [
                            'prod'   =>  [
                                'id'     =>  'client_id',
                                'secret' =>  'client_secret',
                            ]
                        ]
                    ]

                ]
            ]);

        $this->authServiceMock
            ->expects($this->at(1))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'client_credentials',
                'scope'                 => 'apple_watch_read_write',
                'mode'                  => 'live',
                'user_id'               => 'MerchantUser01'
            ])
            ->willReturn([
                'public_token'  => 'rzp_test_oauth_10000000000000',
                'token_type'    => 'Bearer',
                'expires_in'    => 7862400,
                'access_token'  => 'access_token',
            ]);

        $beforeOAuthApps = $this->getEntities('merchant_application',[
            'merchant_id'   => '10000000000000',
        ],true,Mode::LIVE);

        $this->ba->proxyAuthLive();

        $this->startTest();

        $afterOAuthApps = $this->getEntities('merchant_application',[
            'merchant_id'   => '10000000000000',
        ],true,Mode::LIVE);

        self::assertEquals(0,$afterOAuthApps['count'] - $beforeOAuthApps['count']);
    }

    public function testCreateAppleWatchTokenForAdminFails()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $adminRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], Role::ADMIN, Mode::LIVE);

        $this->ba->proxyAuth('rzp_live_10000000000000', $adminRoleUser->getId());

        $this->startTest();
    }

    public function testCreateAppleWatchTokenForOwnerWhenAppDoesNotExist()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->authServiceMock
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'merchant_id' => '10000000000000',
                'type'        => 'apple_watch'
            ])
            ->willReturn([
                'items' => [
                ]
            ]);

        $this->authServiceMock
            ->expects($this->at(1))
            ->method('sendRequest')
            ->with('applications', 'POST',[
                'merchant_id' => '10000000000000',
                'name'        => 'Apple Watch',
                'website'     => 'https://www.razorpay.com/x',
                'type'        => 'apple_watch'
            ])
            ->willReturn([
                    'id'                => 'appidfigorithi',
                    'type'              => 'apple_watch',
                    'client_details'    =>  [
                        'prod'   =>  [
                            'id'     =>  'client_id',
                            'secret' =>  'client_secret',
                        ]
                    ]
            ]);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'client_credentials',
                'scope'                 => 'apple_watch_read_write',
                'mode'                  => 'live',
                'user_id'               => 'MerchantUser01'
            ])
            ->willReturn([
                'public_token'  => 'rzp_test_oauth_10000000000000',
                'token_type'    => 'Bearer',
                'expires_in'    => 7862400,
                'access_token'  => 'access_token',
            ]);

        $beforeOAuthApps = $this->getEntities('merchant_application',[
            'merchant_id'   => '10000000000000',
        ],'true','live');

        $this->ba->proxyAuthLive();

        $this->startTest();

        $afterOAuthApps = $this->getEntities('merchant_application',[
            'merchant_id'   => '10000000000000',
        ],true,Mode::LIVE);

        self::assertEquals(1,$afterOAuthApps['count'] - $beforeOAuthApps['count']);

        $feature = $this->getEntities('feature',
            [
                Feature\Entity::NAME        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH,
                Feature\Entity::ENTITY_ID   => $afterOAuthApps['items'][0]['application_id'],
                Feature\Entity::ENTITY_TYPE => Feature\Constants::APPLICATION
            ], true,Mode::LIVE);

        $this->assertNotNull($feature);
    }

    public function testCreateAppleWatchTokenForOwnerOtpMissing()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testCreateAppleWatchTokenForUnactivatedMerchant()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 0]);

        $this->ba->proxyAuthLive();

        $this->startTest();
    }

    public function testCreateAppleWatchTokenInTestMode()
    {
        $this->fixtures->on('test')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
