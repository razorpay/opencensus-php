<?php

namespace RZP\Tests\Functional\CheckoutOrder;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Checkout\Order\Entity;

class CheckoutOrdersTest extends TestCase
{
    use MocksRedisTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;

    protected $testDataFilePath = __DIR__ . '/helpers/CheckoutOrdersTestData.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->on(Mode::LIVE)->create('terminal:bharat_qr_terminal');

        $this->fixtures->on(Mode::TEST)->create('terminal:bharat_qr_terminal');

        $this->fixtures->on(Mode::LIVE)->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on(Mode::TEST)->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on(Mode::LIVE)->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on(Mode::TEST)->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testCreateCheckoutOrder(): void
    {
        $response = $this->createCheckoutOrder();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->assertAmountAndQrId($response['qr_code']['image_content']);

        $this->assertEquals(
            'https://api.razorpay.com/v1/checkout/qr_code/' . $response['qr_code']['id'] . '/payment/status?key_id=' . $this->ba->getKey(),
            $response['request']['url']
        );

        $checkoutOrderExpiry = $response['expire_at'];
        $qrCodeExpiry = $response['qr_code']['close_by'];
        $this->assertEquals($checkoutOrderExpiry, $qrCodeExpiry);

        //asserting that qr expiry is 12 minutes
        $this->assertLessThan(10, abs($qrCodeExpiry - Carbon::now()->addMinutes(12)->getTimestamp()));

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $response['qr_code']['id']);

        //CheckoutOrder Entity Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $checkoutOrder['merchant_id']);
        $this->assertEquals('+919876543210', $checkoutOrder['contact']);
        $this->assertEquals('abc@exmple.com', $checkoutOrder['email']);
        $this->assertCheckoutOrderMetadata($checkoutOrder);

