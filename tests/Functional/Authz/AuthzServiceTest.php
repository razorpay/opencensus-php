<?php


namespace Functional\Authz;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;



class AuthzServiceTest extends TestCase
{

    use RequestResponseFlowTrait;
    use TestsBusinessBanking;
    use DbEntityFetchTrait;


    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AuthzServiceTestData.php';
        parent::setUp();
        $this->app['config']->set('applications.authz.mock', true);
        $this->ba->proxyAuth();
        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    /*
    public function testFetchActionList()
    {
        $this->ba->adminAuth();
        $response = $this->startTest();
        $this->assertEquals($response['items'][0]['id'], 'FuEmr64NaJWKG7');
    }
    */
}
