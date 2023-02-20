<?php

namespace RZP\Tests\Functional\OAuth;

use Illuminate\Database\Eloquent\Factory;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class OAuthApplicationTest extends TestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $authServiceMock;

    protected function setUp(): void
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

        $res = $this->setAuthServiceMockDetail(
                                        'applications',
                                        'POST',
                                        $requestParams,
                                        1,
                                        ['id' => '8ckeirnw84ifke']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'pure_platform']);

        // TODO: Enable post migrations
        //$this->markPartner();

        $this->startTest();

        $partnerConfig = $this->getDbEntities('partner_config');

        $partnerConfig->toArray();

        $this->assertEquals($partnerConfig[0]['entity_id'], '8ckeirnw84ifke');

        $this->assertEquals($partnerConfig[0]['entity_type'], 'application');
    }

    public function testCreatePartnerApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => 'fdsfsd',
            'website'  => 'https://www.example.com',
            'logo_url' => '/logo/app_logo.png',
            'type'     => 'partner',
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'POST',
                                    $requestParams);

        $this->markPartner('fully_managed');

        $this->startTest();
    }

    public function testCreatePartnerApplicationPurePlatform()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => 'fdsfsd',
            'website'  => 'https://www.example.com',
            'logo_url' => '/logo/app_logo.png',
            'type'     => 'partner',
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'POST',
                                    $requestParams,
                                    0);

        $this->markPartner();

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

        $this->markPartner();

        $this->startTest();
    }

    public function testGetPartnerApplicationPurePlatform()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'GET',
                                    $requestParams,
                                    0);

        $this->markPartner();

        $this->startTest();
    }

    public function testGetPartnerApplicationBank()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail(
                                    'applications',
                                    'GET',
                                    $requestParams,
                                    0);

        $this->markPartner('bank');

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

    public function testUpdateApplicationTypeFail()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $requestParams['name'] = 'apptestnew';

        $requestParams['type'] = 'tally';

        $this->startTest();
    }

    public function testDeleteApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $merchantId='10000000000000';

        $accessMap = $this->fixtures->create('merchant_access_map',
            [
                'id'        => 'CMe2wjY0hiWBrL',
                'entity_id' => '8ckeirnw84ifke',
                'entity_type' => 'application',
            ]);

        $this->createMerchantApplication($merchantId, 'aggregator', '8ckeirnw84ifke');

        $this->setAuthServiceMockDetail(
                                    'applications/8ckeirnw84ifke',
                                    'PUT',
                                    $requestParams);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $this->startTest();

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['id' => $accessMap->getId()], 'live');
        $this->assertNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['id' => $accessMap->getId()], 'test');
        $this->assertNull($accessMapEntity);
    }

    protected function markPartner(string $type = 'pure_platform', string $merchantId = '10000000000000')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $type]);

        if ($type !== 'pure_platform')
        {
            $this->createOAuthApplication(['merchant_id' => $merchantId, 'type' => 'partner', 'partner_type' => $type]);
        }
    }

    public function testRefreshClients()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->createOAuthApplication(['id' => '8ckeirnw84ifke', 'type' => 'partner', 'partner_type'=> 'aggregator']);

        $createParams = [
            'application_id' => '8ckeirnw84ifke'
        ];

        $mergedRequestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail('clients', 'PUT', $mergedRequestParams, 1,
           ['id' => '8ckeirnw84ifke',
               "client_details" =>[
                   "dev"=>[
                       "id"=>"randomDev"
                   ],
                   "prod"=>[
                       "id"=>"randomProd"
                   ],
           ]]);
        // can remove this when proxy auth is allowed for this route
        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRefreshClientsWithError()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->createOAuthApplication(['id' => '8ckeirnw84ifke', 'type' => 'partner', 'partner_type'=> 'aggregator']);

        $createParams = [
            'application_id' => '8ckeirnw84ifke'
        ];

        $mergedRequestParams = array_merge($requestParams, $createParams);

        $this->authServiceMock
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->with('clients', 'PUT', $mergedRequestParams)
            ->will($this->throwException(new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE
            )));

        // can remove this when proxy auth is allowed for this route
        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $this->startTest();
    }
}
