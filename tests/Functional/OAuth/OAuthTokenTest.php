<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthTokenTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected $authServiceMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthTokenTestData.php';

        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->proxyAuth();
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

    public function testRevokeToken()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'tokens/8ckeirnw84ifkg',
                                    'DELETE',
                                    $requestParams);

        $this->startTest();
    }
}
