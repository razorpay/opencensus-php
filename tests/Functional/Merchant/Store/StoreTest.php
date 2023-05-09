<?php


namespace Functional\Merchant\Store;


use RZP\Constants\Mode;
use RZP\Models\Merchant\Store;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidPermissionException;
use RZP\Models\User\Role;
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

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $user->getId());

        $this->startTest();
    }
    public function testInvalidPermissionCreateStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchantDetail['merchant_id'], Role::OWNER, Mode::LIVE);
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testValidCreateStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');
        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $user->getId());

        $this->startTest();
    }

    public function testFetchOnboardingStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $data = [
            'namespace'              => 'onboarding',
            'mtu_coupon_popup_count' => '1',
            'gst_details_from_pan'      => '[]'
        ];

        (new Store\Core())->updateMerchantStore($merchantDetail['merchant_id'], $data,Store\Constants::INTERNAL);
        $this->startTest();

        //test permissions in read
        $data = [
            'namespace' => 'onboarding'
        ];
        $data = (new Store\Core())->fetchMerchantStore($merchantDetail['merchant_id'], $data);
        $this->assertArrayNotHasKey(Store\ConfigKey::GST_DETAILS_FROM_PAN, $data);
        $this->assertArrayNotHasKey(Store\ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT, $data);

        $keys = [
            Store\ConfigKey::GST_DETAILS_FROM_PAN
        ];
        $data = (new Store\Core())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                         Store\ConfigKey::ONBOARDING_NAMESPACE,$keys,Store\Constants::INTERNAL);
        $this->assertArrayHasKey(Store\ConfigKey::GST_DETAILS_FROM_PAN, $data);

        //test permissions in read
        $this->expectException(InvalidPermissionException::class);

        $keys = [
            Store\ConfigKey::GST_DETAILS_FROM_PAN
        ];
        $data = (new Store\Core())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                         Store\ConfigKey::ONBOARDING_NAMESPACE,$keys);



    }

    public function testGetUPITerminalProcurementBannerStatus()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $data = [
            'namespace'                                 => 'onboarding',
            'upi_terminal_procurement_status_banner'    => 'no_banner',
        ];

        (new Store\Core())->updateMerchantStore($merchantDetail['merchant_id'], $data);

        $this->startTest();
    }

    public function testStoreUPITerminalProcurementBannerStatus()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $user->getId());

        $this->startTest();
    }

    //This tests the upi_terminal_procurement_banner_status value if no response is received from terminals team within
    // 10 minutes of making a terminal procurement request
    public function testGetUPITerminalBannerStatusForNoKafkaResponseBeyondThreshold()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',
                                                              [
                                                                  'activation_status' => 'activated_mcc_pending'
                                                              ]);

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->fixtures->on('live')->create('state', [
            'entity_id'     => $merchantDetail->getMerchantId(),
            'name'          => 'activated_mcc_pending',
            'entity_type'   => 'merchant_detail',
            'created_at'    =>  1683030415
        ]);

        $this->fixtures->on('test')->create('state', [
            'entity_id'     => $merchantDetail->getMerchantId(),
            'name'          => 'activated_mcc_pending',
            'entity_type'   => 'merchant_detail',
            'created_at'    =>  1683030415
        ]);

        $this->startTest();
    }
}
