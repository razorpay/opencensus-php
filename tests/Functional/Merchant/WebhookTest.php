<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\App;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Traits\TestsWebhookEvents;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

/**
 * This file is a central place between modules e.g. order, invoice, payout,
 * and settlements etc, of tests asserting that expected webhook events are
 * dispatched on actions.
 *
 * Recommend not to write more tests here! Instead put assertions in respective
 * module itself using TestsWebhookEvents trait.
 */
class WebhookTest extends TestCase
{
    use AttemptTrait;
    use AttemptReconcileTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use PartnerTrait;

    protected $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookData.php';

        parent::setUp();



        $this->ba->proxyAuth();
    }

    public function testWebhookEventData()
    {
        $this->expectWebhookEventWithContents('payment.authorized', __FUNCTION__);

        $this->doAuthPayment();
    }

    public function testInvoicePaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEvent(
            'invoice.paid',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('terminal_id', $event['payload']['payment']['entity']);
            }
        );

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    /**
     * If partner parent has feature "terminal_onboarding" enabled, then only payment entity should have terminal_id key
     */
    public function testPaymentWebhookShouldHaveTerminalIdForFeaturedPartner()
    {
        $partner = $this->fixtures->create('merchant');

        $partnerId = $partner->getId();

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => '10000000000000',
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            $partnerId
        );

        $expectedEvent = $this->testData['testInvoicePaidWebhookEventData']['event'];

        $expectedEvent['payload']['payment']['entity']['terminal_id'] = 'term_1n25f6uN5S1Z5a';

        $this->expectWebhookEventWithContents('invoice.paid', $expectedEvent);

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    /**
     * Invoice created without customer details, once paid should contain those
     * information in invoices entity and same should be sent as hook payload.
     *
     */
    public function testInvoiceWithoutCustomerDetailsPaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('invoice.paid', $expectedEvent);

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice',
            [
                'amount'           => 1000000,
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    public function testInvoicePaidWebhookEventDataWithOrderAndWithoutInvoice()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        // This webhook will be called for order.paid event.
        $this->expectWebhookEvent(
            'order.paid',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayNotHasKey('invoice', $event['payload']);
            }
        );

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testCreateWebhookWithEventWhenFeatureNotEnabled()
    {
        $this->startTest();
    }

    public function testWebhooksFeatureBasedEvents()
    {
        // Adds feature and creates webhook with subscriptions.charged even and asserts the same in next get call.
        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->startTest();
    }

    public function testWebhookEventWithExpressTranslationEnabled()
    {
        $translatedWebhookBody = 'sample translated webhook body';

        // mark as partner
        $partnerId     = '100000Razorpay';
        $client        = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);
        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
                'entity_owner_id' => $partnerId,
            ]
        );

        $app = DB::Connection('auth')
                ->table('applications')
                ->orderBy('created_at', 'desc')
                ->first();

        // create setting for translation url
        $this->ba->adminAuth();
        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth($partnerId);

        $testData = $this->testData['createSettingsForWebhookTranslateUrl'];

        $this->runRequestResponseFlow($testData);

        $this->ba->deleteAccountAuth();

        $payment  = $this->getDefaultPaymentArray();

        // mock mozart webhook translate requests
        $this->mockMozartWebhookTranslateRequest(function ($path, $content) use ($translatedWebhookBody) {

            return [
                'content'   => $translatedWebhookBody,
                'headers'   => ['request-id' => ['12345678']],
            ];
        }, 2);

        $this->expectWebhookEvent(
            'payment.authorized',
            function ($body) use ($translatedWebhookBody)
            {
                $this->assertEquals($translatedWebhookBody, $body);
            }
        );

        // make payment on submerchant
        $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);
    }

    public function testWebhookEventWithExpressTranslationNotEnabled()
    {
        $this->ba->privateAuth();

        $this->mockMozartWebhookTranslateRequest(null, 0);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCreatedForJsonp()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCreatedForAuth()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);
    }

    public function testWebhookPaymentCreatedForAjax()
    {
        $this->expectWebhookEvent('payment.created');

        $this->gateway = 'upi_hulk';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->gateway = 'upi_hulk';

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testWebhookPaymentCreatedForCheckout()
    {
        $this->expectWebhookEvent('payment.created');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPaymentViaCheckoutRoute($payment);
    }

    public function testWebhookPaymentCreatedForS2SPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->ba->privateAuth();

        $this->mockCardVault();

        $this->expectWebhookEvent('payment.created');

        $this->doS2SPrivateAuthPayment();
    }

    public function testWebhookPaymentCreatedForCustomerFee()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->ba->privateAuth();

        $this->mockCardVault();

        $this->dontExpectAnyWebhookEvent();

        $this->createAndGetFeesForPayment();
    }

    public function testOrderPaidWebhookEventData()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('order.paid', $expectedEvent);

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function test1ccOrderPaidWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('order.paid', $expectedEvent);

        $order = $this->createOrder(
            [
                'amount'           => 50000,
                'line_items_total' => 50000,
                'receipt'          => 'random',
            ]);
        $ordermeta = $this->getDbLastEntityToArray('order_meta');
        $this->fixtures->edit('order_meta', $ordermeta['id'],['value'=> self::getOrderMetaValue(50000)]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $this->doAuthAndCapturePayment($payment);
    }

    protected  function getOrderMetaValue(int $amount)
    {
        $app = App::getFacadeRoot();
        $shippingFee = 1000;
        $shipping_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'shipping_address',
            'primary'       => true
        ];
        $billing_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'billing_address',
            'primary'       => true
        ];
        $customer = [
            'contact'           =>'+9191111111111',
            'email'             =>'john.doe@razorpay.com',
            'shipping_address'  =>$shipping_address,
            'billing_address'   =>$billing_address

        ];
        return [
            'cod_fee'           => $shippingFee,
            'net_price'         => $amount+$shippingFee,
            'sub_total'         => 51000,
            'shipping_fee'      => $amount+$shippingFee,
            'customer_details'  => $app['encrypter']->encrypt($customer),
            'line_items_total'  => $amount,
        ];
    }

    protected function createOrder(array $input = [])
    {
        $defaultInput = [
            'amount'        => 50000,
            'currency'      => 'INR',
            'receipt'       => random_int(1000, 99999),
        ];

        $input = array_merge($defaultInput, $input);

        $request = [
            'url'       => '/orders',
            'method'    => 'POST',
            'content'   => $input,
            'convertContentToString' => false,
        ];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testOrderPaidWebhookEventDataWithTaxInvoiceBlock()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('order.paid', $expectedEvent);

        $taxInvoice = [
            'business_gstin'=> '123456789012345',
            'gst_amount'    =>  10000,
            'supply_type'   => 'intrastate',
            'cess_amount'   =>  12500,
            'customer_name' => 'Gaurav',
            'number'        => '1234',
            "date"          => "1589994898",
        ];

        $order = $this->fixtures->order->createOrderWithTaxInvoice($taxInvoice, ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testOrderPaidWebhookEventDataWithoutOrder()
    {
        $this->dontExpectWebhookEvent('order.paid');

        $this->doAuthAndCapturePayment();
    }

    /**
     * Tests if a webhook is triggered to the merchant when a settlement is processed.
     */
    public function testTransferSettlementWebhook()
    {
        $this->ba->privateAuth();

        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $payment = $this->createPaymentEntities(1);

        $account2 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);
        $this->fixtures->merchant->edit('10000000000002', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account2);

        $account3 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000003']);
        $this->fixtures->merchant->edit('10000000000003', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account3);

        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        // Expects settlement.processed on account with id acc_10000000000002.
        $this->expectWebhookEvent(
            'settlement.processed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArrayHasKey('account_id', $event);
                $this->assertEquals('acc_10000000000002', $event['account_id']);
                $this->assertEquals('settlement.processed', $event['event']);
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );
        // Expects settlement.processed on account with id acc_10000000000003.
        $this->expectWebhookEvent(
            'settlement.processed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArrayHasKey('account_id', $event);
                $this->assertEquals('acc_10000000000003', $event['account_id']);
                $this->assertEquals('settlement.processed', $event['event']);
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, false);

        $this->reconcileEntitiesForChannel($channel);

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testWebhookOnSettlementFailure()
    {
        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->createPaymentAndRefundEntities(2);

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        $this->dontExpectAnyWebhookEvent();

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testRefundSpeedChangedWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEvent(
            'refund.speed_changed',
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
                $this->assertArrayHasKey('created_at', $event);
            }
        );

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundFailedWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.failed', $expectedEvent);

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3459);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('reversed', $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertNotNull($reversal['balance_id']);
    }

    public function testRefundProcessedInstantWebhookEventData()
    {
        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        // Adding specific amount to refund - this is meant to test processed instant refunds on scrooge -
        $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundProcessedNormalWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $this->refundPayment($payment['id']);
    }

    // tests the processed webhook for instant refunds of merchants having show_refund_public_status
    // or pending_status feature enabled
    public function testRefundProcessedInstantWebhookEventDataForPublicStatusFeatureMerchants()
    {
        $this->fixtures->merchant->addFeatures(['show_refund_public_status']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData['testRefundProcessedInstantWebhookEventData']['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        // Adding specific amount to refund - this is meant to test processed instant refunds on scrooge -
        $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);
    }

    // tests the scenario when instant refund fails and refund gets processed via normal
    public function testRefundProcessedWebhookOnSpeedChangeFromOptimumToNormal()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        // Adding specific amount to refund - this is meant to test processed instant refunds on scrooge -
        $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
    }

    // tests the scenario when instant refund fails and refund gets processed via normal for
    // show_refund_public_status or pending status  feature enabled merchants
    public function testRefundProcessedWebhookOnSpeedChangeFromOptimumToNormal2()
    {
        $this->fixtures->merchant->addFeatures(['show_refund_public_status']);
        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData['refundSpeedChangedWebhookEventDataForPublicStatusFeatureEnabled']['event'];
        $this->expectWebhookEventWithContents('refund.speed_changed', $expectedEvent);
        $this->dontExpectWebhookEvent('refund.processed');

        $refund =  $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals('created', $refund['status']);

        $expectedEvent = $this->testData['testRefundProcessedWebhookOnSpeedChangeFromOptimumToNormal']['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals('processed', $refund['status']);
    }

    public function testRefundProcessedNormalWebhookWithPublicStatusFeatureEnabled()
    {
        $this->fixtures->merchant->addFeatures(['show_refund_public_status']);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData['testRefundProcessedNormalWebhookEventData']['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals('processed', $refund['status']);
    }

    public function testRefundCreatedWebhookEventData()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.created', $expectedEvent);

        $this->refundPayment($payment['id']);
    }

    // We fire webhook terminal.created to aggregators 45 min after terminal is created
    // We are using reminders service for this
    public function testTerminalCreatedReminderWebhook()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $terminal = $this->fixtures->create('terminal',
        [
            'merchant_id' => $subMerchant->getId(),
            'enabled'     => true,
            'gateway'     => 'worldline',
            'status'      => 'pending',
            'mc_mpan'     => base64_encode('1234567890123456'),
            'visa_mpan'   => base64_encode('9876543210123456'),
            'rupay_mpan'  => base64_encode('1234123412341234'),
        ]);

        $expectedEvent = $this->testData[__FUNCTION__.'Data']['event'];
        $this->expectWebhookEventWithContents('terminal.created', $expectedEvent);

        $this->testData[__FUNCTION__]['request']['url'] = '/reminders/send/test/terminal/terminal_created_webhook/' . $terminal->getId();

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testTerminalOnboardingStatusActivatedWebhook()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            '10000000000000'
        );

        $terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => $subMerchantId,
                'enabled'     => false,
                'gateway'     => 'worldline',
                'status'      => 'pending'
            ]);

        $this->ba->adminAuth();

        $expectedEvent = $this->testData[__FUNCTION__.'Data']['event'];
        $this->expectWebhookEventWithContents('terminal.activated', $expectedEvent);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids' => [$terminal->getId()] ,
            'attributes'   => ['status' => 'activated', 'enabled' => true]
        ];

        $this->startTest();
    }

    protected function createTransferEntity($payment, $account)
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(20)->timestamp + 5;

        $this->fixtures->create('transfer:to_account',
            [
                'account'       => $account,
                'source_id'     => $payment->getId(),
                'source_type'   => 'payment',
                'amount'        => 2500,
                'currency'      => 'INR',
                'on_hold'       => '0',
                'on_hold_until' => Carbon::today(Timezone::IST)->timestamp - 600,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10
            ]);
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }

    public function testRefundArnUpdatedWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['refund_arn_webhook']);
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'upi_icici';

        $this->fixtures->payment->edit($payment['id'], [ 'method' =>'upi', 'gateway' => 'upi_icici']);
        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $matcher = function (array $event) use ($expectedEvent)
        {
            $this->assertArraySelectiveEquals($expectedEvent, $event);
            $this->assertNotNull($event['payload']['refund']['entity']['acquirer_data']['rrn']);
        };

        $this->expectWebhookEvent('refund.arn_updated', $matcher);

        $this->refundPayment($payment['id']);
    }

    public function testWebhookPaymentCreatedForAjaxWithCustomerFee()
    {
        $this->expectWebhookEvent(
            'payment.created',
            function (array $event)
            {
                $this->assertArrayNotHasKey('reference9', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('reference5', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee_gst', $event['payload']['payment']['entity']);

            }
        );
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"upi": {"fee": {"payee": "business", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'upi',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->gateway = 'upi_hulk';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_hulk_terminal');

        $this->gateway = 'upi_hulk';

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testWebhookPaymentAuthorizedForAjaxWithCustomerFee()
    {
        $this->expectWebhookEvent(
            'payment.authorized',
            function (array $event)
            {
                $this->assertArrayNotHasKey('reference9', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('reference5', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee_gst', $event['payload']['payment']['entity']);

            }
        );
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCapturedForAjaxWithCustomerFee()
    {
        $this->expectWebhookEvent(
            'payment.captured',
            function (array $event)
            {
                $this->assertArrayNotHasKey('reference9', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('reference5', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee', $event['payload']['payment']['entity']);
                $this->assertArrayNotHasKey('customer_fee_gst', $event['payload']['payment']['entity']);

            }
        );

        $this->expectWebhookEvent(
            'order.paid',
            function (array $event)
            {
                $this->assertArrayNotHasKey('reference7', $event['payload']['order']['entity']);

                $this->assertArrayNotHasKey('fee_config_id', $event['payload']['order']['entity']);


            }
        );

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);
    }
}
