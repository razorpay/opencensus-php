<?php

namespace RZP\Tests\Functional\Order\Transfers;

use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Unit\Mock\BasicAuth;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTransferTest extends TestCase
{
    use MocksSplitz;
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OrderTransferTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->initializeTestSetup();
    }

    protected function initializeTestSetup()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }

    public function testCreateOrderTransfers()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderTransferWithPartnerAuthForMarketplace()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->merchant->addFeatures(['route_partnerships'], '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $subMerchantId;

        $this->mockAllSplitzTreatment();

        $response = $this->startTest($testData);

        $transfer = $this->getDbEntityById('transfer', $response['transfers'][0]['id']);

        $this->assertEquals($subMerchantId, $transfer->getMerchantId());

        $order = $this->getDbEntityById('order', $response['id']);

        $publicKeyParts = explode(BasicAuth::PARTNER_CALLBACK_KEY_DELIMITER, $order->getPublicKey());

        $this->assertTrue(BasicAuth::isValidPartnerKey($publicKeyParts[0]));

        $merchantId = Entity::verifyIdAndStripSign($publicKeyParts[1]);

        $this->assertEquals($subMerchantId, $merchantId);
    }

    public function testCreateOrderTransferWithPartnerAuthForInvalidPartnerType()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->fixtures->merchant->addFeatures(['route_partnerships'], '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $subMerchantId;

        $this->mockAllSplitzTreatment();

        $this->startTest($testData);
    }

    public function testCreateOrderTransferWithPartnerAuthForInvalidPartnerMerchantMapping()
    {
        $subMerchantId = $this->setUpPartnerAuthAndGetSubMerchantId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $this->fixtures->merchant->addFeatures(['route_partnerships'], '10000000000000');

        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['merchant_id' => $merchant['id']]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $subMerchantId;

        $this->mockAllSplitzTreatment();

        $this->startTest($testData);

    }

    public function testCreateOrderTransferToSuspendedLinkedAccount()
    {
        $this->fixtures->edit('merchant', '10000000000001', ['suspended_at' => 1642901927]);

        $this->startTest();
    }

    public function testReverseOrderTransfer($order = null)
    {
        if ($order === null)
        {
            $order = $this->testCreateOrderTransfers();
        }

        $payment = $this->capturePaymentProcessOrderTransfers($order);

        $transfer = $this->getLastEntity('transfer', true);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/transfers/' . $transfer['id'] . '/reversals';

        $this->ba->privateAuth();

        $reversal = $this->runRequestResponseFlow($data);

        $this->assertEquals($transfer['id'], $reversal['transfer_id']);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('reversed', $transfer['status']);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals(0, $payment['amount_transferred']);
    }


    protected function capturePaymentProcessOrderTransfers($order, $paymentAmount = null)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];

        if ($paymentAmount !== null)
        {
            $payment['amount'] = $paymentAmount;
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
