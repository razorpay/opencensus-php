<?php

namespace Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantAccountConfigForCheckoutTest extends TestCase
{
    use EntityActionTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAccountConfigForCheckoutTestData.php';

        parent::setUp();
    }

    public function testGetInternalAccountConfigForCheckout(): void
    {
        $merchantId = '1X4hRFHFx4UiXt';

        $this->createMerchant(['id' => $merchantId]);

        $this->fixtures->merchant->edit($merchantId, [
            MerchantEntity::BRAND_COLOR => '123456',
            MerchantEntity::LOGO_URL => '/logos/random_image_original.png',
            MerchantEntity::DISPLAY_NAME => 'Tester Account 2',
            MerchantEntity::PARTNERSHIP_URL => 'https://dummycdn.razorpay.com/logos/partnership.png',
            MerchantEntity::CATEGORY2 => 'ecommerce',
            MerchantEntity::CATEGORY => '5945',
            MerchantEntity::EMAIL => 'test@gmail.com'
        ]);

        $keyEntity = $this->fixtures->create('key', ['merchant_id' => $merchantId]);

        $keyId = $keyEntity->getPublicKey();

        $this->ba->checkoutServiceProxyAuth(Mode::TEST, $merchantId);

        $response = $this->startTest();

        $this->assertEquals($keyId, $response['key']);
    }
    public function testGetInternalFetchConfig(): void
    {
        $this->fixtures->create('org',[
                'id' => 'NMt44KU8fXpHdB',
                'invoice_logo_url' => '/logos/random_image_url.png']
        );

        $merchant = $this->fixtures->create('merchant',
            ['org_id' => 'NMt44KU8fXpHdB']
        );
        $this->fixtures->create('feature',
            ['name' => 'org_custom_branding',
                'entity_id' => $merchant->id,
                'entity_type' => 'merchant'
            ]);

        $this->fixtures->merchant->edit($merchant->id, [
            MerchantEntity::BRAND_COLOR => '123456',
            MerchantEntity::LOGO_URL => '/logos/random_image_original.png',
            MerchantEntity::DISPLAY_NAME => 'Tester Account 2',
            MerchantEntity::PARTNERSHIP_URL => 'https://dummycdn.razorpay.com/logos/partnership.png',
            MerchantEntity::CATEGORY2 => 'ecommerce',
            MerchantEntity::CATEGORY => '5945',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId(),
            'business_registered_address' => "B-85 Bais godam industrial area Bangalore",
            'business_registered_address_l2' => 'Koramangala',
            'business_registered_country'     => 'India',
            'business_registered_state' => 'Karnataka',
            'business_registered_city' =>'Bangalore',
            'business_registered_pin' => '560029',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();

    }
    protected function createMerchant($attributes = [])
    {
        $this->ba->adminAuth();

        $defaultAttributes = [
            'id'    => '1X4hRFHFx4UiXt',
            'name'  => 'Tester 2',
            'email' => 'liveandtest@localhost.com'
        ];

        $merchant = array_merge($defaultAttributes, $attributes);

        $request = [
            'content' => $merchant,
            'url' => '/merchants',
            'method' => 'POST'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->assertArraySelectiveEquals($merchant, $content);

        return $content;
    }
}
