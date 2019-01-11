<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OriginTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/OriginTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the merchant key.
     */
    public function testCreatePaymentOriginMerchantKey()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndGetPayment($payment);

        $origin = $this->getDbLastEntity('origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($payment['id']);

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the partner credentials.
     */
    public function testCreatePaymentOriginPartnerKey()
    {
        $payment = $this->getDefaultPaymentArray();

        $partnerId = '100000Razorpay';
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);

        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
            ]
        );

        $response = $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);

        $origin = $this->getDbLastEntity('origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($response['razorpay_payment_id']);

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }
}
