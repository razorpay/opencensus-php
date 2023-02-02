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
}
