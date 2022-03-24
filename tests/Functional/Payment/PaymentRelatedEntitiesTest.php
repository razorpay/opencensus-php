<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PaymentRelatedEntitiesTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $emiPlan;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentRelatedEntitiesTestData.php';

        parent::setUp();

        $this->ba->appAuth('rzp_test','RANDOM_CPS_SECRET');
    }

    public function testFetchOrdersEntity()
    {
        $order = $this->fixtures->create('order');

        $this->testData[__FUNCTION__]['request']['url'] = '/entities/order/' . $order['id'];

        $data = $this->startTest();

        $this->assertEquals('order_'.$order['id'], $data['order']['id']);
    }

    public function testFetchOrdersEntityInvalidId()
    {
        $this->testData[__FUNCTION__]['request']['url'] = '/entities/order/' . '1';

        $data = $this->startTest();
    }

    public function testFetchMerchantsEntity()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['url'] = '/entities/merchant/' . $merchant['id'];

        $data = $this->startTest();

        $this->assertEquals($merchant['id'], $data['merchant']['id']);
    }

    public function testFetchTokenEntity()
    {
        $token = $this->fixtures->create('token');

        $this->testData[__FUNCTION__]['request']['url'] = '/entities/token/' . $token['id'];

        $data = $this->startTest();

        $this->assertEquals('token_'.$token['id'], $data['token']['id']);
    }

    public function testFetchFeaturesEntity()
    {
        $feature = $this->fixtures->create('feature', [
            'name'      => 'entity_fetch_test_feature',
            'entity_id' => '10000000000000',
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/entities/feature/' . $feature['id'];

        $data = $this->startTest();

        $this->assertEquals($feature['id'], $data['feature']['id']);
    }

    public function testFetchIinsEntity()
    {
        // iin 411111 is already seeded. Searching for that iin
        $this->testData[__FUNCTION__]['request']['url'] = '/entities/iin/411111';

        $data = $this->startTest();
    }

    public function testCreateCardEntityWithToken()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->create('merchant');
        $customer = $this->fixtures->create('customer');

        $this->testData[__FUNCTION__]['request']['content']['card']['merchant_id'] = $merchant['id'];
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = $customer['id'];

        $response = $this->startTest();

        $this->assertEquals($response['card']['id'], 'card_'.$response['token']['card_id']);
    }

    public function testCreateCardEntityWithoutToken()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['content']['card']['merchant_id'] = $merchant['id'];

        $response = $this->startTest();

        $this->assertEquals($merchant['id'],$response['card']['merchant_id']);
    }

    public function testPaymentMetaSearch()
    {
        $attributes = [
            'payment_id' => 'J4xTrMIbNo41ac',
            'action_type' => 'capture',
            'reference_id' => 'G3MVX2B2smmDShAna3xPNum',
        ];

        $this->fixtures->create('payment_meta', $attributes);

        $this->ba->expressAuth();

        $this->testData[__FUNCTION__]['request']['content'] = [
            "reference_id" => "G3MVX2B2smmDShAna3xPNum",
            "action_type" => "capture"
        ];

        $data = $this->startTest();

        $this->assertCount(1, $data);
        $this->assertEquals('J4xTrMIbNo41ac', $data[0]['payment_id']);

        $this->testData[__FUNCTION__]['request']['content'] = [
            "reference_id" => "G3MVX2B2smmDShAna3xPNu",
            "action_type" => "capture"
        ];

        $data = $this->startTest();

        $this->assertCount(0, $data);

        $attributes['payment_id'] = 'J4xTrMIbNo41ad';

        $this->fixtures->create('payment_meta', $attributes);

        $attributes['payment_id'] = 'J4xTrMIbNo41ae';

        $this->fixtures->create('payment_meta', $attributes);

        $this->testData[__FUNCTION__]['request']['content'] = [
            "reference_id" => "G3MVX2B2smmDShAna3xPNum",
            "action_type" => "capture"
        ];

        $data = $this->startTest();

        $this->assertCount(3, $data);

        $this->testData[__FUNCTION__]['request']['content'] = [
            "reference_id" => "G3MVX2B2smmDShAna3xPNum",
            "action_type" => "capture",
            "fetch_last" => true
        ];

        $data = $this->startTest();

        $this->assertCount(1, $data);
        $this->assertEquals('J4xTrMIbNo41ae', $data[0]['payment_id']);

        $this->testData[__FUNCTION__]['request']['content'] = [
            "reference_id" => "G3MVX2B2smmDShAna3xPNu",
            "action_type" => "capture",
            "fetch_last" => true
        ];

        $data = $this->startTest();

        $this->assertCount(0, $data);
    }
}
