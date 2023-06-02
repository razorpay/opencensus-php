<?php

namespace Functional\OneClickCheckout\Config;

use Carbon\Carbon;
use Mockery;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout;

class ConfigTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use MocksSplitz;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ConfigTestData.php';
        parent::setUp();
    }

    public function testFetchMethodsAndOffers()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $this->setupMerchant('10000000000000');
        $this->startTest();
    }

    public function testFetchMethodAndOffersForNon1ccMerchant()
    {
        $this->ba->publicAuth();
        $this->setupMerchant('10000000000000');
        $this->startTest();
    }

    public function testAdminCouponWhitelistWithConfigFlag()
    {
        $this->setupMerchant('10000000000000');
        $this->ba->adminAuth();

        // Setup mock for integration service

        $integrationService = $this->getMockBuilder('RZP\Models\Merchant\OneClickCheckout\IntegrationService\Client')
            ->onlyMethods(['makeMultipartRequest'])
            ->getMock();
        $res = $this->getMockBuilder('\Psr\Http\Message\ResponseInterface')
        ->onlyMethods(['getStatusCode', 'getBody'])
        ->getMockForAbstractClass();
        $res->expects($this->exactly(1))->method('getStatusCode')->willReturn(200);
        $res->expects($this->exactly(1))->method('getBody')->willReturn(json_encode(['errors' => null]));
        $integrationService->expects($this->exactly(1))->method('makeMultipartRequest')->willReturn($res);

        $this->app->instance('integration_service_client', $integrationService);

        $service = new OneClickCheckout\Config\Service();
        $service->adminWhitelistCoupons('10000000000000', ['data' => 'test_coupon']);

        $entity = $this->getDbLastEntity('merchant_1cc_configs');
        $this->assertEquals("one_cc_whitelist_coupons", $entity->getConfig());
    }

    protected function mockIntegrationsService()
    {
        $integrationService = \Mockery::mock('RZP\Models\Merchant\OneClickCheckout\IntegrationService\Client[makeMultipartRequest]');
        $this->app->instance('integration_service_client', $integrationService);
        return $integrationService;
    }

    protected function setupMerchant(string $merchantId)
    {
        $this->fixtures->merchant->disableMethod($merchantId, Method::UPI);
        $this->fixtures->merchant->enableMethod($merchantId, Method::CARD);
        $this->fixtures->merchant->enableMethod($merchantId, Method::NETBANKING);
        $this->fixtures->merchant->enableMethod($merchantId, Method::PAYLATER);
        $this->fixtures->merchant->enableMethod($merchantId, Method::COD);
        $this->fixtures->merchant->enableMethod($merchantId, Method::CARDLESS_EMI);
        $this->fixtures->merchant->enableMethod($merchantId, 'airtelmoney');

        $this->fixtures->create('offer', [
            'name'             => 'Sample Offer',
            'payment_method'   => 'card',
            'starts_at'        => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'merchant_id'      => $merchantId,
            'error_message'    => 'Custom error message',
            'min_amount'       => 100,
            'checkout_display' => 1,
            'active'           => 1,
            'type'             => 'discount',
            'percent_rate'     => 1000,
            'max_cashback'     => 10000,
            'default_offer'    => 1,
        ]);

        $this->mockAllSplitzTreatment([
            "response" => [
                "variant" => [
                    "name" => 'enabled',
                ],
            ],
        ]);
    }
}