        //QrCode Entity Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $qrCode['merchant_id']);
        $this->assertEquals($checkoutOrder['id'], $qrCode['entity_id']);
        $this->assertEquals(Entity::class, $qrCode['entity_type']);
        $this->assertEquals('checkout', $qrCode['request_source']);
    }

    public function testCreateCheckoutOrderWithPartnerAuth(): void
    {
        [$response,$client] = $this->createCheckoutOrderForPartner();

        $expectedResponse = $this->testData['testCreateCheckoutOrder'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->assertAmountAndQrId($response['qr_code']['image_content']);

        $checkoutOrderExpiry = $response['expire_at'];
        $qrCodeExpiry = $response['qr_code']['close_by'];
        $this->assertEquals($checkoutOrderExpiry, $qrCodeExpiry);

        $entityOriginEntity = $this->getLastEntity('entity_origin', true);

        //Entity origin creation
        $this->assertEquals('application', $entityOriginEntity['origin_type']);
        $this->assertEquals($client->getApplicationId(), $entityOriginEntity['origin_id']);
    }

    public function testCreateCheckoutOrderWithOrderIdAndCustomerId(): void
    {
        $this->fixtures->create('order', ['amount' => 5000]);
        $this->fixtures->create('customer');

        $order = $this->getDbLastEntity('order');
        $customer = $this->getDbLastEntity('customer');

        $response = $this->createCheckoutOrder(
            [
                'order_id' => $order['id'],
                'customer_id' => $customer['id'],
            ]
        );

        $expectedResponse = $this->testData['testCreateCheckoutOrder'];
        $expectedResponse['order_id'] = 'order_' . $order['id'];
        $expectedResponse['qr_code']['payment_amount'] = 5000;
        $expectedResponse['qr_code']['customer_id'] = 'cust_' . $customer['id'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $response['qr_code']['id']);

        // this is failing as 4000 is stored in checkout meta_data amount
        // $this->assertEquals(5000, $checkoutOrder['meta_data']['amount']);

        $this->assertEquals(5000, $qrCode['amount']);
        $this->assertEquals($customer['id'], $checkoutOrder['meta_data']['customer_id']);
        $this->assertEquals($customer['id'], $qrCode['customer_id']);

        $response = $this->createCheckoutOrder(
            [
                'order_id' => 'order_' . $order['id'],
                'customer_id' => 'cust_' . $customer['id'],
            ]
        );

        $this->assertEquals('order_' . $order['id'], $response['order_id']);
        $this->assertEquals('cust_' . $customer['id'], $response['qr_code']['customer_id']);
    }

    public function testCreateCheckoutOrderWithOrderIdFailedWhenOrderIsPaidOrOrderIdNotPresent(): void
    {
        $this->fixtures->create('order', [
            'amount' => 5000,
            'status' => 'paid',
        ]);
        $order = $this->getDbLastEntity('order');

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('Order already paid. Cannot create CheckoutOrder on paid orders.');

        $this->createCheckoutOrder(['order_id' => $order['id']]);

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The id provided does not exist');

        $this->createCheckoutOrder(['order_id' => 'abcdefi1234567']);
}

    public function testCreateCheckoutOrderShouldFailForTpvMerchantWithoutOrder(): void
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED);

        $this->fixtures->merchant->addFeatures(['tpv']);

        $this->createCheckoutOrder();
    }

    public function testCreateCheckoutOrderShouldFailWithExtraInputParameters(): void
    {
        $this->expectException(ExtraFieldsException::class);
        $this->expectExceptionMessage('fee is/are not required and should not be sent');

        $this->createCheckoutOrder(['fee' => 100]);
    }

    /**
     * We create a checkoutOrder with qrCode
     * Mock gateway callback to make the payment on qrCode
     * Once the payment is done,
     *  1. qrPayment should be created with expected = 1
     *  2. qrCode should be closed with reason paid
     *  3. checkoutOrder should be marked paid
     *  4. payment should be created and marked paid with correct amount
     *  5. paymentAnalytics should be created with the correct metadata such as library, device, etc
     *  6. upiMetadata should be created with flow as intent
     */
    public function testCheckoutOrderSuccessPayment(): void
    {
        $response = $this->createCheckoutOrder();

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');

        //CheckoutOrder Assertions
        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('paid', $checkoutOrder['close_reason']);
        $this->assertNotNull($checkoutOrder['closed_at']);

        //QrCode Assertions
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertNotNull($qrCode['closed_at']);
        $this->assertEquals($qrCode['amount'], $qrCode['payments_amount_received']);
        $this->assertEquals(1, $qrCode['payments_received_count']);

        //QrPayment Assertions
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['amount'], $qrPayment['amount']);

        //Payment Assertions
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals($qrCode['amount'], $payment['amount']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($checkoutOrder['meta_data']['description'], $payment['description']);
        $this->assertEquals('random@icici', $payment['vpa']);
        $this->assertEquals($checkoutOrder['email'], $payment['email']);
        $this->assertEquals($checkoutOrder['contact'], $payment['contact']);
        $this->assertEquals('upi_icici', $payment['gateway']);
        $this->assertArraySelectiveEquals(
            $checkoutOrder['meta_data']['notes'],
            json_decode($payment->getNotesJson(), true)
        );

        //PaymentAnalytics Assertions
        $paymentAnalytics = $this->getDbLastEntity('payment_analytics');

        $this->assertEquals($payment['id'], $paymentAnalytics['payment_id']);
        $this->assertEquals($checkoutOrder['checkout_id'], $paymentAnalytics['checkout_id']);
        $this->assertEquals($checkoutOrder['meta_data']['_']['library'], $paymentAnalytics['library']);
        $this->assertEquals('chrome', $paymentAnalytics['browser']);
        $this->assertEquals('107.0.0.0', $paymentAnalytics['browser_version']);
        $this->assertEquals('macos', $paymentAnalytics['os']);
        $this->assertEquals('10_15_7', $paymentAnalytics['os_version']);
        $this->assertEquals('desktop', $paymentAnalytics['device']);
        $this->assertEquals($checkoutOrder['meta_data']['_']['platform'], $paymentAnalytics['platform']);
        $this->assertEquals($checkoutOrder['meta_data']['_']['referer'], $paymentAnalytics['referer']);
        $this->assertEquals($checkoutOrder['meta_data']['user_agent'], $paymentAnalytics['user_agent']);
        $this->assertEquals($checkoutOrder['meta_data']['ip'], $paymentAnalytics['ip']);
        $this->assertEquals($checkoutOrder['meta_data']['_']['device_id'], $paymentAnalytics['virtual_device_id']);

        //UpiMetadata assertions
        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertEquals($payment['id'], $upiMetadata['payment_id']);
        $this->assertEquals('intent', $upiMetadata['flow']);
        $this->assertEquals('default', $upiMetadata['type']);
        $this->assertEquals('upi_qr', $upiMetadata['mode']);
    }

    /**
     * Ensure that default QrCode Pricing from Plan Id: 'A8UwvIbaL8n4Q8' isn't
     * applied to auto-captured QrV2 payments originating from standard checkout.
     *
     * @return void
     */
    public function testDefaultQrCodePricingIsNotChargedForCheckoutOrderQrCodePaymentsInAutoCaptureMode(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $defaultQrPricingPlan = [
            'plan_id'             => Fee::DEFAULT_QR_CODE_PLAN_ID,
            'plan_name'           => 'TestDefaultQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'fee_bearer'          => 'platform',
            'receiver_type'       => 'qr_code',
            'percent_rate'        => 99, // 99 base points i.e. 0.99%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $defaultQrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $order = $this->fixtures->create('order', [
            'amount' => 100000,
            'payment_capture' => 1,
        ]);

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount'] / 100;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payment->getTransactionId()]);

        // Payment assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals(0, $payment->getFee());
        $this->assertEquals(0, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(0, $feeBreakup[0]['amount']);
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(0, $feeBreakup[1]['amount']);
    }

    /**
     * Ensure that default QrCode Pricing from Plan Id: 'A8UwvIbaL8n4Q8' isn't
     * applied to QrV2 payments originating from standard checkout for merchants
     * who manual capture payments.
     *
     * @return void
     */
    public function testDefaultQrCodePricingIsNotChargedForCheckoutOrderQrCodePaymentsInManualCaptureMode(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $defaultQrPricingPlan = [
            'plan_id'             => Fee::DEFAULT_QR_CODE_PLAN_ID,
            'plan_name'           => 'TestDefaultQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'fee_bearer'          => 'platform',
            'receiver_type'       => 'qr_code',
            'percent_rate'        => 99, // 99 base points i.e. 0.99%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $defaultQrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $order = $this->fixtures->create('order', ['amount' => 100000]);

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount'] / 100;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $payment->getPublicId() . '/capture',
            'content' => ['amount' => 100000, 'currency' => 'INR'],
        ];

        $this->ba->privateAuth();
        // Manual Capture Payment
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('fee', $content);
        $this->assertArrayHasKey('tax', $content);

        $this->assertEquals('captured', $content['status']);
        $this->assertEquals(100000, $content['amount']);
        $this->assertEquals(0, $content['fee']);
        $this->assertEquals(0, $content['tax']);

        $payment->refresh();
        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payment->getTransactionId()]);
        // Payment assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals(0, $payment->getFee());
        $this->assertEquals(0, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(0, $feeBreakup[0]['amount']);
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(0, $feeBreakup[1]['amount']);
    }

    /**
     * Ensure that if a merchant has QrCode pricing defined in their pricing
     * plan even then the default UPI pricing is only applied & QrV2 pricing
     * isn't considered for auto-captured QrV2 payments originating from checkout.
     *
     * @return void
     */
    public function testMerchantSpecificQrCodePricingIsNotChargedForCheckoutOrderQrCodePaymentsInAutoCaptureMode(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 165, // 165 base points i.e. 1.65%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $order = $this->fixtures->create('order', [
            'amount' => 100000,
            'payment_capture' => 1,
        ]);

        $response = $this->createCheckoutOrder([
            'order_id' => $order['id'],
            'amount' => 100000,
        ]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount'] / 100;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payment->getTransactionId()]);
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 2.00%
        $this->assertEquals(2360, $payment->getFee());
        $this->assertEquals(360, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(2000, $feeBreakup[0]['amount']); // 2.00% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(360, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 2000
    }

    /**
     * Ensure that if a merchant has QrCode pricing defined in their pricing
     * plan even then the default UPI pricing is only applied & QrV2 pricing
     * isn't considered for QrV2 payments originating from checkout for
     * merchants who manually capture payments.
     *
     * @return void
     */
    public function testMerchantSpecificQrCodePricingIsNotChargedForCheckoutOrderQrCodePaymentsInManualCaptureMode(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 165, // 165 base points i.e. 1.65%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $order = $this->fixtures->create('order', ['amount' => 100000]);

        $response = $this->createCheckoutOrder([
            'order_id' => $order['id'],
            'amount' => 100000,
        ]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount'] / 100;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $payment->getPublicId() . '/capture',
            'content' => ['amount' => 100000, 'currency' => 'INR'],
        ];

        $this->ba->privateAuth();
        // Manual Capture Payment
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('fee', $content);
        $this->assertArrayHasKey('tax', $content);

        $this->assertEquals('captured', $content['status']);
        $this->assertEquals(100000, $content['amount']);
        $this->assertEquals(2360, $content['fee']);
        $this->assertEquals(360, $content['tax']);

        $payment->refresh();
        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payment->getTransactionId()]);
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 2.00%
        $this->assertEquals(2360, $payment->getFee());
        $this->assertEquals(360, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(2000, $feeBreakup[0]['amount']); // 2.00% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(360, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 2000
    }

    public function testCheckoutOrderPaymentWithCustomerId(): void
    {
        $this->fixtures->create('customer');
        $customer = $this->getDbLastEntity('customer');

        $response = $this->createCheckoutOrder(['customer_id' => $customer['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['amount'], $qrPayment['amount']);

        //Payment Assertions
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['amount'], $payment['amount']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('random@icici', $payment['vpa']);
        $this->assertEquals($checkoutOrder['email'], $payment['email']);
        $this->assertEquals($checkoutOrder['contact'], $payment['contact']);
        $this->assertEquals('upi_icici', $payment['gateway']);

        $this->assertEquals($customer['id'], $payment['customer_id']);
    }

    public function testCheckoutOrderPaymentWithOrderId(): void
    {
        $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);
        $order = $this->getDbLastEntity('order');

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();

        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['amount'], $qrPayment['amount']);


        //Payment Assertions
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['amount'], $payment['amount']);
        $this->assertEquals('upi', $payment['method']);
//        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('random@icici', $payment['vpa']);
        $this->assertEquals($checkoutOrder['email'], $payment['email']);
        $this->assertEquals($checkoutOrder['contact'], $payment['contact']);
        $this->assertEquals('upi_icici', $payment['gateway']);

        //Order Assertions
        $this->assertEquals($order['amount'], $qrCode['amount']);
        $this->assertEquals($order['amount'], $qrPayment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(1, $order['authorized']);
        $this->assertEquals(5000, $order['amount_paid']);
    }

    public function testCheckoutOrderPaymentWithInvoice(): void
    {
        $order = $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);

        $invoice = $this->fixtures->create('invoice',[
            'amount' => $order['amount'],
            'order_id' => $order['id'],
        ]);

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();
        $invoice->reload();

        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['amount'], $qrPayment['amount']);


        //Payment Assertions
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['amount'], $payment['amount']);
        $this->assertEquals('upi', $payment['method']);
