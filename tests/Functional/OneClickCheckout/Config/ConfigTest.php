<?php

namespace Functional\OneClickCheckout\Config;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Offer;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

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
