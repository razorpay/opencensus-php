<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthApplicationTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected $authServiceMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthApplicationTestData.php';

        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->proxyAuth();
    }

    public function testCreateApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
                       'name'     => 'fdsfsd',
                       'website'  => 'https://www.example.com',
                       'logo_url' => '/logo/app_logo.png',
                    ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'POST',
                                    $requestParams);

        $this->startTest();
    }

    public function testGetApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'applications/8ckeirnw84ifke',
                                    'GET',
                                    $requestParams);

        $this->startTest();
    }

    public function testGetMultipleApplications()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'GET',
                                    $requestParams);

        $this->startTest();
    }

    public function testUpdateApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $requestParams['name'] = 'apptestnew';

        $this->setAuthServiceMockDetail(
                                    'applications/8ckeirnw84ifke',
                                    'PATCH',
                                    $requestParams);

        $this->startTest();
    }

    public function testDeleteApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'applications/8ckeirnw84ifke',
                                    'DELETE',
                                    $requestParams);

        $this->startTest();
    }
}
