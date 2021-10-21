<?php


namespace Functional\Merchant\Store;


use RZP\Models\Merchant\Store;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class StoreTest extends TestCase
{
    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/StoreTestData.php';

        parent::setUp();
    }

    public function testInvalidCreateStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testValidCreateStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function fetchOnboardingStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $data = [
            'namespace'                 => 'onboarding',
            'mtu_coupon_popup_count'    => 1
        ];
        
        (new Store\Core())->updateMerchantStore($merchantDetail['merchant_id'], $data);
        $this->startTest();
    }
}
