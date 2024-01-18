<?php

namespace Functional\Merchant\Products;

use Mail;
use RZP\Constants\Mode;
use RZP\Models\User\Role;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;

class LOCConfigTest extends TestCase
{
    use MocksSplitz;
    use PartnerTrait;
    use WebhookTrait;
    use TerminalTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/LOCConfigTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->fixtures->connection('test')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.razorpay.com/terms/'], 'business_unit' => 'payments']);
        $this->fixtures->connection('live')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.razorpay.com/terms/'], 'business_unit' => 'payments']);

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('sendWhatsappMessage')->andReturn([]);
    }

    public function testCreateLOCProductConfig()
    {
        //Mail::fake();

        list($partner, $app) = $this->setupPrivateAuthForPartner();

        $this->fixtures->merchant->addFeatures(['loc_subm_onboarding_api'], $partner->getId());

        $this->mockCapitalPartnershipSplitzExperiment($partner->getId());

        $testData = $this->testData['createAccountV2ForMandatoryFilledByCapitalPartner'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        // assert that LOS Service gets one request to get product list and one request to create capital application
        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetNoApplicationBulkRequestOnLOSService($losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $testData = $this->testData['testCreateLOCProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products' ;

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateLOCProductConfigWhenLOCAppAlreadyExists()
    {
        Mail::fake();

        list($partner, $app) = $this->setupPrivateAuthForPartner();

        $this->fixtures->merchant->addFeatures(['loc_subm_onboarding_api'], $partner->getId());

        $this->mockCapitalPartnershipSplitzExperiment($partner->getId());

        $testData = $this->testData['createAccountV2ForMandatoryFilledByCapitalPartner'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        // assert that LOS Service gets one request to get product list and one request to create capital application
        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetApplicationBulkRequestByMerchantIdOnLOSService($losServiceMock, substr($accountId, 4));

        $this->mockCreateApplicationRequestOnLOSServiceNegative($losServiceMock);

        $testData = $this->testData['testCreateLOCProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products' ;

        $this->runRequestResponseFlow($testData);
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication(
            [
                'partner_type' => 'reseller'
            ]);
        $this->fixtures->merchant->activate($partner->getId());

        $this->fixtures->user->createUserForMerchant($partner->getId(), [], Role::OWNER, Mode::LIVE);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return [$partner, $key];
    }
}