//        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('random@icici', $payment['vpa']);
        $this->assertEquals($checkoutOrder['email'], $payment['email']);
        $this->assertEquals($checkoutOrder['contact'], $payment['contact']);
        $this->assertEquals('upi_icici', $payment['gateway']);

        //Order Assertions
        $this->assertEquals($order['amount'], $qrCode['amount']);
        $this->assertEquals($order['amount'], $qrPayment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(1, $order['authorized']);
        $this->assertEquals(5000, $order['amount_paid']);

        //Invoice assertions
        $this->assertEquals($order['amount'], $invoice['amount']);
        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($order['amount'], $invoice['amount_paid']);
        $this->assertEquals('pay_' . $payment['id'], $invoice['payment_id']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pay_' . $payment['id'], $response['razorpay_payment_id']);
        // TODO: Does fetch status API not return order in invoice payment response?
//        $this->assertEquals('order_' . $order['id'], $response['razorpay_order_id']);
        $this->assertEquals('inv_' . $invoice['id'], $response['razorpay_invoice_id']);
        $this->assertEquals('paid', $response['razorpay_invoice_status']);
        $this->assertArrayHasKey('razorpay_invoice_receipt', $response);
        $this->assertNotNull($response['razorpay_signature']);
    }

    public function testPaymentShouldNotBeAutoCapturedIfOrderCaptureFlagNotSetToTrue(): void
    {
        //Not setting payment_capture to true here
        $this->fixtures->create('order', [
            'amount' => 5000,
        ]);
        $order = $this->getDbLastEntity('order');

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();

        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals($qrCode['id'], $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['amount'], $qrPayment['amount']);

        //Payment Assertions
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['amount'], $payment['amount']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('random@icici', $payment['vpa']);
        $this->assertEquals($checkoutOrder['email'], $payment['email']);
        $this->assertEquals($checkoutOrder['contact'], $payment['contact']);
        $this->assertEquals('upi_icici', $payment['gateway']);

        //Order Assertions
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(1, $order['authorized']);
        $this->assertEquals(0, $order['amount_paid']);

        $this->ba->publicAuth();
        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pay_' . $payment['id'], $response['razorpay_payment_id']);
        $this->assertEquals('order_' . $order['id'], $response['razorpay_order_id']);
        $this->assertNotNull($response['razorpay_signature']);
    }

    public function testShouldCreateUnexpectedPaymentWhenPaymentIsDoneOnClosedCheckoutOrder(): void
    {
        /**
         * Create checkout order
         * Close checkout order
         * Then do payment, it should come as unexpected payment
         */

        $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);
        $order = $this->getDbLastEntity('order');

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        $request = [
            'method'  => 'DELETE',
            'url'     => '/checkout/order/' . $checkoutOrder['id'],
            'content' => ['close_reason' => 'opt_out']
        ];
        $response = $this->makeRequestAndGetRawContent($request);

        $this->processAndAssertStatusCode(
            ['response' => ['status_code' => 204]],
            $response
        );

        $qrCode->reload();
        $checkoutOrder->reload();

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('on_demand', $qrCode['close_reason']);
        $this->assertEquals('closed', $checkoutOrder['status']);
        $this->assertEquals('opt_out', $checkoutOrder['close_reason']);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode->reload();
        $checkoutOrder->reload();
        $order->reload();

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('on_demand', $qrCode['close_reason']);
        $this->assertEquals('closed', $checkoutOrder['status']);
        $this->assertEquals('opt_out', $checkoutOrder['close_reason']);

        $this->assertEquals('created', $order['status']);
        $this->assertEquals(0, $order['amount_paid']);
        $this->assertEquals(0, $order['attempts']);

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::CHECKOUT_ORDER_CLOSED, $qrPayment['unexpected_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($order['amount'], $payment['amount']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($qrCodeId, $payment['receiver_id']);
        $this->assertEquals($rrn, $payment['reference16']);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals(UnexpectedPaymentReason::CHECKOUT_ORDER_CLOSED, $refund['notes']['refund_reason']);
        $this->assertEquals($order['amount'], $refund['amount']);
    }

    public function testShouldCreateUnexpectedPaymentWhenPaymentAmountIsDifferentFromQrAmount(): void
    {
        $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);
        $order = $this->getDbLastEntity('order');

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        $request = $this->testData['testProcessIciciQrPayment'];

        $paidAmount = 3000;
        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $paidAmount/100;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode->reload();
        $checkoutOrder->reload();
        $order->reload();

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        $this->assertEquals('created', $order['status']);
        $this->assertEquals(0, $order['amount_paid']);
        $this->assertEquals(0, $order['attempts']);

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH, $qrPayment['unexpected_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($paidAmount, $payment['amount']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($qrCodeId, $payment['receiver_id']);
        $this->assertEquals($rrn, $payment['reference16']);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH, $refund['notes']['refund_reason']);
        $this->assertEquals($paidAmount, $refund['amount']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('unprocessed', $response['status']);
    }

    public function testShouldCreateUnexpectedPaymentWhenOrderIsAlreadyPaid(): void
    {
        $order = $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        //Edit the order to make it paid here
        $this->fixtures->edit('order', $order['id'], [
            'status' => 'paid',
            'amount_paid' => $order['amount'],
            'attempts' => 1,
        ]);
        $order->reload();

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode->reload();
        $checkoutOrder->reload();
        $order->reload();

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals($order['amount'], $order['amount_paid']);
        $this->assertEquals(1, $order['attempts']);

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals('Payment already done for this order.', $qrPayment['unexpected_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($order['amount'], $payment['amount']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('FallbackQrCode', $payment['receiver_id']);
        $this->assertEquals($rrn, $payment['reference16']);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals('Payment already done for this order.', $refund['notes']['refund_reason']);
        $this->assertEquals($order['amount'], $refund['amount']);
    }

    public function testFetchQrCodePaymentStatusApi(): void
    {
        /**
         * Create a checkout order without order
         * call fetch status api - should return unprocessed
         * do payment
         * then call again - should return payment id
         */

        $response = $this->createCheckoutOrder();

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('unprocessed', $response['status']);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pay_' . $payment['id'], $response['razorpay_payment_id']);
    }

    public function testFetchQrCodePaymentStatusAssociatedToOrder(): void
    {
        /**
         * Create a checkout order with order
         * call fetch status api - should return unprocessed
         * do payment
         * then call again - should return payment_id, order_id and signature
         */

        $this->fixtures->create('order', [
            'amount' => 5000,
            'payment_capture' => 1
        ]);
        $order = $this->getDbLastEntity('order');

        $response = $this->createCheckoutOrder(['order_id' => $order['id']]);
        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('unprocessed', $response['status']);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order['amount']/100;

        $this->makeUpiIciciPayment($request);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();

        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCode['id'], $payment['receiver_id']);
        $this->assertEquals('paid', $order['status']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pay_' . $payment['id'], $response['razorpay_payment_id']);
        $this->assertEquals('order_' . $order['id'], $response['razorpay_order_id']);
        $this->assertNotNull($response['razorpay_signature']);
    }

    public function testCancelCheckoutOrderApi(): void
    {
        /**
         * Create checkout order
         * Assert the qr code status, checkout order status
         * Fetch the payment status, should return unprocessed
         * Call the cancel checkout order api
         * Assert the qr code status, checkout order status
         * Call the fetch payment status, it should throw an error
         */

        $response = $this->createCheckoutOrder();

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);
        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);

        $this->assertEquals('active', $qrCode['status']);
        $this->assertEquals('active', $checkoutOrder['status']);

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('unprocessed', $response['status']);

        $request = [
            'method'  => 'DELETE',
            'url'     => '/checkout/order/' . $checkoutOrder['id'],
            'content' => ['close_reason' => 'opt_out']
        ];
        $response = $this->makeRequestAndGetRawContent($request);

        $this->processAndAssertStatusCode(
            [ 'response' => [ 'status_code' => 204 ] ],
            $response
        );

        $qrCode->reload();
        $checkoutOrder->reload();

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('on_demand', $qrCode['close_reason']);
        $this->assertEquals('closed', $checkoutOrder['status']);
        $this->assertEquals('opt_out', $checkoutOrder['close_reason']);

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout/qr_code/qr_' . $qrCodeId . '/payment/status',
        ];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Payment processing cancelled');

        $this->makeRequestAndGetContent($request);
    }

    public function testCancelCheckoutOrderOnPaidCheckoutOrder(): void
    {
        /**
         * When Cancel API is called after the checkout order is paid
         * the checkout order status should not change
         */
        $response = $this->createCheckoutOrder();

        $qrCodeId = $response['qr_code']['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $checkoutOrder = $this->getDbEntityById('checkout_order', $response['id']);
        $qrCode = $this->getDbEntityById('qr_code', $qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('paid', $checkoutOrder['close_reason']);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'DELETE',
            'url'     => '/checkout/order/' . $checkoutOrder['id'],
            'content' => ['close_reason' => 'opt_out']
        ];
        $response = $this->makeRequestAndGetRawContent($request);

        $this->processAndAssertStatusCode(
            [ 'response' => [ 'status_code' => 204 ] ],
            $response
        );

        $qrCode->reload();
        $checkoutOrder->reload();

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('paid', $checkoutOrder['status']);
        $this->assertEquals('paid', $checkoutOrder['close_reason']);
    }

    protected function makeUpiIciciPayment($request): array
    {
        $this->ba->directAuth();

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        return $response;
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    protected function createCheckoutOrder(array $input = [], string $mode = Mode::TEST, string $merchantId = Account::TEST_ACCOUNT)
    {
        $this->ba->publicAuth();

        if ($mode === Mode::LIVE)
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $defaultValues = $this->getDefaultCreateCheckoutOrderArray();

        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method'  => 'POST',
            'url'     => '/checkout/order',
            'content' => $attributes,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function createCheckoutOrderForPartner(array $input = [], string $partnerId = Account::TEST_ACCOUNT, string $subMerchantId = '100submerchant')
    {
        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $subMerchantId);

        $this->fixtures->merchant->enableMethod($subMerchantId, 'upi');

        $defaultValues = $this->getDefaultCreateCheckoutOrderArray();

        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method'  => 'POST',
            'url'     => '/checkout/order?key_id=rzp_test_partner_' . $client->getId().'&account_id='.$subMerchantId,
            'content' => $attributes,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
            ],
        ];
        $response = $this->makeRequestAndGetContent($request);

        return [$response,$client];
    }

    protected function getDefaultCreateCheckoutOrderArray(): array
    {
        return [
            "amount" => 4000,
            "currency" => "INR",
            "checkout_id" => "KGyqftFxvElLNy",
            "receiver_type" => "qr_code",
            "description" => "Test Checkout Order",
            "notes" => [
                "purpose" => "Test UPI QR code notes"
            ],
            "method" => "upi",
            "contact" => "+919876543210",
            "email" => "abc@exmple.com",
            "upi" => [
                "flow" => "intent",
            ],
            "_" => [
                "upiqr" => 1,
                "flow" => "intent",
                "build" => null,
                "checkout_id" => "KGyqftFxvElLNy",
                "device.id" => "1.391dc652026b48704c49a5d88ebd5230d2034330.1658476679999.47124528",
                "device_id" => "1.391dc652026b48704c49a5d88ebd5230d2034330.1658476679999.47124528",
                "env" => "testing",
                "library" => "checkoutjs",
                "platform" => "browser",
                "referer" => "http://checkout.localhost/",
                "request_index" => 0,
                "shield" => [
                    "fhash" => "069a7598fa5cf4d27b9aea85b73b0a46148415e4",
                    "tz" => 330
                ],
            ],
        ];
    }

    protected function assertAmountAndQrId(string $intentUrl): void
    {
        $qrCodeEntity = $this->getDbLastEntity('qr_code');

        $tr = 'RZP' . substr($qrCodeEntity['id'], 0, 14) . 'qrv2';
        $amount = $qrCodeEntity['amount'] / 100;

        $this->assertStringContainsString('tr=' . $tr, $intentUrl);
        $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
    }

    protected function assertCheckoutOrderMetadata($checkoutOrder): void
    {
        $expectedCheckoutOrderMetadata = [
            '_' => [
                'env' => 'testing',
                'flow' => 'intent',
                'build' => null,
                'upiqr' => 1,
                'shield' => [
                    'tz' => 330,
                    'fhash' => '069a7598fa5cf4d27b9aea85b73b0a46148415e4'
                ],
                'library' => 'checkoutjs',
                'referer' => 'http://checkout.localhost/',
                'platform' => 'browser',
                'device.id' => '1.391dc652026b48704c49a5d88ebd5230d2034330.1658476679999.47124528',
                'device_id' => '1.391dc652026b48704c49a5d88ebd5230d2034330.1658476679999.47124528',
                'checkout_id' => 'KGyqftFxvElLNy',
                'request_index' => 0
            ],
            'upi' => [
                'flow' => 'intent'
            ],
            'notes' => [
                'purpose' => 'Test UPI QR code notes'
            ],
            'amount' => '4000',
            'method' => 'upi',
            'currency' => 'INR',
            'description' => 'Test Checkout Order',
            'receiver_type' => 'qr_code',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
        ];

        $this->assertArraySelectiveEquals($expectedCheckoutOrderMetadata, $checkoutOrder['meta_data']);
    }
}
