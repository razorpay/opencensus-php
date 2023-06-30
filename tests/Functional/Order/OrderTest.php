<?php

namespace RZP\Tests\Functional\Order;

use Mockery;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Account;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Traits\TestsWebhookEvents;

class OrderTest extends TestCase
{
    use RazorxTrait;
    use MocksRazorx;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/OrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function setUpBillDeskGateway()
    {
        $this->fixtures->create('terminal:shared_billdesk_tpv_terminal');

        $this->setMockGatewayTrue();
    }

    public function setUpSharpGateway()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';
    }

    public function testCreateOrderLiveModeNonKycActivatedNonCaActivatedExperimentOff()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateOrderLiveModeKycActivatedNonCaActivatedExperimentOff()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrder'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateOrderLiveModeNonKycActivatedCaActivatedExperimentOff()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrderLiveModeNonKycActivatedNonCaActivatedExperimentOff'];

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           => '10000000000000',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateOrderLiveModeNonKycActivatedIciciCaActivatedExperimentOff()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrderLiveModeNonKycActivatedNonCaActivatedExperimentOff'];

        $attributes = [
            'merchant_id'       => '10000000000000',
            'bas_business_id'   => '10000000000000',
        ];

        $this->fixtures->on('live')->create('merchant_detail', $attributes);

        $this->fixtures->on('live')->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
                'channel'           => 'icici',
            ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateOrderLiveModeKycActivatedCaActivatedExperimentOff()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrder'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           => '10000000000000',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->mockCardVaultWithCryptogram();

        $this->startTest();
    }

    public function testCreateOrderLiveModeKycActivatedIciciCaActivatedExperimentOff()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrder'];

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $attributes = [
            'merchant_id'       => '10000000000000',
            'bas_business_id'   => '10000000000000',
        ];

        $this->fixtures->on('live')->create('merchant_detail', $attributes);

        $this->fixtures->on('live')->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
                'channel'           => 'icici',
            ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }


    public function testCreateOrderLiveModeNonKycActivatedNonVaActivatedExperimentOn()
    {

        $attributes = [
            'merchant_id'       => '10000000000000',
            'bas_business_id'   => '10000000000000',
        ];

        $this->fixtures->on('live')->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product' => 'banking',
                'type' => 'X',
                'group' => 'products_enabled',
                'value' => 'false',
            ]);

        $this->fixtures->on('live')->create('merchant_detail', $attributes);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderMYRMerchantMY()
    {
        $this->fixtures->edit('merchant', 10000000000000, [
            'country_code' => 'MY',
            'convert_currency' => true
        ]);

        $order = $this->startTest();

        $this->assertEquals($order['amount'], 50000);
        $this->assertEquals($order['status'], 'created');
        $this->assertEquals($order['currency'], 'MYR');
        $this->assertEquals($order['receipt'], 'rcptid42');
    }

    public function testCreateOrderINRMerchantMY()
    {
        $this->fixtures->edit('merchant', 10000000000000, [
            'convert_currency' => true,
            'country_code' => 'MY'
        ]);

        $order = $this->startTest();

        $this->assertEquals($order['amount'], 50000);
        $this->assertEquals($order['status'], 'created');
        $this->assertEquals($order['currency'], 'INR');
        $this->assertEquals($order['receipt'], 'rcptid42');
    }

    public function testCreateOrderAdminAuthRoute()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->merchant->addFeatures(FeatureConstants::ALLOW_FORCE_TERMINAL_ID, $merchant->getId());

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchant->getId());

        $this->startTest();
    }

    public function testCreateOrderAdminAuthRouteMerchantNotHavingFeature()
    {
        $merchant = $this->fixtures->create('merchant');

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchant->getId());

        $this->startTest();
    }

    public function testCreateOrderWithPhonepeSwitchContext()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $this->assertEquals($testData['request']['content']['phonepe_switch_context'], $order['provider_context']);
    }

    public function testCreateOrderWithInvalidPhonepeSwitchContext()
    {
        $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $this->assertNull($order);
    }

    public function testCreateOrderForNonRegisteredBusinessLessThanMaxAmount()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            Entity::CATEGORY => 5399,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $merchantDetailAttribute = [
            Entity::MERCHANT_ID             => $merchantId,
            DetailEntity::BUSINESS_TYPE     => 2,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderForNonRegisteredBusinessMoreThanMaxAmount()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            Entity::CATEGORY => 5399,
            ENTITY::MAX_INTERNATIONAL_PAYMENT_AMOUNT    => 25000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $merchantDetailAttribute = [
            Entity::MERCHANT_ID             => $merchantId,
            DetailEntity::BUSINESS_TYPE     => 2,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderWithEmptyArrayTransfersParam()
    {
        $this->startTest();
    }

    public function testCreateOrderWithNonArrayTokenParam()
    {
        $request = $this->testData['testCreateOrder']['request'];

        $request['content']['token'] = "random";

        $this->makeRequestAndCatchException(
            function () use ($request)
            {
                $this->makeRequestAndGetContent($request);
            },
            Exception\BadRequestValidationFailureException::class,
            'token attribute must be an array.'
        );
    }

    public function testUniqueReceiptFeatureWithNoReceipt()
    {
        $this->fixtures->merchant->addFeatures(['order_receipt_unique']);

        $this->startTest();
    }

    public function testUniqueReceiptFeatureWithValidReceipt()
    {
        $this->fixtures->merchant->addFeatures(['order_receipt_unique']);

        $this->startTest();
    }

    public function testInvalidCurrency()
    {
        $this->startTest();
    }

    public function testValidCurrencyForConvertSupport()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);
        $this->startTest();
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testCurrencyForShaadiComWithFeatureEnabled()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);
        $this->startTest();
    }

    public function testCurrencyForShaadiComWithFeatureNotEnabled()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);
        $this->startTest();
    }

    public function testUniqueReceiptFeatureWithDuplicateReceipt()
    {
        $order = $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ]);

        $this->fixtures->merchant->addFeatures(['order_receipt_unique']);

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['error']['field']['order_ids'] = [$order->getPublicId()];

        $this->startTest();
    }

    public function testUniqueReceiptErrorFeatureWithDuplicateReceipt()
    {
        $order = $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ]);

        $this->fixtures->merchant->addFeatures(['order_receipt_unique', 'order_receipt_unique_err']);

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['error']['field']['order_ids'] = [$order->getId()];

        $this->startTest();
    }

    /**
     * Checks if two orders can be created each having receipt as null when the order_receipt_unique feature is not
     * added.
     *
     * @return array
     */
    public function testCreateOrderWithTwoNullReceipts()
    {
        $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
        ]);

        $this->startTest();
    }

    /**
     * Checks if two orders can be created each having the same receipt when the order_receipt_unique feature is not
     * added.
     *
     * @return array
     */
    public function testCreateOrderWithTwoValidReceipts()
    {
        $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ]);

        $this->startTest();
    }

    public function testCreateOrderWithNegativeAmount()
    {
        $this->startTest();
    }

    public function testCreateOrderWithoutReceipt()
    {
        $this->startTest();
    }

    public function testCreateAutoCaptureOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrder()
    {
        $testData = $this->testData[__FUNCTION__];

        $orderResponse = $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $bankAccount =  $this->getDbLastEntity('bank_account');

        $orderRequest = $testData['request']['content'];

        // Account number and payer name will be updated in both the places
        // until on gateways we start using account number from bank accounts.
        $this->assertEquals($bankAccount->getAccountNumber(),$orderRequest['account_number']);
        $this->assertStringContainsString($orderRequest['bank'], $bankAccount->getIfscCode());

        $this->assertEquals($order->getAccountNumber(),$orderRequest['account_number']);
        $this->assertEquals($order->getBank(), $orderRequest['bank']);

        return $orderResponse;
    }

    public function testCreateTPVOrderWithoutAccountNumber()
    {
        $this->fixtures->merchant->enableTPV();

        $this->startTest();
    }

    public function testCreateTPVOrderWithNewFlow()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $bankAccount =  $this->getDbLastEntity('bank_account');

        $bankAccountRequest = $testData['request']['content']['bank_account'];

        // Account number and payer name will be updated in both the places
        // until on gateways we start using account number from bank accounts.
        $this->assertEquals($bankAccount->getAccountNumber(),$bankAccountRequest['account_number']);
        $this->assertEquals($bankAccount->getIfscCode(), $bankAccountRequest['ifsc']);
        $this->assertEquals($bankAccount->getBeneficiaryName(), $bankAccountRequest['name']);

        $this->assertEquals($order->getAccountNumber(),$bankAccountRequest['account_number']);
        $bankCodeFromIfsc = strtoupper(substr($bankAccountRequest['ifsc'], 0, 4));
        $this->assertEquals($order->getBank(), $bankCodeFromIfsc);
        $this->assertEquals($order->getPayerName(), $bankAccountRequest['name']);
    }

    public function testCreateOrderAccountInvalidBankIfscWithFeatureEnable()
    {
        $this->fixtures->merchant->addFeatures(['enable_ifsc_validation']);

        $this->startTest();
    }

    public function testCreateOrderAccountInvalidBankIfscWithFeatureDisabled()
    {
        $this->startTest();
    }

    //Creating few UPI specific tests, as other TPV tests are of method netbanking.
    public function testCreateUpiTPVOrderOldRequestFormat()
    {
        $testData = $this->testData[__FUNCTION__];

        $orderResponse = $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $this->assertEquals($order->getMethod(), 'upi');

        $bankAccount =  $this->getDbLastEntity('bank_account');

        $orderRequest = $testData['request']['content'];

        // Account number and payer name will be updated in both the places
        // until on gateways we start using account number from bank accounts.
        $this->assertEquals($bankAccount->getAccountNumber(),$orderRequest['account_number']);
        $this->assertStringContainsString($orderRequest['bank'], $bankAccount->getIfscCode());

        $this->assertEquals($order->getAccountNumber(),$orderRequest['account_number']);
        $this->assertEquals($order->getBank(), $orderRequest['bank']);

        return $orderResponse;
    }


    public function testCreateUpiTPVOrderNewRequestFormat()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->startTest();

        $order =  $this->getDbLastEntity('order');

        $this->assertEquals($order->getMethod(), 'upi');

        $bankAccount =  $this->getDbLastEntity('bank_account');

        $bankAccountRequest = $testData['request']['content']['bank_account'];

        // Account number and payer name will be updated in both the places
        // until on gateways we start using account number from bank accounts.
        $this->assertEquals($bankAccount->getAccountNumber(),$bankAccountRequest['account_number']);
        $this->assertEquals($bankAccount->getIfscCode(), $bankAccountRequest['ifsc']);
        $this->assertEquals($bankAccount->getBeneficiaryName(), $bankAccountRequest['name']);

        $this->assertEquals($order->getAccountNumber(),$bankAccountRequest['account_number']);
        $bankCodeFromIfsc = strtoupper(substr($bankAccountRequest['ifsc'], 0, 4));
        $this->assertEquals($order->getBank(), $bankCodeFromIfsc);
        $this->assertEquals($order->getPayerName(), $bankAccountRequest['name']);
    }

    public function testCreateUpiTPVOrderNewRequestOldIfsc()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->startTest();

        $bankAccount =  $this->getDbLastEntity('bank_account');
        $order =  $this->getDbLastEntity('order');

        $bankAccountRequest = $testData['request']['content']['bank_account'];

        //Ifsc from request and that in bank account entity should NOT match
        $this->assertNotEquals($bankAccount->getIfscCode(), $bankAccountRequest['ifsc']);

        //Bank account entity should have ifsc equal to one fetched from mapping.
        $this->assertEquals($bankAccount->getIfscCode(), BankAccount\OldNewIfscMapping::getNewIfsc($bankAccountRequest['ifsc']));

        //Order->getBank equal to first 4 chars of bankAccount entity's ifsc
        $bankCodeFromIfsc = strtoupper(substr($bankAccount->getIfscCode(), 0, 4));
        $this->assertEquals($order->getBank(), $bankCodeFromIfsc);
        $this->assertEquals($order->getPayerName(), $bankAccountRequest['name']);

    }

    public function testCreateUpiRecurringTPVOrder()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->startTest();
        $bankAccount =  $this->getDbLastEntity('bank_account');
        $order =  $this->getDbLastEntity('order');

        $bankAccountRequest = $testData['request']['content']['bank_account'];

        // Bank account entity should have ifsc equal as the order create request
        $this->assertEquals($bankAccount->getIfscCode(), $bankAccountRequest['ifsc']);

        // Order->getBank equal to first 4 chars of bankAccount entity's ifsc
        $bankCodeFromIfsc = strtoupper(substr($bankAccount->getIfscCode(), 0, 4));
        $this->assertEquals($order->getBank(), $bankCodeFromIfsc);
        $this->assertEquals($order->getPayerName(), $bankAccountRequest['name']);
    }

    public function testCreateTPVOrderEmptyMethod()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrderUpiBank()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrderUpiBankInconsitentIfsc()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateCardTPVOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrderWhenMethodNull()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateCardTPVOrderNoMaxAmount()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateCardTPVOrderNoExpireAt()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderWithBank()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testEMandateOrderWithCustomerFeeBearer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->startTest();
    }

    public function testNachOrderWithCustomerFeeBearer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->startTest();
    }

    public function testEmandateRegistrationOrderWithZeroRupee()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testEmandateRegistrationOrderWithTokenMaxAmount()
    {
        $this->mockCardVault();
        $this->fixtures->create('terminal:shared_emandate_axis_terminal');
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $order = $this->startTest();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray('UTIB', 0);

        $payment['order_id'] = $order['id'];

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766'
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals(2500, $token['max_amount']);
    }

    public function testEmandateRegistrationOrderWithZeroRupeeAndTokenWithFirstAmount()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testEmandateRegistrationOrderWithZeroRupeeAndTokenWithoutCustomer()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testEmandateRegistrationOrderWithZeroRupeeAndToken()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testTokenRegistrationOrderWithDifferentMethod()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testTokenRegistrationOrderWithoutMethod()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testEmandateRegistrationOrderWithoutZeroRupee()
    {
        $this->markTestSkipped('No non-zero ruppee flow available');

        $order = $this->startTest();

        return $order;
    }

    public function testEmandateRegistrationOrderWithInvalidBank()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testGetOrder()
    {
        $order = $this->testCreateOrder();

        $order = $this->getEntityById('order', $order['id']);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $order);
    }

    public function testGetMultipleOrders()
    {
        $createdOrders = $this->fixtures->times(2)->create('order');
        $createdOrders = array_reverse($createdOrders);
        $collection = new \RZP\Models\Base\PublicCollection($createdOrders);
        $array = $collection->toArrayPublic();

        $this->testData[__FUNCTION__]['response']['content'] = $array;

        $this->startTest();
    }

    public function testGetMultiplePaymentsForOrder()
    {
        $order = $this->testCreateOrder();
        $order = $this->getLastEntity('order');

        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('fetchOrderPayments')
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function (string $orderId, string $merchantId)
            {
                return [];
            });

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $this->mockCardVaultWithCryptogram();
        $rzpPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/orders/'. $order['id'] . '/payments';

        $this->ba->privateAuth();
        $this->mockCardVaultWithCryptogram();

        $payments = $this->startTest();

        $this-> assertEquals(1, $payments['count']);
    }

    public function testFetchOrder()
    {
        $order = $this->fixtures->create('order');

        $this->testData[__FUNCTION__]['request']['url'] = '/orders/order_' . $order['id'];

        $response = $this->startTest();

        $this->assertArrayNotHasKey('virtual_account', $response);
    }

    public function testFetchOrderDetailForExpressAuth()
    {
        $order = $this->fixtures->create('order');

        $this->testData[__FUNCTION__]['request']['url'] = '/orders_internal/order_' . $order['id'];

        $this->ba->expressAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('virtual_account', $response);
    }

    public function testFetchOrderDetailNotExpressAuthError()
    {
        $order = $this->fixtures->create('order');

        $this->testData[__FUNCTION__]['request']['url'] = '/orders_internal/order_' . $order['id'];

        $response = $this->startTest();

        $this->assertArrayNotHasKey('virtual_account', $response);
    }

    public function testRetrieveOrderWithReceipt()
    {
        $order = $this->fixtures->create('order');

        $this->ba->proxyAuth();

        $orders = $this->retrieveOrdersDefault();

        //GIVEN
        $receipt = $orders['items'][0]['receipt'];

        $order = $this->retrieveOrdersDefault(['receipt' => $receipt]);

        $this->assertEquals($receipt, $order['items'][0]['receipt']);
    }

    public function testRetrieveOrderPaymentsWithReceipt()
    {
        $order = $this->fixtures->create('order');

        $this->ba->privateAuth();

        $orders = $this->retrieveOrdersDefault();

        //GIVEN
        $receipt = $orders['items'][0]['receipt'];

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = 'order_' . $order['id'];
        $payment['amount'] = $order['amount'];

        $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $order = $this->retrieveOrdersDefault(['receipt' => $receipt, 'expand' => ['payments']]);

        $this->assertEquals($receipt, $order['items'][0]['receipt']);

        $this->assertEquals($order['items'][0]['id'], $order['items'][0]['payments']['items'][0]['order_id']);
    }

    public function testStatusAfterPayment()
    {
        $order = $this->testCreateOrder();
        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $rzpPayment = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);

        $payment = $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'attempted');
        $this->assertEquals($order['authorized'], true);

        // If a payment is requested for an already authorised order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testStatusAfterAutoCapturePaymentWoCallback()
    {
        $this->mockCardVaultWithCryptogram();
        $order = $this->testCreateAutoCaptureOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $response = $this->doAuthPayment($payment);

        $this->assertAutoCaptureResponse($response, $payment, $order);
    }

    public function testAutoCaptureFeeBearerCustomer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $payment = $this->getDefaultPaymentArray();
        $this->ba->publicAuth();
        $feesArray = $this->validateFees($payment);

        $this->ba->privateAuth();

        $amount = $payment['amount'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];
        $payment['fee'] = $feesArray['input']['fee'];

        $order = $this->testCreateAutoCaptureOrder();

        $payment['order_id'] = $order['id'];

        $response = $this->doAuthPayment($payment);

        $this->assertAutoCaptureResponse($response, $payment, $order);
    }

    public function testStatusAfterAutoCapturePaymentWCallback()
    {
        // Sharp gateway will make payment go via callback flow
        $this->setUpSharpGateway();

        $this->testStatusAfterAutoCapturePaymentWoCallback();
    }

    protected function assertAutoCaptureResponse(array $response, $payment, $order)
    {
        $actualSignature = $response['razorpay_signature'];

        unset($response['razorpay_signature']);

        ksort($response);
        $exceptedSignature = $this->getSignature($response, 'TheKeySecretForTests');

        $this->assertEquals($actualSignature, $exceptedSignature);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($order['authorized'], true);
    }

    public function testOrderAndPaymentAmountMismatch()
    {
        $order = $this->testCreateOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = '1000';

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForTPVMerchantWithoutOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'UTIB';

        // Not adding order_id in payment

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testCardPaymentForTPVMerchantWithoutOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpSharpGateway();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentWithIncorrectBankForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ALLA';

        $payment['order_id'] = $order['id'];

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'UTIB';

        $payment['order_id'] = $order['id'];

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals($order['id'], $payment['order_id']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForTPVMerchants()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $this->testCreateTPVOrder();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForTPVMerchantsEmptyMethod()
    {
        $this->fixtures->merchant->enableTPV();
        $this->fixtures->merchant->enableUPI();

        $this->setUpBillDeskGateway();

        $this->testCreateTPVOrderEmptyMethod();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForTPVMerchantsEmptyMethodInvalidBank()
    {
        $this->fixtures->merchant->enableTPV();
        $this->fixtures->merchant->enableUPI();

        $this->setUpBillDeskGateway();

        $this->testCreateTPVOrderUpiBank();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForOrderWithBank()
    {
        $this->testCreateOrderWithBank();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);
    }

    public function testPreferencesForOrderWithAuthType()
    {
        $this->testEmandateRegistrationOrderWithZeroRupeeAndTokenWithFirstAmount();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $this->startTest($testData);
    }

    public function testPaymentWithIncorrectBankFromOrderBank()
    {
        $this->testCreateOrderWithBank();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'KKBK';

        $payment['order_id'] = $order['id'];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateOrderWithOffer()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"],
            'error_message' => 'Payment Method is not available for this Offer']);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(true, $order['force_offer']);

        // Pivot table entry also got created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer['entity_type']);
    }

    public function testCreateOrderWithOfferUpdatedFormat()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offers'][] = $offer->getPublicId();

        //
        // Backward compatible
        //
        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();
        $this->testData[__FUNCTION__]['response']['content']['offers'][] = $offer->getPublicId();

        $this->startTest();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(false, $order['force_offer']);

        // Pivot table entry also got created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer['entity_type']);
    }

    public function testCreateOrderWithMultipleOffers()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offers'] = [
            $offer1->getPublicId(),
            $offer2->getPublicId(),
        ];

        $this->testData[__FUNCTION__]['response']['content']['offers'] = [
            $offer1->getPublicId(),
            $offer2->getPublicId(),
        ];

        $res = $this->startTest();

        $order = $this->getLastEntity('order', true);

        //
        // Need to fetch both entity_offer entities separately since
        // there's no guarantee on the ordering of entity_offers
        //

        $entityOffer1 = $this->getEntities('entity_offer', ['offer_id' => $offer1->getPublicId()], true)['items'][0];
        $this->assertEquals($offer1->getId(), $entityOffer1['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer1['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer1['entity_type']);

        $entityOffer2 = $this->getEntities('entity_offer', ['offer_id' => $offer2->getPublicId()], true)['items'][0];
        $this->assertEquals($offer2->getId(), $entityOffer2['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer2['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer2['entity_type']);
    }

    public function testCreateOrderWithRepeatedOffers()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offers'] = [
            $offer->getPublicId(),
            $offer->getPublicId(),
        ];

        //
        // Backward compatible
        //
        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();
        $this->testData[__FUNCTION__]['response']['content']['offers'][] = $offer->getPublicId();

        $this->startTest();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(false, $order['force_offer']);

        // Pivot table entry also got created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer['entity_type']);
    }

    public function testCreateOrderWithOfferIDDifferentProduct()
    {
        $this->mockRazorX(__FUNCTION__, 'offer_on_subscription', 'on');

        $offer = $this->fixtures->create('offer:card',
            [
                'active'       => 1,
                'product_type' => 'subscription',
            ]);

        $subOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $offer->getId(),
        ]);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateCardWithMaxAmountMoreThan1000000()
    {
        $this->startTest();
    }

    public function testCreateCardWithMaxAmountLessThanZero()
    {
        $this->startTest();
    }

    public function testCreateCardWithNoMaxAmount()
    {
        $this->startTest();
    }

    public function testCreateOrderWithOffersAndOfferID()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offers'][] = $offer->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateOrderWithOfferAndDiscounting()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();

        $order = $this->startTest();

        // Pivot table entry also got created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($order['id'], 'order_' . $entityOffer['entity_id']);
        $this->assertEquals($order['entity'], $entityOffer['entity_type']);
    }

    public function testCreateOrderWithNotApplicableOffer()
    {
        $offer = $this->fixtures->create('offer:card', [
            'active' => false,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();

        // Pivot table entry did not get created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertNull($entityOffer);
    }

    public function testCreateOrderWithExpiredOffer()
    {
        $offer = $this->fixtures->create('offer:expired');

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();

        // Pivot table entry did not get created
        $entityOffer = $this->getLastEntity('entity_offer', true);
        $this->assertNull($entityOffer);
    }

    public function testPaymentWithOfferAppliedOnOrder()
    {
        $this->mockCardVault();
        $this->mockCardVaultWithCryptogram();
        $this->testCreateOrderWithOffer();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $rzpPayment = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');
    }

    public function testPaymentWithFailedOfferCheck()
    {
        $this->fixtures->merchant->enableMobikwik();

        $this->testCreateOrderWithOffer();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultWalletPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithCheckoutDisplayOffer()
    {
        $this->mockCardVaultWithCryptogram();
        $this->setUpTerminals();

        $offer = $this->fixtures->create('offer', [
            'payment_method'   => 'wallet',
            'issuer'           => 'amazonpay',
            'starts_at'        => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'checkout_display' => 1,
        ]);

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'amount' => 1000,
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['offer_id'] = $offer->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'offer_id'    => $offer->getId(),
            'amount'      => 1000,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');
    }

    public function testPaymentOnOfferWithNullMethod()
    {
        $this->mockCardVault();
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $offer = $this->fixtures->create('offer', [
            'starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'issuer' => 'HDFC',
        ]);

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'offer_id' => $offer->getId(),
            'amount' => 1000,
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'offer_id'    => $offer->getId(),
            'amount'      => 1000,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');
    }

    public function testPaymentWithFailedOfferCheckOnNullMethodOffer()
    {
        $offer = $this->fixtures->create('offer', [
            'starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'issuer' => 'HDFC',
            'error_message' => 'Custom error message'
        ]);

        $this->fixtures->merchant->enableMobikwik();

        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'merchant_id' => '10000000000000',
            'amount'      => 1000,
            'force_offer' => true,
        ]);

        $payment = $this->getDefaultWalletPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithFailedOfferCheckOnInternational()
    {

        // Will fix this later
        $this->markTestSkipped();

        $offer = $this->fixtures->create('offer', [
            'starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'international' => true,
            'error_message' => 'Selected Card is not international but offer applied requires international card'
        ]);

        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'merchant_id' => '10000000000000',
            'amount'      => 1000,
            'force_offer' => true,
        ]);

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment['card']['number'] = '4012010000000007';
        $this->doAuthPayment($payment);
    }

    public function testPaymentWithFailedOfferWithCustomErrorMessage()
    {
        $this->fixtures->merchant->enableMobikwik();

        $offer = $this->fixtures->create('offer:card', ['error_message' => 'Payment Method is not available for this Offer']);

        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'force_offer' => true,
        ]);

        $payment = $this->getDefaultWalletPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithBlockPaymentDisabledOnOffer()
    {
        $offer = $this->fixtures->create('offer:card', ['block' => false]);

        $order = $this->fixtures->create('order:with_undiscounted_offer_applied', [
            'offer_id' => $offer->getId()
        ]);

        $this->fixtures->merchant->enableMobikwik();

        $payment = $this->getDefaultWalletPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->fixtures->terminal->createSharedMobikwikTerminal();

        $rzpPayment = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);
        $this->assertEquals($order->getPublicId(), $rzpPayment['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithOfferOnNullMethodAndIinAndIssuer()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->mockCardVault();

        $offer = $this->fixtures->create('offer', [
            'starts_at'     => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'iins'          => ['411111'],
            'issuer'        => 'HDFC',
            'error_message' => 'Selected card does not belong to offer iins',
            'type'          => 'already_discounted'
        ]);

        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'merchant_id' => '10000000000000',
            'amount'      => 1000,
            'force_offer' => true,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        // Test that HDFC netbanking payment passes with the offer
        $this->doAuthAndCapturePayment($payment);

        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'merchant_id' => '10000000000000',
            'amount'      => 1000,
            'force_offer' => true,
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        // Test that offer with invalid IIN fails against the offer
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPartialPaymentOnOrderWithNoPartialPaymentFlag()
    {
        $order = $this->fixtures->create('order');

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH);
        $this->expectExceptionMessage(
            'Your payment amount is different from your order amount. To pay successfully, please try using right amount.');

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testPartialPayment()
    {
        $order = $this->fixtures->create(
            'order',
            [
                'payment_capture' => true,
                'partial_payment' => true,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    /**
     * Having run above test(made a partial payment), this test attempts to
     * make another payment with amount greater than the current due of order.
     */
    public function testPartialPaymentTooMuchAmount()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 500001;

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE);
        $this->expectExceptionMessage(
            'Payment amount is greater than the amount due for order');

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testMultiplePartialPayments()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');

        // Order was for 1000000.
        // Amount due: 500000, as 500000 was paid in above first payment.

        // Will make another 2 payments to make amount due 0 and check the order
        // status at both stage.

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 250000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order['id'],
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(750000, $order['amount_paid']);
        $this->assertEquals(250000, $order['amount_due']);

        // 2nd payment >

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 250000;

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(1000000, $order['amount_paid']);
        $this->assertEquals(0, $order['amount_due']);
    }

    public function testPartialPaymentAndRefund()
    {
        $this->testPartialPayment();

        $payment = $this->getLastEntity('payment');

        // Payment was done for 500000 (above ^). Due amount is 500000.

        // Will refund the payment in 2 calls (partial refunds) and check
        // order's attributes at both stage.
        //
        // Refund should not affect order's attributes in any way.

        $this->refundPayment($payment['id'], 250000);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);

        // 2nd refund

        $this->refundPayment($payment['id']);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    public function testPartialPaymentAndNoAutoCapture()
    {
        $order = $this->fixtures->create(
            'order',
            [
                'payment_capture' => false,
                'partial_payment' => true,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $expectedPaymentResponse = [
            'status'   => 'authorized',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(1, $order['attempts']);

        $this->assertEquals(0, $order['amount_paid']);
        $this->assertEquals(1000000, $order['amount_due']);

        // Capture the payment

        $this->capturePayment($payment['id'], $payment['amount']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    public function testPaymentWithMaxPaymentCountOfferAppliedOnOrderWithNoCardSaving()
    {
        //Will fix this later
        $this->markTestSkipped();
        $this->setUpTerminals();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
            'type'   => 'already_discounted'
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer);

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountAppliedOnOrderWithGlobalSavedCard()
    {
        // Will fix this later
        $this->markTestSkipped();
        $this->setUpTerminals();
        $this->mockSession();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
            'type' => 'already_discounted'
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'save' => 1
        ]);

        $this->doAuthAndCapturePayment($payment);

        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'card' => ['cvv' => 111],
            'token' => $token['token'],
        ]);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountAppliedOnOrderWithLocallySavedCard()
    {
        //Will fix this later
        $this->markTestSkipped();

        $this->setUpTerminals();
        $this->mockSession();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
            'type'      => 'already_discounted'
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'save' => 1,
            'customer_id' => 'cust_100000customer',
        ]);

        $this->doAuthAndCapturePayment($payment);

        $this->mockCardVaultWithCryptogram();

        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'customer_id' => 'cust_100000customer',
            'card' => ['cvv' => 111],
            'token' => $token['token'],
        ]);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountOfferButPaymentsAlreadyMadeOnLinkedOffers()
    {
        //Will fix this later
        $this->markTestSkipped();

        $this->setUpTerminals();

        $offer1 = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
            'type'      => 'already_discounted'
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer1);

        $this->doAuthAndCapturePayment($payment);

        $offer2 = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'linked_offer_ids' => (array) $offer1->getId(),
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer2);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPartialPaymentExcessAmount()
    {
        $this->mockCardVaultWithCryptogram();
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);
        $order = $this->fixtures->create(
            'order',
            [
                'payment_capture' => true,
                'partial_payment' => true,
                'amount'          => 50000,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 70000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(70000, $order['amount_paid']);
        $this->assertEquals(-20000, $order['amount_due']);
    }

    public function testPartialPaymentExcessAmountMultiple()
    {
        $this->mockCardVaultWithCryptogram();
        $this->fixtures->merchant->addFeatures(['excess_order_amount']);
        $order = $this->fixtures->create(
            'order',
            [
                'payment_capture' => true,
                'partial_payment' => true,
                'amount'          => 50000,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 30000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(30000, $order['amount_paid']);
        $this->assertEquals(20000, $order['amount_due']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount']   = 50000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order['id'],
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(80000, $order['amount_paid']);
        $this->assertEquals(-30000, $order['amount_due']);
    }

    public function testPartialPaymentExcessAmountManualCaptureFailure()
    {
        $this->mockCardVaultWithCryptogram();
        $order = $this->fixtures->create(
            'order',
            [
                'payment_capture' => false,
                'partial_payment' => true,
                'amount'          => 50000,
            ]);

        $payment = $this->getDefaultPaymentArray();
        $payment2 = $this->getDefaultPaymentArray();
        $payment3 = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment2['order_id'] = $order->getPublicId();
        $payment3['order_id'] = $order->getPublicId();

        $payment['amount']   = 30000;
        $payment2['amount']   = 30000;
        $payment3['amount']   = 30000;

        $expectedPaymentResponse = [
            'status'   => 'authorized',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);
        $payment2 = $this->doAuthAndGetPayment($payment2, $expectedPaymentResponse);

        $this->capturePayment($payment['id'], $payment['amount']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment2)
        {
            $this->capturePayment($payment2['id'], $payment2['amount']);
        });

        $this->fixtures->merchant->addFeatures(['excess_order_amount']);

        $payment3 = $this->doAuthAndGetPayment($payment3, $expectedPaymentResponse);

        $this->capturePayment($payment3['id'], $payment3['amount']);

        $order = $this->getLastEntity('order');

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(60000, $order['amount_paid']);
        $this->assertEquals(-10000, $order['amount_due']);
    }

    protected function setUpTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockCardVault();
    }

    protected function createOrderWithOfferAppliedAndGetPaymentArray($offer, array $additionalPaymentAttributes = [])
    {
        $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
            'force_offer' => true
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $payment = array_merge($payment, $additionalPaymentAttributes);

        return $payment;
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function retrieveOrdersDefault(array $content = [], $method = 'GET')
    {
        $request = array(
            'method'  => $method,
            'url'     => '/orders',
            'content' => $content
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function validateFees($payment)
    {
        $feesArray = $this->createAndGetFeesForPayment($payment);

        if ($payment['amount'] === 50000)
        {
            $this->assertEquals(1173, $feesArray['input']['fee']);

            $this->assertEquals(1.49, $feesArray['display']['tax']);
        }

        return $feesArray;
    }

    public function testForceAuthorizeAndAutoCaptureOrder()
    {
        $this->testCreateAutoCaptureOrder();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $server = $this->mockServer('axis_migs')
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content) {
                $content['vpc_TxnResponseCode'] = '5';
            })->mock();

        $this->setMockServer($server, 'axis_migs');

        $this->makeRequestAndCatchException(function () use ($payment) {
            $content = $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $gatewayPayment = $this->getLastEntity('axis_migs', true);

        $this->fixtures->merchant->edit($payment['merchant_id'],
            [
                'auto_capture_late_auth' => 1
            ]);

        $content = ['vpc_TransactionNo' => ($gatewayPayment['vpc_TransactionNo'] + 1)];

        $this->resetMockServer();

        $server = $this->mockServer('axis_migs')
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content) {
                $content['vpc_TxnResponseCode'] = '0';
            })->mock();

        $this->setMockServer($server, 'axis_migs');

        $this->forceAuthorizeFailedPayment($payment['id'], $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
    }

    /*
     * below test is failing on github action
     */
    public function testForceAuthorizeAndAutoCaptureOrderPricingError()
    {
        $this->markTestSkipped('Failing on github action');
        $this->testCreateAutoCaptureOrder();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $server = $this->mockServer('axis_migs')
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content) {
                $content['vpc_TxnResponseCode'] = '5';
            })->mock();

        $this->setMockServer($server, 'axis_migs');

        $this->makeRequestAndCatchException(function () use ($payment) {
            $content = $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $gatewayPayment = $this->getLastEntity('axis_migs', true);

        $this->fixtures->merchant->edit($payment['merchant_id'],
            [
                'auto_capture_late_auth' => 1
            ]);

        $content = ['vpc_TransactionNo' => ($gatewayPayment['vpc_TransactionNo'] + 1)];

        $this->resetMockServer();

        $server = $this->mockServer('axis_migs')
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content) {
                $content['vpc_TxnResponseCode'] = '0';
            })->mock();

        $this->setMockServer($server, 'axis_migs');

        $txnClass = \Mockery::mock('overload:RZP\Models\Transaction\Core')->makePartial();

        $txnClass->shouldReceive('createOrUpdateFromPaymentCaptured')->andThrow(new Exception\LogicException('Invalid rule count: 0, Merchant Id: ', ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT, ['payment_id' => 'test', 'method' => 'default']));

        $this->forceAuthorizeFailedPayment($payment['id'], $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testOrderEditNotes()
    {
        $requestContent = $this->testData['testCreateOrder']['request']['content'];

        $this->testData['testCreateOrder']['request']['content'] = array_merge($requestContent, [
            'notes' => ['key' => 'value'],
        ]);

        $order = $this->testCreateOrder();

        $this->testData[__FUNCTION__]['request']['url'] = '/orders/' . $order['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testCreateOrderWithConfigId()
    {
        $config = $this->fixtures->create('config');

        $this->fixtures->merchant->addFeatures(['send_payment_config_id']);

        $this->testData[__FUNCTION__]['request']['content']['checkout_config_id'] = $config->getPublicId();

        $response = $this->startTest();

        $this->assertEquals($config->getPublicId(), $response['checkout_config_id']);
    }


    public function testRetrieveOrderPaymentsCard()
    {
        $order = $this->fixtures->create('order');

        $this->ba->privateAuth();

        $this->mockCardVaultWithCryptogram();

        $orders = $this->retrieveOrdersDefault();

        //GIVEN
        $receipt = $orders['items'][0]['receipt'];

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = 'order_' . $order['id'];
        $payment['amount'] = $order['amount'];

        $this->doAuthPayment($payment);

        $this->ba->privateAuth();

        $order = $this->retrieveOrdersDefault(['expand' => ['payments.card']]);

        $this->assertEquals($order['items'][0]['id'], $order['items'][0]['payments']['items'][0]['order_id']);

        $this->assertArrayHasKey('card', $order['items'][0]['payments']['items'][0]);
    }

    public function testCreateOrderWithValidProductType()
    {
        $this->startTest();

        $this->getLastEntity('order', true);

        $order = $this->getLastEntity('order', true);

        self::assertEquals($order['product_id'], "somerandtestId");

        self::assertEquals($order['product_type'], "invoice");
    }

    public function testCreateOrderWithPlV2ProductType()
    {
        $this->startTest();

        $this->getLastEntity('order', true);

        $order = $this->getLastEntity('order', true);

        self::assertEquals($order['product_id'], "somerandtestId");

        self::assertEquals($order['product_type'], "payment_link_v2");
    }

    public function testUpdateOrderWithPartialPayment()
    {
        $this->testCreateOrderWithPlV2ProductType();

        $this->ba->privateAuth();

        $this->mockCardVaultWithCryptogram();

        $order = $this->getDbLastEntity('order');

        self::assertFalse($order->isPartialPaymentAllowed());

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/orders/'.$order->getPublicId();

        $this->startTest();

        $order = $this->getDbLastEntity('order');

        self::assertTrue($order->isPartialPaymentAllowed());

        self::assertEquals($order->getFirstPaymentMinAmount(), 3434);
    }

    public function testUpdateOrderWithPartialPaymentWithInvalidProductType()
    {
        $this->testCreateOrderWithValidProductType();

        $this->ba->privateAuth();

        $this->mockCardVaultWithCryptogram();

        $order = $this->getDbLastEntity('order');

        self::assertFalse($order->isPartialPaymentAllowed());

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/orders/'.$order->getPublicId();

        $this->startTest();
    }

    public function testUpdateOrderWithPartialPaymentWithLargerAmount()
    {
        $this->testCreateOrderWithPlV2ProductType();

        $this->ba->privateAuth();

        $order = $this->getDbLastEntity('order');

        self::assertFalse($order->isPartialPaymentAllowed());

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/orders/'.$order->getPublicId();

        $this->startTest();
    }

    public function testUpdateOrderSuccessFromPGRouter()
    {
        $this->ba->pgRouterAuth();

        $this->mockCardVaultWithCryptogram();

        $order = $this->fixtures->create('order');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/orders/'.$order->getPublicId();

        $testData['request']['content']['merchant_id'] = $order->getMerchantId();

        $this->startTest();
    }

    public function testUpdateOrderSuccessThroughOrderOutbox()
    {
        $orderId = $this->fixtures->generateUniqueId();

        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('updateInternalOrder')
            ->with(Mockery::type('array'), Mockery::type('string'), Mockery::type('string'), Mockery::type('bool'))
            ->andReturnUsing(function (array $input, string $orderId, string $merchantId, bool $throwExceptionOnFailure)
            {
                $this->fixtures->stripSign($orderId);

                $order = [
                    "amount_paid"   => 1000,
                    "status"        => "paid",
                ];

                return (new Order\Entity())->forceFill($order);
            });

        $orderOutbox = $this->fixtures->create('order_outbox', [
            'order_id'      => $orderId,
            'created_at'    => Carbon::yesterday(Timezone::IST)->addHour(1)->timestamp
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $orderOutbox->reload();

        $this->assertNotNull($orderOutbox['deleted_at']);
    }

    public function testUpdateOrderFailureThroughOrderOutbox()
    {
        $orderId = $this->fixtures->generateUniqueId();

        $this->enablePgRouterConfig();
        $pgService = Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('updateInternalOrder')
            ->with(Mockery::type('array'), Mockery::type('string'), Mockery::type('string'), Mockery::type('bool'))
            ->andReturnUsing(function (array $input, string $orderId, string $merchantId, bool $throwExceptionOnFailure)
            {
                throw new Exception\ServerErrorException('PG Router Response cannot be null', ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);;
            });

        $orderOutbox = $this->fixtures->create('order_outbox', [
            'order_id'      => $orderId,
            'created_at'    => Carbon::yesterday(Timezone::IST)->addHour(1)->timestamp
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $orderOutbox->reload();

        $this->assertNull($orderOutbox['deleted_at']);
    }

    public function testCreateOrderWithInvalidValidProductType()
    {
        $this->startTest();
    }

    public function testCreateOrderWithProductIdMissing()
    {
        $this->startTest();
    }

    public function testCreateOrderWithProductTypeMissing()
    {
        $this->startTest();
    }

    public function testCreateOrderWithoutTaxInvoice()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateOrder'];

        $response = $this->startTest();

        $this->assertArrayNotHasKey('tax_invoice', $response);
    }

    public function testCreateOrderWithAmountGreaterThanMaxAmountAndCurrencyUSD()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            Entity::INTERNATIONAL         => true,
            Entity::CONVERT_CURRENCY      => true,
            ENTITY::MAX_PAYMENT_AMOUNT    => 10000,
            ENTITY::MAX_INTERNATIONAL_PAYMENT_AMOUNT    => 10000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $merchantDetailAttribute = [
            Entity::MERCHANT_ID             => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->startTest();
    }

    public function testOrderForMerchantDisabledBank()
    {
        $this->setMerchantBanks(['SBIN']);

        $data = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $this->mockCardVaultWithCryptogram();

        $this->runRequestResponseFlow($data);

        $this->setMerchantBanks(['ICIC']);

        $orderData = [
            Order\Entity::METHOD => 'netbanking',
            Order\Entity::BANK   => 'ICIC',
            Order\Entity::AMOUNT => 10000,
        ];

        $this->createOrder($orderData);

        $orderEntity = $this->getDbLastOrder()->toArray();

        $this->assertNotNull($orderEntity['id']);
        $this->assertEquals('created', $orderEntity['status']);
        $this->assertEquals($orderData[Order\Entity::METHOD], $orderEntity['method']);
        $this->assertEquals($orderData[Order\Entity::BANK], $orderEntity['bank']);
        $this->assertEquals($orderData[Order\Entity::AMOUNT], $orderEntity['amount']);
    }

    public function testOrderFor1CC()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);

        $orderData = [
            Order\Entity::AMOUNT                              => 10000,
            Order\Entity::RECEIPT                             => 'R1',
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL => 10000,
        ];

        $this->createOrder($orderData);
        $order = $this->getDbLastOrder();
        $orderEntity = $this->fetchOrderById($order->getPublicId());

        $this->assertNotNull($orderEntity);
        $this->assertEquals($orderData[Order\Entity::AMOUNT], $orderEntity['amount']);
        $this->assertEquals($orderData[Order\Entity::RECEIPT], $orderEntity['receipt']);
        $this->assertEquals($orderData[Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL],
            $orderEntity[Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL]);
    }

    public function testCreateOrderFor1CCWithLineItems()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);

        $orderData = [
            Order\Entity::AMOUNT                              => 10000,
            Order\Entity::RECEIPT                             => 'R1',
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL => 10000,
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS       => [
                [
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_NAME => 'Line Item 1',
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_PRICE => 1000,
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_QUANTITY => 1,
                ],
                [
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_NAME => 'Line Item 2',
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_PRICE => 2000,
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_QUANTITY => 2,
                ],
            ],
        ];

        $this->createOrder($orderData);
        $order = $this->getDbLastOrder();
        $orderEntity = $this->fetchOrderById($order->getPublicId());

        $this->assertNotNull($orderEntity);
        $this->assertEquals($orderData[Order\Entity::AMOUNT], $orderEntity['amount']);
        $this->assertEquals($orderData[Order\Entity::RECEIPT], $orderEntity['receipt']);
        $this->assertEquals($orderData[Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL],
            $orderEntity[Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL]);
        $this->assertTrue(empty($orderEntity[Order\OrderMeta\Order1cc\Fields::LINE_ITEMS]));
    }

    public function testCreateOrderFor1CCWithInvalidLineItems()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);

        $this->startTest();
    }

    protected function fetchOrderById(string $publicOrderId)
    {
        $request = [
            'url'       => "/orders/$publicOrderId",
            'method'    => 'GET',
        ];
        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetch1ccOrderById(string $publicOrderId)
    {
        $request = [
            'url'       => "/1cc/orders/$publicOrderId",
            'method'    => 'GET',
        ];
        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testCreateOrderWithConvenienceFeeConfigEmpty()
    {
        $data = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $orderEntity = $this->getDbLastOrder()->toArray();

        $this->assertEquals(null, $orderEntity['reference7']);
    }

    public function testCreateOrderWithConvenienceFeeConfigMerchantNotOnDynamicFeeBearer()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant', '10000000000000',['fee_bearer' => 'customer']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);
    }

    public function testCreateOrderWithConvenienceFeeConfigMerchantOnDynamicFeeBearer()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $orderEntity = $this->getDbLastOrder()->toArray();

        $this->assertNotNull($orderEntity['reference7']);

        $config = $this->getDbEntityById('config', $orderEntity['reference7']);

        $this->assertEquals('{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 20}}}}', $config->getConfig());
    }

    public function testCreateOrderWithConvenienceFeeConfigWithDifferentCurrency()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['convert_currency' => true, 'fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $orderEntity = $this->getDbLastOrder()->toArray();

        $this->assertNotNull($orderEntity['reference7']);

        $config = $this->getDbEntityById('config', $orderEntity['reference7']);

        $this->assertEquals('{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 20}}}}', $config->getConfig());
    }

    public function testFetch1ccOrderWithOffer()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"],
                                                             'error_message' => 'Payment Method is not available for this Offer']);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();

        $this->ba->privateAuth();

        $this->mockCardVaultWithCryptogram();

        $order = $this->startTest();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $this->doAuthPayment($payment);

        $response = $this->fetch1ccOrderById($order['id']);

        $this->assertEquals($offer->getPublicId(), $response['offer']['id']);
    }

    public function testCanRouteToPgRouterFor1cc()
    {
        $this->fixtures->merchant->addFeatures([FeatureConstants::ONE_CLICK_CHECKOUT]);
        $merchant = $this->getDbEntityById('merchant', '10000000000000');
        $orderService = new Order\Service();
        $result = $orderService->canRouteOrderCreationToPGRouter(['line_items_total' => 2000], $merchant);
        $this->assertEquals(false, $result);
    }

    public function testOrderNoteUpdateFor1CC()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);

        $orderData = [
            Order\Entity::AMOUNT                              => 10000,
            Order\Entity::RECEIPT                             => 'R1',
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL => 10000,
        ];

        $this->createOrder($orderData);

        $order = $this->getDbLastOrder();

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/orders/1cc/'.$order->getPublicId().'/order-notes';

        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'one_cc_capture_gstin',
                'value'       => true
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'one_cc_capture_order_instructions',
                'value'       => true
            ]
        );

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchOrderDetailsForCheckout(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['order'] = [
            'id' => 'abcdef1234567',
            'amount' => 50000,
            'partial_payment'   => false,
            'currency'          => 'INR',
            'amount_paid'       => 0,
            'amount_due'        => 50000,
            'first_payment_min_amount' => null,
        ];

        $this->startTest();
    }

    public function testFetchOrderDetailsForCheckoutWithAllPossibleFieldsInResponse(): void
    {
        $this->fixtures->merchant->addFeatures([
            FeatureConstants::TPV,
            FeatureConstants::ONE_CLICK_CHECKOUT,
        ]);

        $orderData = [
            'amount'        => 50000,
            'receipt'       => 'rcptid42',
            'method'        => 'netbanking',
            'bank_account'  => [
                'account_number'    => '040304030403040',
                'ifsc'              => 'UTIB0003098',
                'name'              => 'ThisIsAwesome',
            ],
            'line_items_total' => 50000,
            'line_items' => [
                [
                    'type' => 'e-commerce',
                    'sku' => '1g234',
                    'variant_id' => '12r34',
                    'other_product_codes' => [
                        'upc' => '12r34',
                        'ean' => '123r4',
                        'unspsc' => '123s4'
                    ],
                    'price' => '20000',
                    'offer_price' => '20000',
                    'tax_amount' => 0,
                    'quantity' => 1,
                    'name' => 'TEST',
                    'description' => 'TEST',
                    'weight' => '1700',
                    'dimensions' => [
                        'length' => '1700',
                        'width' => '1700',
                        'height' => '1700'
                    ],
                    'image_url' => 'http://url',
                    'product_url' => 'http://url',
                    'notes' => []
                ],
                [
                    'type' => 'e-commerce',
                    'sku' => '1g235',
                    'variant_id' => '12r34',
                    'other_product_codes' => [
                        'upc' => '12r34',
                        'ean' => '123r4',
                        'unspsc' => '123s4'
                    ],
                    'price' => '30000',
                    'offer_price' => '30000',
                    'tax_amount' => 0,
                    'quantity' => 1,
                    'name' => 'TEST',
                    'description' => 'TEST',
                    'weight' => 1700,
                    'dimensions' => [
                        'length' => 1700,
                        'width' => 1700,
                        'height' => 1700
                    ],
                    'image_url' => 'http://url',
                    'product_url' => 'http://url',
                    'notes' => []
                ]
            ]
        ];

        $order = $this->createOrder($orderData);

        $this->ba->checkoutServiceProxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['order'] = [
            'id' => substr($order['id'], 6),
            'amount' => 50000,
            'partial_payment'   => false,
            'currency'          => 'INR',
            'amount_paid'       => 0,
            'amount_due'        => 50000,
            'first_payment_min_amount' => null,
            'receipt' => 'rcptid42',
            'bank' => 'UTIB',
            'method' => 'netbanking',
            'account_number' => '040304030403040'
        ];

        $this->startTest();
    }

    public function testFetchOrderDetailsForCheckoutWithExpandOrder(): void
    {
        $this->fixtures->merchant->addFeatures([
            FeatureConstants::TPV,
            FeatureConstants::ONE_CLICK_CHECKOUT,
        ]);

        $orderData = [
            Order\Entity::AMOUNT                              => 50000,
            Order\Entity::RECEIPT                             => 'R1',
            Order\Entity::BANK_ACCOUNT                        => [
                'account_number' => '040304030403040',
                'ifsc'           => 'UTIB0003098',
                'name'           => 'ThisIsAwesome',
            ],
            Order\Entity::METHOD                              => 'netbanking',
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL => 50000,
            Order\OrderMeta\Order1cc\Fields::LINE_ITEMS       => [
                [
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_NAME     => 'Line Item 1',
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_PRICE    => 10000,
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_QUANTITY => 1,
                ],
                [
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_NAME     => 'Line Item 2',
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_PRICE    => 20000,
                    Order\OrderMeta\Order1cc\Fields::LINE_ITEM_QUANTITY => 2,
                ],
            ],
        ];

        $order = $this->createOrder($orderData);

        $this->ba->checkoutServiceProxyAuth();

        $orderId = str_replace('order_', '', $order['id']);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order['id'];
        $this->testData[__FUNCTION__]['response']['content']['order']['id'] = $orderId;
        $this->testData[__FUNCTION__]['response']['content']['order']['order_metas'][0]['order_id'] = $orderId;

        $this->startTest();
    }

    public function testFetchOrderDetailsForCheckoutWithSubscriptionId(): void
    {
        $subscriptionId = UniqueIdEntity::generateUniqueId();

        $orderData = [
            'id'                       => 'TestOrder10000',
            'amount'                   => 50000,
            'partial_payment'          => false,
            'currency'                 => 'INR',
            'first_payment_min_amount' => null,
        ];

        $order = $this->fixtures->order->create($orderData);

        $this->fixtures->invoice->create([
            'order_id'        => $order->getId(),
            'subscription_id' => $subscriptionId,
            'status'          => 'issued',
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['subscription_id'] = 'sub_' . $subscriptionId;

        $this->startTest();
    }

    public function test1CCOrderWithOffer()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);

        $testData = $this->testData[__FUNCTION__];

        $orderData = [
            Order\Entity::AMOUNT    => 100000,
            Order\Entity::RECEIPT   => 'R1',
            Order\Entity::CURRENCY  => 'INR'
        ];

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1
        ], $orderData);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $testData['request']['url'] = '/order/'.$order->getPublicId().'/payment_offers';

        $this->ba->publicAuth();

        $response = $this->startTest($testData);

        $this->assertEquals($offer1->getPublicId(), $response['offers'][0]['id']);
    }

    public function testCurrencyForTurkishLiraEnabled()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => false]);
        $this->startTest();
    }

}
