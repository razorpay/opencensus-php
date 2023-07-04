<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Mode;
use RZP\Services\EsClient;
use RZP\Models\Card\Network;
use RZP\Models\Address\Type;
use RZP\Models\Card\Repository;
use RZP\Models\NetbankingConfig;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;


use RZP\Error\PublicErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Services\Dcs;
use RZP\Jobs\EsSync;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Traits\MocksSplitz;

class PaymentCreateTest extends TestCase
{
    use OAuthTrait;
    use MocksSplitz;
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InvoiceTestTrait;
    use TerminalTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreatePaymentWithBillingAddress()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $billingAddressArray = $this->getDefaultBillingAddressArray();

        $paymentArray['billing_address'] = $billingAddressArray;

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        // fetching payment in admin auth.
        $paymentEntity = $this->getLastPayment(true);

        $addressEntity = $this->getLastEntity('address', true);


        $this->assertEquals($paymentFromResponse['id'], $paymentEntity['id']);

        $this->assertEquals($paymentEntity['id'], $addressEntity['entity_id']);

        $this->assertEquals('payment', $addressEntity['entity_type']);

        foreach (['line1', 'line2', 'city', 'state', 'country'] as $attribute)
        {
            $this->assertEquals($billingAddressArray[$attribute], $addressEntity[$attribute]);
        }

        // address entity stores zip code as "zipcode"
        // in input to paymentcreate, we get zip code as "postal_code"
        $this->assertEquals($billingAddressArray['postal_code'], $addressEntity['zipcode']);
    }

    public function testCreatePaymentWithBillingAddressWithMandatoryFieldsMissing()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $originalBillingAddressArray = $this->getDefaultBillingAddressArray();

        foreach(['line1', 'city', 'country'] as $mandatoryField)
        {
            $billingAddressArray = $originalBillingAddressArray;

            unset($billingAddressArray[$mandatoryField]);

            try
            {
                $paymentArray['billing_address'] = $billingAddressArray;

                $this->doAuthAndCapturePayment($paymentArray);

                $this->fail('Should have thrown exception because ' . $mandatoryField . ' was missing');
            }
            catch(Exception\BadRequestValidationFailureException $exception)
            {
                $this->assertStringContainsString($mandatoryField, $exception->getMessage());
            }
        }
    }

    public function testCreatePaymentWithBillingAddressWithOptionalFieldsMissing()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $originalBillingAddressArray = $this->getDefaultBillingAddressArray();

        foreach(['line2', 'postal_code', 'state'] as $optionalField)
        {
            $billingAddressArray = $originalBillingAddressArray;

            unset($billingAddressArray[$optionalField]);

            $paymentArray['billing_address'] = $billingAddressArray;

            $this->doAuthAndCapturePayment($paymentArray);
        }
    }


    public function testCreatePaymentWithoutBillingAddress()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentFromResponse = $this->doAuthAndCapturePayment($paymentArray);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals($paymentFromResponse['id'], $paymentEntity['id']);

        $addressEntity = $this->getDbEntity('address', [
                                 'entity_type' => 'payment',
                                 'entity_id'   => Entity::verifyIdAndStripSign($paymentFromResponse['id'])
                            ]);

        $this->assertNull($addressEntity);
    }

    public function testCreatePaymentWithoutOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function mockCps($terminal, $responder, $paymentId)
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('GET', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($paymentId, $terminal, $responder)
            {
                switch ($responder)
                {
                    case 'entity_fetch':
                        return $this->mockCpsEntityFetch($url, $paymentId);
                }
            });
    }

    protected function mockCpsEntityFetch($url, $paymentId)
    {
        $id= str_replace("pay_","",$paymentId);
        switch ($url)
        {
            case 'entity/authentication/'.$id:
                return [
                    'id' => 'Flj87LBAuB6JcE',
                    'created_at' => 1602011616,
                    'payment_id' => $id,
                    'merchant_id' => 'CCOhinUeUsT8HN',
                    'attempt_id' => 'Flj87KPgVIXUjX',
                    'status' => 'skip',
                    'gateway' => 'card_fss',
                    'terminal_id' => 'DfqXJH6OO9NEU5',
                    'gateway_merchant_id' => 'escowrazcybs',
                    'enrollment_status' => 'Y',
                    'pares_status' => 'Y',
                    'acs_url' => '',
                    'eci' => '05',
                    'commerce_indicator' => '',
                    'xid' => 'ODUzNTYzOTcwODU5NzY3Qw==',
                    'cavv' => '3q2+78r+ur7erb7vyv66vv\\/\\/8=',
                    'cavv_algorithm' => '1',
                    'notes' => '',
                    'error_code' => '',
                    'gateway_error_code' => '',
                    'gateway_error_description' => '',
                    'gateway_transaction_id1' => '',
                    'gateway_reference_id1' => '',
                    'gateway_reference_id2' => '100222021120200000000742753928',
                    'success' => true
                ];
            default:
                return [
                    'error' => 'CORE_FAILED_TO_FIND_MODEL',
                    'success' => false,
                ];
        }
    }


    public function testCreatePaymentWithAuthRefRupay()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $payment = $this->doAuthAndCapturePayment($payment);

        $paymentId = $payment['id'];

        $this->mockCps(null, "entity_fetch", $paymentId);

        $request = array(
            'url'     => '/payments/authentication/'.$paymentId,
            'method'  => 'get',
        );

        $this->ba->expressAuth();

        $response = $this->makeRequestAndGetContent($request);

        $paymentFetchResponse = $this->fetchPaymentWithCpsResponse($paymentId, $response);

        $this->assertArrayHasKey('authentication_reference_number', $paymentFetchResponse['acquirer_data']);
    }

    public function testSuccessCreatePaymentForMultipleCurrencies()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $payment = $this->getDefaultPaymentArray();

        foreach ($data as $sucecssPayment)
        {
            $payment['currency'] = $sucecssPayment['currency'];

            $payment['amount'] = $sucecssPayment['amount'];

            $this->doAuthPayment($payment);
        }
    }

    public function testFailedCreatePaymentForMultipleCurrencies()
    {
        $data = $this->testData[__FUNCTION__]['requestData'];

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $payment = $this->getDefaultPaymentArray();

        foreach ($data as $failedPayment)
        {
            $payment['currency'] = $failedPayment['currency'];

            $payment['amount'] = $failedPayment['amount'];

            $responseData = $this->testData[__FUNCTION__]['responseData'];

            $responseData['response']['content']['error']['description'] = 'The amount must be atleast ' .
                $payment['currency'] . ' '. amount_format_IN(Currency::getMinAmount($payment['currency']));

            $this->runRequestResponseFlow($responseData, function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
        }
    }

    public function testCreatePaymentWithValidOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->doAuthPayment($payment);
    }


    public function testCreatePaymentWithValidOrderIdMYR()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['currency'] = 'MYR';

        $this->fixtures->edit('iin', 401200, [
            'country' => 'MY',
            'network' => "Union Pay"
        ]);

        $order = $this->fixtures->create('order', ['id' => '100000000order', 'currency' => 'MYR']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY']);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->doS2SPrivateAuthJsonPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['currency'], "MYR");

        $this->assertEquals($payment['status'], "authorized");

        $this->assertEquals($payment["base_amount"], $payment["amount"]);
    }

    public function testCreatePaymentWithInvalidPaymentCurrency()
    {
        $this->fixtures->edit('iin', 401200, [ 'country' => 'MY']);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order', 'currency' => 'MYR']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        try {

            $this->doAuthPayment($payment);
        }
        catch(\Throwable $e) {
           $this->assertEquals($e->getCode(), "BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH");
        }

    }

    public function testCreatePaymentWithInvalidOrderCurrency()
    {
        $this->fixtures->edit('iin', 401200, [ 'country' => 'MY']);

        $payment = $this->getDefaultPaymentArray();

        $payment['currency'] = 'MYR';

        $this->fixtures->create('order', ['id' => '100000000order', 'currency' => 'INR']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        try {

            $this->doAuthPayment($payment);
        }
        catch(\Throwable $e) {
            $this->assertEquals($e->getCode(), "BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH");
        }

    }

    public function testCreateGooglePayCardPayment()
    {
        $this->enableCpsConfig();

        $order = $this->fixtures->create('order');

        $googlePayPaymentCreateRequestData = $this->testData['googlePayPaymentCreateRequestData'];

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $googlePayPaymentCreateRequestData['order_id'] = $order->getPublicId();

        $googlePayPaymentCreateRequestData['amount']   = $order['amount'];

        $googlePayPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
            );

        $this->fixtures->merchant->addFeatures([Feature\Constants::GOOGLE_PAY_CARDS]);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertEquals($response['type'], 'application');

        $this->assertEquals($response['application_name'], 'google_pay');

        $this->assertEquals($response['request']['method'], 'sdk');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['authentication_gateway'], 'google_pay');

        $this->assertEquals($payment['cps_route'], 0);

        $this->checkPaymentStatus($payment['id'], 'created');
    }

    public function testCreateGooglePayCardPaymentJsonRoute()
    {
        $this->enableCpsConfig();

        $payment = $this->testData['googlePayPaymentCreateRequestData'];
        $payment['amount'] = 100;

        $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', Feature\Constants::GOOGLE_PAY_CARDS]);

        $response = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('next', $response);

        $this->assertEquals('invoke_sdk', $response['next'][0]['action']);

        $this->assertEquals('google_pay', $response['next'][0]['provider']);

        $this->assertArrayHasKey('card', $response['next'][0]['data']['google_pay']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['authentication_gateway'], 'google_pay');

        $this->assertEquals($payment['method'], 'card');

        $this->assertEquals($payment['cps_route'], 0);

        $this->checkPaymentStatus($payment['id'], 'created');
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testCreateGooglePayCardPaymentInvalidCurrency()
    {
        $googlePayPaymentCreateRequestData = $this->testData['googlePayPaymentCreateRequestData'];
        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $googlePayPaymentCreateRequestData['amount'] = 5000;
        $googlePayPaymentCreateRequestData['currency'] = 'USD';
        $googlePayPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        $this->fixtures->create('terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $this->fixtures->merchant->addFeatures([Feature\Constants::GOOGLE_PAY_CARDS]);
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['convert_currency' => 1]);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($googlePayPaymentCreateRequestData)
        {
            $this->doAuthPayment($googlePayPaymentCreateRequestData);
        });
    }

    public function testCreateGooglePayCardS2SPayment()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $googlePayPaymentCreateRequestData = $this->testData['googlePayPaymentCreateRequestData'];

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $googlePayPaymentCreateRequestData['order_id'] = $order->getPublicId();

        $googlePayPaymentCreateRequestData['amount']   = $order['amount'];

        $googlePayPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
            );

        $this->fixtures->merchant->addFeatures(['s2s', Feature\Constants::GOOGLE_PAY_CARDS]);

        $response = $this->doS2SPrivateAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertEquals('google_pay', $response['provider']);

        $this->assertArrayHasKey('card', $response['data']['google_pay']);

        $this->assertEquals(['VISA', 'MASTERCARD'], $response['data']['google_pay']['card']['supported_networks']);

        $this->assertArrayHasKey('gateway_reference_id', $response['data']['google_pay']['card']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['authentication_gateway'], 'google_pay');

        $this->assertEquals($payment['method'], 'card');
    }

    public function testCreateAutoRecurringPayment()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['cvv'] = null;

        $paymentArray['recurring'] = "auto";

        $this->fixtures->merchant->addFeatures(['s2s', Feature\Constants::RECURRING_AUTO]);

        $response = $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals(true, $payment['recurring']);
        $this->assertEquals('auto', $payment['recurring_type']);
    }

    public function testCreateAutoRecurringPaymentBadRequest()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['recurring'] = "auto";

        $this->fixtures->merchant->addFeatures(['s2s']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });
    }

    public function testCreateAutoRecurringPaymentBinNotSupported()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '6074667022059103';

        $this->fixtures->create('iin',
            [
                'iin'       => '607466',
                'issuer'    => 'ICIC',
                'type'      => 'debit',
                'recurring' => 0,
            ]);

        $paymentArray['recurring'] = "auto";

        $this->fixtures->merchant->addFeatures(['s2s', Feature\Constants::RECURRING_AUTO]);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });
    }

    public function testCreateCardPaymentFailedWithRestrictionUpi()
    {
        $payment = $this->getDefaultPaymentArray();

        $order = $this->createOrder(['notes' => ['somekey' => 'some value', 'Pay_Mode' => 'UPI']]);

        $payment['amount'] = 50000;

        $payment['order_id'] = $order['id'];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateUpiPaymentSuccessWithRestrictionUpi()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $order = $this->createOrder(['notes' => ['somekey' => 'some value', 'Pay_Mode' => 'UPI']]);

        $payment['amount'] = 50000;

        $payment['order_id'] = $order['id'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->doAuthPayment($payment);

    }

    public function testCreateCardPaymentWithRestrictionNoUpi()
    {
        $payment = $this->getDefaultPaymentArray();

        $order = $this->createOrder(['notes' => ['somekey' => 'some value', 'Pay_Mode' => 'NOUPI']]);

        $payment['amount'] = 50000;

        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentWithValidOrderIdWithTrace()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory', 'log_response']);

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentWithInvalidMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'invalid';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    // Test to check if payment fails on disabled methods
    public function testCreatePaymentWithDisabledMethod()
    {
        $this->fixtures->merchant->disableNetbanking();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithDisabledInstrument()
    {
        $this->fixtures->merchant->enablePayLater();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'paylater';
        $payment['provider'] = 'lazypay';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaytmTestPaymentWithDisabledMethod()
    {
        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpaypaytm',
            'gateway_secure_secret'     => 'randomsecret',
            'gateway_terminal_id'       => 'nodalaccountpaytm',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'www.merchant.com',
            'enabled'                   =>  1
        );

        $this->fixtures->on('test')->create('terminal', $attributes);

        $this->fixtures->merchant->disablePaytm();

        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

    }

    public function testCreatePaytmTestPaymentWithDisabledMethodWithDisabledTerminal()
    {
        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpaypaytm',
            'gateway_secure_secret'     => 'randomsecret',
            'gateway_terminal_id'       => 'nodalaccountpaytm',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'www.merchant.com',
            'enabled'                   =>  0
        );

        $this->fixtures->on('test')->create('terminal', $attributes);

        $this->fixtures->merchant->disablePaytm();

        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }


    // Test to check if payment is success on enabled methods
    public function testCreatePaymentWithEnabledMethod()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentWithoutMethod()
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['method']);

        $this->doAuthPayment($payment);
    }

    public function testCreatePaymentForNonRegisteredBusinessMoreThanMaxAmount()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            Merchant::CATEGORY => 5399,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID             => $merchantId,
            DetailEntity::BUSINESS_TYPE     => 2,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '50000001';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithoutCardNumber()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['card']['number']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentWithoutContact()
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['contact']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecure()
    {
        $this->payment['card']['number'] = '555555555555558';

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCheckoutCallbackNo3dSecureError()
    {
        $this->payment['card']['number'] = '555555555555559';

        $this->changeEnvToNonTest();

        $content = $this->doAuthPaymentViaCheckoutRoute($this->payment);

        $this->assertEquals($content['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_VALIDATION_FAILURE');
    }

    public function testPaymentWithUnderscoreArray()
    {
        $this->payment['_'] = array('1' => 2, '3' => 4);

        $content = $this->doAuthPayment($this->payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCallbackOnAuthorizedPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->doAuthPayment();
        $id = $payment['razorpay_payment_id'];
    }

    public function testWalletPostFormViaPaymentCreate()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testWalletPostFormWithDummyEmailForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        $payment['contact'] = '+919999999998';
        unset($payment['email']);

        $payment = $this->doAuthPayment($payment, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
        $this->getLastEntity('payment', true);
    }

    public function testWalletPostFormWithDummyEmailAndPhoneForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        unset($payment['contact'], $payment['email'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testWalletPostFormEmailNotOptionalForAmazonPay()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'amazonpay');
        $this->fixtures->merchant->addFeatures(['contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('amazonpay');

        $payment['contact'] = '+919999999998';

        unset($payment['email'], $payment['notes']);

        $data = $this->testData['testWalletPostFormEmailNotOptionalForAmazonPay'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        });
    }

    public function testCoprotoForMissingBankAccountDetailsForFirstRecurring()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB');

        unset($payment['notes']);

        $response = $this->getFormViaCreateRoute($payment, 'emandate.form');

        $content = $response['content'];
        unset($content['bank_account[name]'],
            $content['bank_account[account_number]'],
            $content['bank_account[ifsc]'],
            $content['bank_account[account_type]'],
            $content['aadhaar[number]']);

        $content['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766',
            'account_type'   => 'current',
        ];

        // TODO: Figure out why auth_type is not coming in the form response even though it's present in the input!!
        $content['auth_type'] = 'netbanking';

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $content['amount']]);
        $content['order_id'] = $order->getPublicId();

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertArrayHasKey('razorpay_payment_id', $payment);
    }

    public function testCoprotoForMissingBankAccountDetailsForSecondRecurring()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB');

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766',
            'account_type'   => 'savings',
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        $payment['amount'] = 3000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //
        $response = $this->doS2SRecurringPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('netbanking_axis', $paymentEntity['gateway']);
    }

    public function testRecurringTokenForEmandate()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766',
            'account_type'      => 'savings',
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 2000,
            'expire_by' => $expireBy,
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals(2000, $token['max_amount']);
        $this->assertEquals($expireBy, $token['expired_at']);
    }

    public function testRecurringTokenForSecondRecurringForEmandate()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766',
            'account_type'      => 'savings',
        ];

        $expireBy = Carbon::now(Timezone::IST)->addDays(10)->getTimestamp();

        $payment['recurring_token'] = [
            'max_amount' => 3000,
            'expire_by' => $expireBy,
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        unset($payment['bank_account'], $payment['auth_type']);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();
        // basically, recurring_token max_amount should not matter
        // here because this is second recurring payment
        $payment['recurring_token']['max_amount'] = 1000;

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);
        $paymentEntity = $this->getLastEntity('payment', true);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);

        $this->assertEquals(3000, $token['max_amount']);
    }

    public function testSecondRecurringWithMissingBankAccountDetailsAndAuthType()
    {
        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB', 0);

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766',
            'account_type'   => 'savings',
        ];

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];
        unset($payment['bank_account'], $payment['auth_type']);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //
        $paymentId = $this->doS2SRecurringPayment($payment)['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        // Setting created at to 8 am. Payments for debit are picked from 9 to 9 cycle
        $this->fixtures->edit(
            'payment',
            $paymentId,
            ['created_at' => Carbon::today(Timezone::IST)->addHours(8)->getTimestamp()]
        );

        $this->ba->adminAuth();

        $data = $this->testData[__FUNCTION__];
        $this->startTest($data);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($token['account_number']);
        $this->assertNotNull($token['beneficiary_name']);
        $this->assertNotNull($token['ifsc']);
    }

    public function testEmandatePaymentCreateFailIfBankMissing()
    {
        $payment = $this->setupEmandateAndGetPaymentRequest('ICIC');

        $payment['bank_account'] = [
            'account_number' => '12812891982',
            'name'           => 'test name',
            'ifsc'           => 'UTIB0002766'
        ];

        unset($payment['bank']);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testInternationalPayment()
    {
        $this->fixtures->merchant->enableInternational();
        $this->payment['card']['number'] = '4012010000000007';
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testInternationalPaymentMyMerchantMyCard()
    {
        $this->fixtures->merchant->setCountry('MY');

        $this->fixtures->merchant->edit('10000000000000',
            [
                'convert_currency' => false
            ]
        );

        $this->fixtures->iin->create([
            'iin' => '514024',
            'country' => 'MY',
            'network' => 'MasterCard',
            'type'    => 'credit',
        ]);

        $this->payment['card']['number'] = '5140241918501669';

        $this->doAuthPayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], false);
    }


    public function testInternationalPaymentINMerchantINCard()
    {
        $this->fixtures->merchant->setCountry('IN');

        $this->payment['card']['number'] = '4111111111111111';

        $this->doAuthPayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], false);
    }

    public function testInternationalPaymentINMerchantMYCard()
    {
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->setCountry('IN');

        $this->fixtures->iin->create([
            'iin' => '514024',
            'country' => 'MY',
            'network' => 'MasterCard',
            'type'    => 'credit',
        ]);

        $this->payment['card']['number'] = '5140241918501669';

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->doAuthPayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testRaaSInternationalPayment()
    {
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures('raas');
        $this->payment['card']['number'] = '4012010000000007';
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['international'], true);
    }

    public function testPaymentEmails()
    {
        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);

        $this->fixtures->create('org_hostname', [
            'org_id' => $dummyOrg->getId(),
            'hostname' => 'test.razorpay.com',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $dummyOrg['id']]);

        $this->fixtures->merchant->addFeatures(['dummy']);

        Mail::fake();

        Mail::setFakeConfig();

        $this->doAuthCaptureAndRefundPayment($this->payment);

        Mail::assertQueued(CapturedMail::class);

        Mail::assertNotQueued(AuthorizedMail::class);

        Mail::assertQueued(RefundedMail::class);
    }

    public function testIntlPaymentWhenNotAllowed()
    {
        $this->fixtures->merchant->disableInternational();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED');
    }

    public function testCreatePaymentInEs()
    {
        $expected = $this->testData[__FUNCTION__];

        // Ref to InvoiceTest.testCreateInvoiceAndAssertEsSync() test on why
        // this is being asserted differently.

        $expectedNotes = [
            [
                'key'   => 'merchant_order_id',
                'value' => 'random order id',
            ],
        ];

        $esMock = $this->getMockBuilder(EsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['bulkUpdate'])
            ->getMock();

        $this->app->instance('es', $esMock);

        $esMock->expects($this->exactly(2))
            ->method('bulkUpdate')
            ->with(
                $this->callback(
                    function ($actual) use ($expected, $expectedNotes)
                    {
                        $this->assertArraySelectiveEquals($expected, $actual);

                        $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                        $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                        $this->assertNotEmpty($actual['body'][1]['id']);

                        return true;
                    })
            );

        $this->doAuthPaymentViaCheckoutRoute($this->payment);
    }

    public function testPaymentRoutedThroughCps()
    {
        $this->markTestSkipped();

        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->ba->adminAuth();

        $request = $this->testData[__FUNCTION__]['request'];

        $data = $this->makeRequestAndGetContent($request);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('cps');

        $this->ba->publicAuth();

        $payment = $this->doAuthPayment();

        $pay = $this->getLastEntity('payment', true);

        $this->assertTrue($pay['cps_route']);

        $this->ba->adminAuth();

        $request['content']['cps_service_enabled'] = 0;

        $data = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        $payment = $this->doAuthPayment();

        $pay = $this->getLastEntity('payment', true);

        $this->assertFalse($pay['cps_route']);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForSuccess()
    {
        $payment = $this->doAuthPayment();

        $callbackUrl = $this->recorder->callbackUrl;

        $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($payment, $content);
    }

    public function testPaymentCreateCallingCallbackRouteTwiceForError()
    {
        // This will have raised an insufficient balance error.
        $this->makeRequestAndCatchException(function()
        {
            $this->payment['card']['number'] = '5010101010101015';
            $content = $this->doAuthPayment($this->payment);
            $this->doAuthPayment();
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $callbackUrl = $this->recorder->callbackUrl;

            $response = $this->submitPaymentCallbackData($callbackUrl, 'get', []);
        });
    }

    public function testPaymentS2SAutoCaptureWithoutOrder()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['capture'] = true;

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'captured');
    }

    public function testPaymentS2SAutoCaptureWithoutOrderCaptureFalse()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['capture'] = false;

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testPaymentS2SAutoCaptureWithOrder()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 50000]);

        $payment['capture'] = true;
        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testPaymentS2SPaymentWithSaveandFetchMetaData()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 50000]);

        $payment['capture'] = true;
        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'vault_bu_namespace_card_metadata_variant')
                    {
                        return 'on';
                    }
                    return 'off';
                }));

        $response = $this->doS2SPrivateAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }

    public function testPaymentS2SDisable()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $order = $this->fixtures->create('order', ['amount' => 50000]);

        $payment['capture'] = true;
        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->addFeatures(['s2s_disable_cards']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment, null);
    }

    public function testPaymentS2SOnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->config['app.throw_exception_in_testing'] = false;

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['cvv'] = '1';

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $error = $response['error'];
        $this->assertEquals($error['field'], 'cvv');
        $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($error['description'], 'The cvv must be between 3 and 4 digits.');
    }

    /**
     * Tests S2S on partner auth with application feature(S2S)
     */
    public function testPaymentS2SOnPartnerAuth()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $response['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'authorized');
    }

    /**
     * Tests S2S on partner auth with token_interoperability feature(S2S)
     */
    public function testPaymentS2SOnPartnerAuthWithTokenInteroperability()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->fixtures->feature->create([
            'entity_type' => 'application',
            'entity_id'   => $client->getApplicationId(),
            'name'        => 'token_interoperability']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
        $this->fixtures->merchant->addFeatures('token_interoperability');

        $this->createMerchantApplication('10000000000000', 'fully_managed', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $this->mockCardVaultWithCryptogram(null,true);

        $payment = $this->getDefaultPaymentArray();


        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   =>  '1000ggcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );
        $payment['token'] = 'token_100022custcard';
        $payment['customer_id'] = 'cust_1000ggcustomer';
        unset($payment['card']);
        $payment['card']['cvv']='123';
        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $response['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'authorized');

        $this->assertEquals($pay['customer_id'], 'cust_1000ggcustomer');

        $this->assertEquals($pay['token_id'], 'token_100022custcard');

        $this->assertEquals($pay['merchant_id'], $sub->getId());
    }

    public function testSavedCardS2SPaymentOnPartnerAuthWithTokenInteroperability()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->fixtures->feature->create([
            'entity_type' => 'application',
            'entity_id'   => $client->getApplicationId(),
            'name'        => 'token_interoperability']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
        $this->fixtures->merchant->addFeatures('token_interoperability');

        $this->createMerchantApplication('10000000000000', 'fully_managed', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $this->mockCardVaultWithCryptogram(null,true);

        $payment = $this->getDefaultPaymentArray();


        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );
        $payment['customer_id'] = 'cust_1000ggcustomer';
        $payment['save'] = 1 ;
        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $response['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'authorized');

        $this->assertEquals($pay['customer_id'], 'cust_1000ggcustomer');

        $this->assertNotNull($pay['token_id']);

        $this->assertEquals($pay['merchant_id'], $sub->getId());

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($token['merchant_id'], '10000000000000');

        $this->assertEquals($token['customer_id'], '1000ggcustomer');


        // subsequent payments

        $subsequentPayment = $this->getDefaultPaymentArray();
        $subsequentPayment['customer_id'] = 'cust_1000ggcustomer';
        $subsequentPayment['token'] = $token['id'];
        unset($subsequentPayment['card']);
        $subsequentPayment['card']['cvv']='123';

        $subsequentPaymentResponse = $this->doS2SPartnerAuthPayment($subsequentPayment, $client, 'acc_' . $sub->getId());


        $this->assertArrayHasKey('razorpay_payment_id', $subsequentPaymentResponse);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $subsequentPaymentResponse['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'authorized');

        $this->assertEquals($pay['customer_id'], 'cust_1000ggcustomer');

        $this->assertEquals($pay['token_id'], $subsequentPayment['token']);

        $this->assertEquals($pay['merchant_id'], $sub->getId());

    }

    public function testPaymentS2SonAggregatorWithTokenInteroperability()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->fixtures->feature->create([
            'entity_type' => 'application',
            'entity_id'   => $client->getApplicationId(),
            'name'        => 'token_interoperability']);

        $sub = $this->fixtures->merchant->createWithBalance();
        $this->fixtures->merchant->addFeatures('s2s');
        $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
        $this->fixtures->merchant->addFeatures('token_interoperability');

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $this->mockCardVaultWithCryptogram(null,true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   =>  '1000ggcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );
        $payment['token'] = 'token_100022custcard';
        $payment['customer_id'] = 'cust_1000ggcustomer';
        unset($payment['card']);
        $payment['card']['cvv']='123';

        $this->makeRequestAndCatchException(
            function() use ($payment, $client, $sub)
            {
                $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());
            },
            \RZP\Exception\BadRequestException::class , 'The id provided does not exist');

    }

    public function testPaymentS2SonPartnerAuthWhereTokenInteroperabilityIsDisabled()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();
        $this->fixtures->merchant->addFeatures('s2s');
        $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
        $this->fixtures->merchant->addFeatures('s2s_json',$sub->getId());

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $this->mockCardVaultWithCryptogram(null,true);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   =>  '1000ggcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );
        $payment['token'] = 'token_100022custcard';
        $payment['customer_id'] = 'cust_1000ggcustomer';
        unset($payment['card']);
        $payment['card']['cvv']='123';

        $this->makeRequestAndCatchException(
            function() use ($payment, $client, $sub)
            {
                $this->doS2SJsonPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());
            },
            \RZP\Exception\BadRequestException::class , 'The id provided does not exist');

    }


    public function testRecurringPaymentWithTokenInteroperabilityandWithPartnerCustomerID(){

         $client = $this->createPartnerApplicationAndGetClientByEnv(
        'dev',
        [
            'type' => 'partner',
            'id'   => 'AwtIC8XQqM0Wet'
        ]);

            $this->mockCardVault();

            $this->fixtures->feature->create([
                'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

            $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

            $sub = $this->fixtures->merchant->createWithBalance();
            $this->fixtures->merchant->addFeatures('s2s');
            $this->fixtures->merchant->addFeatures('token_interoperability');
            $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
            $this->fixtures->merchant->addFeatures('s2s_json',$sub->getId());
            $this->fixtures->merchant->addFeatures('charge_at_will',$sub->getId());
            $this->createMerchantApplication('10000000000000', 'fully_managed', $client->getApplicationId());

            $this->fixtures->create(
                'merchant_access_map',
                [
                    'entity_id'   => $client->getApplicationId(),
                    'merchant_id' => $sub->getId(),
                ]
            );

            $this->mockCardVaultWithCryptogram(null,true);

            $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);
            $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
            );

           $payment = $this->getDefaultRecurringPaymentArray();

            $payment['save'] = true;
            $payment['recurring'] = 'preferred';
            $payment['customer_id'] = 'cust_1000ggcustomer';

            $this->makeRequestAndCatchException(
            function() use ($payment, $client, $sub)
            {
                $this->doS2SJsonPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());
            },
            \RZP\Exception\BadRequestException::class , 'The id provided does not exist');

    }

    public function testRecurringPaymentWithoutTokenInteroperabilityAndSubmerchantKaCustomer(){

        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $sub = $this->fixtures->merchant->createWithBalance();
        $this->fixtures->merchant->addFeatures('s2s');
        $this->fixtures->merchant->addFeatures('s2s',$sub->getId());
        $this->fixtures->merchant->addFeatures('s2s_json',$sub->getId());
        $this->fixtures->merchant->addFeatures('charge_at_will',$sub->getId());
        $this->createMerchantApplication('10000000000000', 'fully_managed', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $this->mockCardVaultWithCryptogram(null,true);

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);
        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   =>  $sub->getId()
            ]
        );

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = true;
        $payment['recurring'] = 'preferred';
        $payment['customer_id'] = 'cust_1000ggcustomer';

        $responseContent = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);

        $this->assertEquals('authorized', $paymentEntity['status']);

        $tokenEntity = $this->getEntityById('token', $paymentEntity['token_id'],true);

        $this->assertEquals($sub->getId(), $tokenEntity['merchant_id']);
    }

    public function testNotEnrolledCardPaymentS2SOnPrivateAuth()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testPaymentWithEmptyAcquirerData()
    {
        $paymentData = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($paymentData);

        $request = [
            'method'    => 'GET',
            'url'       => '/payments/' . $payment['razorpay_payment_id']
        ];

        $this->ba->privateAuth();

        // Get raw response
        $response = $this->sendRequest($request)->getContent();

        $this->assertMatchesRegularExpression('/' . preg_quote('"acquirer_data":{"auth_code":"') . '[0-9]{6}' . preg_quote('"}') . '/' , $response);
    }

    public function testPaymentWithAcquirerData()
    {
        $paymentData = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthPayment($paymentData);

        $payment = $this->getLastEntity('payment');

        $this->assertArrayHasKey('acquirer_data', $payment);

        $this->assertArrayHasKey('bank_transaction_id', $payment['acquirer_data']);
    }

    public function testPaymentWithGatewayProcurer()
    {
        $this->fixtures->merchant->addFeatures(['expose_gateway_provider']);

        $this->sharedTerminal->forceDelete();
        $sharpTerminal = $this->fixtures->create('terminal:shared_sharp_terminal', ['procurer' => 'sharp']);

        $paymentData = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthPayment($paymentData);

        $payment = $this->getLastEntity('payment');

        $this->assertArrayHasKey('gateway_provider', $payment);
        $this->assertEquals('sharp', $payment['gateway_provider']);
    }

    public function testPaymentWithSettledByNotEnable()
    {
        $this->sharedTerminal->forceDelete();
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $paymentData = $this->getDefaultNetbankingPaymentArray();
        $response = $this->doAuthPayment($paymentData);

        $payment = $this->fetchPayment($response['razorpay_payment_id']);

        $this->assertArrayNotHasKey('settled_by', $payment);
    }

    public function testPaymentWithSettledByRazorpay()
    {
        $this->fixtures->merchant->addFeatures(['expose_settled_by']);

        $this->sharedTerminal->forceDelete();
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $paymentData = $this->getDefaultNetbankingPaymentArray();
        $response = $this->doAuthPayment($paymentData);

        $payment = $this->fetchPayment($response['razorpay_payment_id']);

        $this->assertArrayHasKey('settled_by', $payment);
        $this->assertEquals('Razorpay', $payment['settled_by']);
    }

    public function testPaymentWithSettledByWithDS()
    {
        $this->fixtures->merchant->addFeatures(['expose_settled_by']);

        $this->sharedTerminal->forceDelete();

        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $response = $this->doAuthPayment($payment);

        $payment = $this->fetchPayment($response['razorpay_payment_id']);

        $this->assertArrayHasKey('settled_by', $payment);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testPreferredRecurringPaymentInputValidation()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $payment['recurring'] = 'xyz';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->getFormViaCreateRoute($payment);
            });
    }

    public function testPreferredRecurringPaymentInputValidationInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');
        $payment['recurring'] = true;

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testPreferredRecurringPaymentRecurringInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['recurring'] = 'preferred';

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['razorpay_payment_id'], $paymentEntity['id']);
        $this->assertEquals(false, $paymentEntity['recurring']);
    }

    public function testPreferredRecurringPaymentInvalidMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        unset($payment['email'], $payment['contact'], $payment['notes']);

        $response = $this->getFormViaCreateRoute($payment);
        $content = $response['content'];
        $content['contact'] = '+919999999998';
        $content['email'] = 'test@razorpay.com';

        $payment = $this->doAuthPayment($content, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['razorpay_payment_id'], $paymentEntity['id']);
        $this->assertEquals(false, $paymentEntity['recurring']);
    }

    public function testPreferredRecurringPaymentCard()
    {
        $this->mockCardVault();

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = true;
        $payment['recurring'] = 'preferred';

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $paymentEntity['recurring']);
    }

    public function testDirectSettlementPayment()
    {
        $this->fixtures->merchant->addFeatures(['expose_settled_by']);

        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testDirectSettlementPaymentCustomerFeeBearer()
    {
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Admin\Org\Entity::HDFC_ORG_ID
            ]
        );

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->getFeesForPayment($payment)['input'];
        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('axis_migs', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testDirectSettlementAxisMigsPayment()
    {
        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('axis_migs', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testDirectSettlementCybersourcePayment()
    {
        $this->fixtures->create('terminal:direct_settlement_cybersource_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('cybersource', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('hdfc', $payment['settled_by']);
    }

    public function testPaymentSettledBy()
    {
        $this->fixtures->merchant->addFeatures(['expose_settled_by']);

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('Razorpay', $payment['settled_by']);
    }

    public function testPaymentS2SAxisEmandate()
    {
        $this->ba->privateAuth();

        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB');

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766',
            'account_type'      => 'savings',
        ];

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['id'], $response['razorpay_payment_id']);

        $this->assertTrue($this->redirectToAuthorize);

        $payment['token'] = $paymentEntity['token_id'];
        $payment['amount'] = 3000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //

        $response = $this->doS2SRecurringPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('netbanking_axis', $paymentEntity['gateway']);
    }

    public function testPaymentS2SRedirectPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthMaestro()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5081597022059105';

        unset($payment['card']['cvv']);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthRazorx()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectPrivateAuthInvalidTrackId()
    {
        $request = [
            'request' => [
                'url' => '/payments/1234/redirect',
                'method' => 'get',
                'content' => [],
            ],
            'response' => []
        ];

        $this->ba->directAuth();

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $this->runRequestResponseFlow($request);
        },
        \RZP\Exception\BadRequestException::class,
        'Payment failed');
    }

    protected function setupEmandateAndGetPaymentRequest($bank = 'HDFC', $amount = 2000)
    {
        $this->mockCardVault();
        $this->fixtures->create('terminal:shared_emandate_icici_terminal');
        $this->fixtures->create('terminal:shared_emandate_axis_terminal');
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray($bank, $amount);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        return $payment;
    }

    public function testPaymentEditNotes()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPaymentFailedEditNotesMoreThan15Entries()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPaymentFailedEditNotesArrayValue()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['notes'] = [
            'key' => 'value',
        ];

        $payment = $this->doAuthAndGetPayment($payment);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'];

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPaymentFlowWithSyncCallToSmartRouting()
    {
        // creating a downtime
        $this->ba->adminAuth();

        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'begin'       => time(),
                'method'      => 'card',
                'issuer'      => 'HDFC',
                'source'      => 'other',
                'acquirer'    => 'ALL'
                  ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($request);

        // making a card payment here
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        // adding terminals to get multiple terminals for sorting
        $this->fixtures->create('terminal:multiple_category_terminals');

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['order_id_mandatory']);

        $this->doAuthPayment($payment);
    }

    public function testPaymentByUpiTpvForSpecificBanks()
    {
        // mocking the gateway call to check the bank account in input
        $this->mockGateway();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableTpv();

        $order = $this->fixtures->create('order', ['bank' => IFSC::KKBK, 'account_number' => '923729373']);

        $payment['amount'] = 1000000;

        $payment['bank'] = IFSC::KKBK;

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    public function testPublishEventToDopplerViaSNS()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableTpv();

        $order = $this->fixtures->create('order', ['bank' => IFSC::KKBK, 'account_number' => '923729373']);

        $payment['amount'] = 1000000;

        $payment['bank'] = IFSC::KKBK;

        $payment['order_id'] = $order->getPublicId();

        // mocking the gatewayFcall and return exception
        $this->mockGatewayException();

        // testing failure event here
        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            \RZP\Exception\BadRequestException::class);

        // mocking the gateway call and return success
        $this->mockGatewaySuccess();

        // testing success event here
        $this->doAuthPayment($payment);
    }

    public function testRearchPaymentCreateAjax()
    {
        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                if ($feature === 'card_payments_via_pg_router_v2')
                                {
                                    return 'on';
                                }
                                return 'off';
                            }));

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $payment = $this->getDefaultPaymentArray();

        // $payment['order_id'] = 'order_'.$order->getId();

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateAjaxMalaysia()
    {
        $this->fixtures->iin->edit('401200',[
            'country' => 'MY',
            'issuer'  => 'SBIN',
            'network' => 'Union Pay',
        ]);

        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $payment = $this->getDefaultPaymentArray();
        $payment['currency'] = 'MYR';

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateBoostWalletMalaysia()
    {
        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $this->wallet = Payment\Processor\Wallet::BOOST;
        $this->fixtures->merchant->enableAdditionalWallets([$this->wallet]);
        $this->terminal = $this->fixtures->create('terminal:shared_eghl_terminal');
        $payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $this->payment['currency'] = 'MYR';

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateGrabPayWalletMalaysia()
    {
        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $this->wallet = Payment\Processor\Wallet::GRABPAY;
        $this->fixtures->merchant->enableAdditionalWallets([$this->wallet]);
        $this->terminal = $this->fixtures->create('terminal:shared_eghl_terminal');
        $payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $this->payment['currency'] = 'MYR';

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateTouchNGgoWalletMalaysia()
    {
        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $this->wallet = Payment\Processor\Wallet::TOUCHNGO;
        $this->fixtures->merchant->enableAdditionalWallets([$this->wallet]);
        $this->terminal = $this->fixtures->create('terminal:shared_eghl_terminal');
        $payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $this->payment['currency'] = 'MYR';

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateMCashWalletMalaysia()
    {
        $this->fixtures->merchant->edit('10000000000000',[
            Merchant::COUNTRY_CODE => 'MY'
        ]);

        $this->wallet = Payment\Processor\Wallet::MCASH;
        $this->fixtures->merchant->enableAdditionalWallets([$this->wallet]);
        $this->terminal = $this->fixtures->create('terminal:shared_eghl_terminal');
        $payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $this->payment['currency'] = 'MYR';

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

    public function testRearchPaymentCreateAjaxInvoiceFail()
    {
        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $order = $this->fixtures->order->create(['amount' => 50000]);

        $this->fixtures->create('invoice',
                                           [
                                               'id'         => '1000005invoice',
                                               'order_id'   => $order->getId(),
                                               'expire_by'  => null,
                                               'status'     => 'issued',
                                               'amount'     => 50000,
                                           ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = 'order_'.$order->getId();

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

//         $response = $this->makeRequestParent($request);

//         $this->doAuthAndCapturePayment($payment);

//         $payment = $this->getDbLastEntityToArray('payment');

//         $this->assertEquals($payment['cps_route'], 0);

//         $invoice = $this->getDbLastEntityToArray('invoice');

//         $this->assertEquals($invoice['status'], 'paid');
    }

    public function testRearchPaymentCreateJson()
    {
        $this->markTestSkipped();
        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'headless_s2s_card_payments_via_pg_router_v2')
                    {
                        return 'on';
                    }
                    return 'off';
                }));

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = 'order_'.$order->getId();

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $paymentData = [
            'body' => [
                "data" => [
                    "pg_router" => true,
                    "payment" => [
                        'id' => 'JCWpeYcasrmYFb',
                        'merchant_id' => '10000000000000',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'base_amount' => 50000,
                        'method' => 'card',
                        'status' => 'captured',
                        'two_factor_auth' => 'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' => FALSE,
                        'amount_authorized' => 50000,
                        'amount_refunded' => 0,
                        'base_amount_refunded' => 0,
                        'amount_transferred' => 0,
                        'amount_paidout' => 0,
                        'refund_status' => NULL,
                        'description' => 'description',
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' => FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => [
                            'merchant_order_id' => 'id',
                        ],
                        'authorized_at' => 1614253879,
                        'auto_captured' => FALSE,
                        'captured_at' => 1614253880,
                        'gateway' => 'hdfc',
                        'terminal_id' => '1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' => 0,
                        'signed' => FALSE,
                        'verified' => NULL,
                        'gateway_captured' => TRUE,
                        'verify_bucket' => 0,
                        'verify_at' => 1614253880,
                        'callback_url' => NULL,
                        'fee' => 1000,
                        'mdr' => 1000,
                        'tax' => 0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' => FALSE,
                        'save' => FALSE,
                        'late_authorized' => FALSE,
                        'convert_currency' => NULL,
                        'disputed' => FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' => 'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' => 1614253879,
                        'updated_at' => 1614253880,
                        'captured' => TRUE,
                        'reference2' => '12343123',
                        'entity' => 'payment',
                        'fee_bearer' => 'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' => FALSE,
                        'gateway_amount' => 50000,
                        'gateway_currency' => 'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' => FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout) use ($paymentData)
            {
                return $paymentData;
            });

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($paymentData)
            {
                return $paymentData;
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/json',
            'method'  => 'post'
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'otp_auth_default']);

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], true);

        //otp resend
        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/pay_JCWpeYcasrmYFb/otp/resend',
            'content' => []
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], true);

        //otp submit
        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/pay_JCWpeYcasrmYFb/otp/submit',
            'content' => [
                'otp' => 123456
            ]
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], true);
    }

    protected function mockGatewayException()
    {
        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
                ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
                Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway, $action, $input, $mode)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD);
            });

        $this->app->instance('gateway', $gateway);
    }

    protected function mockGatewaySuccess()
    {
        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
                Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway,$action,$input,$mode)
            {
                return null;
            });

        $this->app->instance('gateway', $gateway);
    }

    protected function mockGateway()
    {
        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
            Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway,$action,$input,$mode)
            {
                $length = strlen($input['order']['account_number']);
                $this->assertEquals(10, $length);
            });

        $this->app->instance('gateway', $gateway);
    }

    public function testPaymentFailOnDinersAndDisableMerchant()
    {
        $this->markTestSkipped();

        $this->changeEnvToNonTest();

        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        // enabling the diners cards
        $this->fixtures->merchant->enableCardNetworks('10000000000000',['dicl']);

        // disabling the terminal as we want to test for "No terminal found"
        $this->fixtures->on('live')->terminal->edit('1n25f6uN5S1Z5a', ['enabled' =>  0]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '30569309025904';

        $this->doAuthPayment($payment);

        $entity = $this->getDbLastEntity('methods', 'live');

        // checking whether diners card got disabled or not for the merchant
        $this->assertEquals(false, $entity->isCardNetworkEnabled('DICL'));

    }

     public function testDinersPaymentOnPaytm()
    {
        $this->fixtures->create('terminal:shared_paytm_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        // enabling the diners cards
        $this->fixtures->merchant->enableCardNetworks('10000000000000',['dicl']);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '30569309025904';

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('paytm', $payment['gateway']);

        $this->assertEquals('1000PaytmTrmnl', $payment['terminal_id']);
    }

    public function testForRuPayPaymentOnHitachiTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'RuPay',
        ]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertTrue($paymentObj['gateway_captured'] );
    }

    public function testForRupayPaymentOnCybersourceTerminalModeDual()
    {
        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal',[
            'id'          => 'CmRSEGymhC3lae',
            'merchant_id' => '10000000000000',
            'mode'        => '3',
            'gateway'     => 'cybersource',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->edit('terminal', '1000SharpTrmnl', ['enabled' => false]);

        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'RuPay',
        ]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::CYBERSOURCE_HDFC_TERMINAL,
            [
                'mode' => 3,
            ]
        );

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['amount'] = 1000000;

        $this->doAuthPayment($payment);
        $payment = $this->getLastEntity('payment', true);

        $this->assertTrue($payment['gateway_captured']);
        $this->assertEquals('CmRSEGymhC3lae', $payment['terminal_id']);
        $this->assertEquals('cybersource', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);
    }

    public function testForMasterCardPaymentOnHitachiTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
        ]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::HITACHI_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertTrue($paymentObj['gateway_captured'] );
    }

    public function testPaymentOnNetbankingEbsTerminalModePurchase()
    {
        $this->mockCardVault();
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal',['mode'=>2]);
        $this->fixtures->terminal->edit(
            \RZP\Models\Terminal\Shared::EBS_RAZORPAY_TERMINAL,
            [
                'mode' => 2,
            ]
        );
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = 1000000;
        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $paymentObj = $this->getLastEntity('payment', true);
        $this->assertTrue($paymentObj['gateway_captured'] );
    }

    public function testCreatePaymentCardTypePrepaid()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testCreatePaymentCardTypePrepaidWithPrepaidRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'      => 'card',
            'merchant_id' => '10000000000000',
            'step'        => 'authorization',
            'gateway'     => 'axis_genius',
            'type'        => 'filter',
            'method_type' => 'prepaid',
            'filter_type' => 'select',
            'group'       => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('axis_genius', $paymentObj['gateway']);
    }

    public function testCreatePaymentCardTypePrepaidWithDefaultRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'      => 'card',
            'merchant_id' => '10000000000000',
            'step'        => 'authorization',
            'gateway'     => 'hdfc',
            'type'        => 'filter',
            'filter_type' => 'select',
            'group'       => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $paymentObj['gateway']);
    }


    public function testPaymentFailOnNetBankingAndDisableMerchant()
    {
        $this->changeEnvToNonTest();

        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        // disabling the terminal as we want to test for "No terminal found"
        $this->fixtures->on('live')->terminal->edit('1n25f6uN5S1Z5a', ['enabled' =>  0]);

        $res = $this->doAuthPayment($payment);

        $this->assertEquals($res['error']['internal_error_code'], ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
    }

    public function testCreatePaymentSubTypeWithDefaultRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'      => 'card',
            'merchant_id' => '10000000000000',
            'step'        => 'authorization',
            'gateway'     => 'hdfc',
            'type'        => 'filter',
            'filter_type' => 'select',
            'group'       => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'sub_type'=> 'business',
        ]);
        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('hdfc', $paymentObj['gateway']);
    }

    public function testCreatePaymentWithSubTypeRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method'            => 'card',
            'merchant_id'       => '10000000000000',
            'step'              => 'authorization',
            'method_subtype'    => 'business',
            'gateway'           => 'axis_genius',
            'type'              => 'filter',
            'filter_type'       => 'select',
            'group'             => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'sub_type'=> 'business',
        ]);
        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('axis_genius', $paymentObj['gateway']);
    }

    public function testCreatePaymentWithCategoryRule()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('rule_filter');

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:axis_genius_terminal');

        $ruleAttributes = [
            'method' => 'card',
            'merchant_id' => '10000000000000',
            'step' => 'authorization',
            'method_subtype' => 'business',
            'card_category' => 'Commercial Standard',
            'gateway' => 'axis_genius',
            'type' => 'filter',
            'filter_type' => 'select',
            'group' => 'A',
        ];

        $this->fixtures->create('gateway_rule', $ruleAttributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type' => 'credit',
            'sub_type' => 'business',
            'category' => 'Commercial Standard'
        ]);
        $this->doAuthPayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('axis_genius', $paymentObj['gateway']);
    }


    public function testRupayPaymentFallbackTo3ds()
    {
        $this->mockCardVault();

        $this->mockOtpElfForFailedRupayResponse();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '6075007194490126';

        $this->fixtures->edit('iin', 607500, ['flows' => ['headless_otp' => '1']]);

        $payment['preferred_auth'] = ['otp'];

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'hitachi',
            'card'                       => 1,
            'gateway_merchant_id'       => 'HDFC000012340818',
            'gateway_merchant_id2'      => '38R10000',
            'type'                      => [
                'non_recurring'  => '1',
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->create('terminal', $attributes);

        $res = $this->doAuthPayment($payment);

        $payment_id = explode('_', $res['razorpay_payment_id'])[1];

        $paySecureEntities = $this->getEntities('paysecure', ['payment_id' => $payment_id], true);

        $this->assertEquals(2, count($paySecureEntities['items']));
    }

    protected function mockOtpElfForFailedRupayResponse()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                    'data' => [

                    ]
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);
    }

    public function testRupayPaymentFallbackTo3dsForCardBlockError()
    {


        $this->mockCardVault();

        $this->mockOtpElfForBlockCardResponse();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '6075007194490126';

        $this->fixtures->edit('iin', 607500, ['flows' => ['headless_otp' => '1']]);

        $payment['preferred_auth'] = ['otp'];

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'hitachi',
            'card'                       => 1,
            'gateway_merchant_id'       => 'HDFC000012340818',
            'gateway_merchant_id2'      => '38R10000',
            'type'                      => [
                'non_recurring'  => '1',
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->create('terminal', $attributes);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            \RZP\Exception\GatewayErrorException::class);
    }

    public function testOtpPaymentCardBlockError()
    {


        $this->mockCardVault();

        $this->mockOtpElfForBlockCardResponse();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '6075007194490126';

        $this->fixtures->edit('iin', 607500, ['flows' => ['headless_otp' => '1']]);

        $payment['auth_type'] = 'otp';

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'hitachi',
            'card'                       => 1,
            'gateway_merchant_id'       => 'HDFC000012340818',
            'gateway_merchant_id2'      => '38R10000',
            'type'                      => [
                'non_recurring'  => '1',
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->create('terminal', $attributes);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            \RZP\Exception\GatewayErrorException::class,
            "Payment processing failed because cardholder's card was blocked\n".
            "Gateway Error Code: \n".
            "Gateway Error Desc: ");
    }

    protected function mockOtpElfForBlockCardResponse()
    {
        $otpelf = Mockery::mock('RZP\Services\Mock\OtpElf')->makePartial();

        $this->app->instance('card.otpelf', $otpelf);

        $otpelf->shouldReceive('otpSend')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $input)
            {
                return [
                    'success' => false,
                    'error' => [
                        'reason' => 'CARD_BLOCKED'
                    ]
                ];
            });

        $this->app->instance('card.otpelf', $otpelf);
    }

    /*
     * /payments/create/json, card payment
     */
    public function testPaymentS2SRedirectJsonPrivateAuthCardPayment()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentS2SRedirectJsonPrivateAuthCardPaymentWithoutCvv()
    {
        $this->mockCardVault();

        $this->mockRazorxWith(
            "skip_cvv", 'skip');
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'skip_cvv']);

        unset($payment["card"]["cvv"]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    public function testPaymentCardMotoWithoutTokenForAmex()
    {
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'direct_debit']);

        $this->mockRazorxWith('use_detect_network_for_dummy_cvv', 'on');

        $payment = $this->getDefaultPaymentArray();

        unset($payment["card"]["cvv"]);

        // an amex card number
        $payment["card"]["number"] = CardEntity::DUMMY_AMEX_CARD;
        $payment['auth_type']      = 'skip';
        $payment['customer_id']    = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Only tokenized cards are allowed for amex moto payments');

        $this->makeRequestParent($request);
    }

    public function testPaymentCardMotoWithTokenForAmex()
    {
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'direct_debit']);

        $this->mockRazorxWith('use_detect_network_for_dummy_cvv', 'on');

        $payment = $this->getDefaultPaymentArray();

        unset($payment["card"]["cvv"]);

        // an amex card number
        $payment["card"]["number"] = CardEntity::DUMMY_AMEX_CARD;
        $payment['auth_type']      = 'skip';
        $payment['save']           = 1;
        $payment['customer_id']    = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        // first normal paymernt to create the token
        $response = $this->makeRequestParent($request);

        // Note: 4 digit amex cvv check also passes here.
        $content = $this->getJsonContentFromResponse($response);

        $redirectContent = $content['next'][0];

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $fetchedPayment = $this->getLastEntity('payment', true);

        // getting the token created for card
        $tokenId = $fetchedPayment['token_id'];

        // moto payment, unsetting card and setting the token generated in last step
        unset($payment["card"]["number"]);
        unset($payment["save"]);

        $payment['token'] = $tokenId;

        $this->fixtures->create('terminal:shared_amex_terminal');

        // Note: tokenized cards will not throw a validation error
        $rr = $this->doAuthPayment($payment);

        $this->assertNotNull($rr["razorpay_payment_id"]);
    }

    public function testPaymentCardMotoWithToken()
    {
        $this->mockRazorxWith(
            "skip_cvv", 'skip');

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'skip_cvv', 'network_tokenization_live', 'direct_debit']);

        unset($payment["card"]["cvv"]);

        $payment['save'] = '1';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        // first normal paymernt to create the token
        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $fetchedPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($fetchedPayment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $fetchedPayment['status']);

        $this->assertTrue($this->redirectToAuthorize);

        // getting the token created for card
        $tokenId = $fetchedPayment['token_id'];

        // moto payment, unsetting card and setting the token generated in last step
        unset($payment["card"]["number"]);
        unset($payment["save"]);

        $payment['auth_type'] = 'skip';

        $payment['token'] = $tokenId;

        $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $rr = $this->doAuthPayment($payment);

        $this->assertNotNull($rr["razorpay_payment_id"]);
    }
    private function mockRazorxWith(string $featureUnderTest, string $value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->will(
            $this->returnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            ));
    }

    public function testPaymentS2SRedirectCardAuthenticatedPayment()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $paymentId = $content['razorpay_payment_id'];

        $this->repo = (new Payment\Repository());

        $id = Payment\Entity::stripDefaultSign($paymentId);

        $payment = Payment\Entity::findOrFail($id);

        $payment->setStatus('authenticated');

        $payment->setAuthenticatedTimestamp();

        $this->repo->saveOrFail($payment);

        $request = [
            'method'  => 'GET',
            'url'     => $this->getPaymentRedirectToAuthorizrUrl($id),
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetRawContent($request);

        $this->repo->reload($payment);

        $this->assertEquals($payment->getStatus(), 'authenticated');
    }

    public function testPaymentS2SJsonPrivateAuthUPIIntent()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->ba->privateAuth();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
        }, 'upi_icici');

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $this->assertEquals('intent', $content['next'][0]['action']);

        $this->assertEquals('poll', $content['next'][1]['action']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNotNull($upiMetadata);

        $this->assertArraySubset([
            UpiMetadata\Entity::TYPE       => 'default',
            UpiMetadata\Entity::FLOW       => 'intent',
        ], $upiMetadata->toArray());

        $this->assertSame($payment['id'],'pay_'.$upiMetadata->getPaymentId());
    }

    public function testPaymentS2SJsonPrivateAuthUPIVpa()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->ba->privateAuth();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
        }, 'upi_icici');

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $this->assertEquals('poll', $content['next'][0]['action']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

    }

    /*
     * /payments/create/json, netbanking payment
     */
    public function testPaymentS2SRedirectJsonPrivateAuthNetbankingPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['method'] = 'netbanking';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    /*
     * /payments/create/json, recurring payment
     */
    public function testPaymentS2SRedirectJsonPrivateAuthRecurringPayment()
    {
        $this->mockCardVault();
        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = true;
        $payment['recurring'] = 'preferred';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment,
            'convertContentToString' => false,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);
    }

    /*
     * /payments/create/json, emandate payment
     */
    public function testPaymentS2SRedirectJsonPrivateAuthEmandatePayment()
    {
        $this->ba->privateAuth();

        $payment = $this->setupEmandateAndGetPaymentRequest('UTIB');

        $payment['bank_account'] = [
            'account_number'    => '123123123',
            'name'              => 'test name',
            'ifsc'              => 'UTIB0002766',
            'account_type'      => 'savings',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);


        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertTrue($this->redirectToAuthorize);
    }

    // tests for merchant that have/do not have block_debit_2k feature enabled

    public function testBlockDebit2kEnabledMerchant()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures([Feature\Constants::BLOCK_DEBIT_2K]);

        $this->fixtures->iin->create([
            'iin'     => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'debit'
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        //test  payment with amount less than 2k
        $payment['amount'] = 100000;

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        //test  payment with amount more than 2k
        $payment['amount'] = 300000;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Amount exceeds maximum amount allowed');

        $this->doAuthPayment($payment);
    }

    public function testBlockDebit2kDisabledMerchant()
    {
        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'debit'
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        foreach ([100000, 300000] as $amount)
        {
            $payment['amount'] = $amount;

            $content = $this->doAuthPayment($payment);

            $this->assertArrayHasKey('razorpay_payment_id', $content);
        }
    }


    // begin tests for fee_bearer attribute of pricing plans and merchant

    public function testPaymentCreateMerchantPlatformBearerPricingPlatformBearer()
    {
        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(
            FeeBearer::PLATFORM,
            FeeBearer::PLATFORM);

        $this->doAuthAndCapturePayment($paymentArray);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(FeeBearer::PLATFORM, $payment['fee_bearer']);

        $this->assertEquals($paymentArray['amount'], $payment['amount']);
    }

    public function testPaymentCreateMerchantDynamicBearerPricingPlatformBearer()
    {
        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(
            FeeBearer::DYNAMIC,
            FeeBearer::PLATFORM);

        $this->doAuthAndCapturePayment($paymentArray);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(FeeBearer::PLATFORM, $payment['fee_bearer']);

        $this->assertEquals($paymentArray['amount'], $payment['amount']);
    }

    public function testPaymentCreateMerchantCustomerBearerPricingCustomerBearer()
    {
        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(
            FeeBearer::CUSTOMER,
            FeeBearer::CUSTOMER);

        $paymentFromResponse = $this->doAuthAndGetPayment($paymentArray);

        $captureAmount = $paymentArray['amount'] - $paymentArray['fee'];

        $this->capturePayment($paymentFromResponse['id'], $captureAmount, 'INR', $paymentArray['amount']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(FeeBearer::CUSTOMER, $payment['fee_bearer']);

        $this->assertEquals($paymentArray['amount'], $payment['amount']);

        $this->assertEquals($paymentArray['fee'], $payment['fee']);
    }

    public function testPaymentCreateMerchantDynamicBearerPricingCustomerBearer()
    {
        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(
            FeeBearer::DYNAMIC,
            FeeBearer::CUSTOMER);

        $paymentFromResponse = $this->doAuthAndGetPayment($paymentArray);

        $captureAmount = $paymentArray['amount'] - $paymentArray['fee'];

        $this->capturePayment($paymentFromResponse['id'], $captureAmount, 'INR', $paymentArray['amount']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(FeeBearer::CUSTOMER, $payment['fee_bearer']);

        $this->assertEquals($paymentArray['amount'], $payment['amount']);

        $this->assertEquals($paymentArray['fee'], $payment['fee']);
    }

    public function testPaymentCreatePlatformBearerNonINRCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => '1']);

        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(FeeBearer::PLATFORM, FeeBearer::PLATFORM);

        $paymentArray['currency'] = 'USD';


        $this->doAuthAndGetPayment($paymentArray, ['currency' => 'USD']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(FeeBearer::PLATFORM, $payment['fee_bearer']);
    }

    public function testPaymentCreateCustomerBearerMerchantNonINRCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => '1']);

        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(FeeBearer::CUSTOMER, FeeBearer::CUSTOMER);

        $paymentArray['currency'] = 'USD';

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage('Currency is not supported');

        $this->doAuthAndGetPayment($paymentArray, ['currency' => 'USD']);

    }

    public function testPaymentCreateDynamicBearerMerchantNonINRCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => '1']);

        $paymentArray = $this->setUpAndGetPaymentArrayForFeeBearerPricingTest(FeeBearer::DYNAMIC, FeeBearer::CUSTOMER);

        $paymentArray['currency'] = 'USD';

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage('Currency is not supported');

        $this->doAuthAndGetPayment($paymentArray, ['currency' => 'USD']);

    }

    // end tests for fee_bearer attribute of pricing plans and merchant

    public function testOrderStatusForUpiPaymentWithFlatCashbackOffer()
    {
        $offer = $this->fixtures->create("offer",['type' => 'instant', 'payment_method' => 'upi', 'flat_cashback'=>'100', 'min_amount'=>'200']);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true, 'notes' => ['somekey' => 'some value', 'Pay_Mode' => 'UPI'], 'amount' => '500', 'payment_capture' => '1'
        ]);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = 500;

        $payment['order_id'] = 'order_'.$order->getId();

        $this->doAuthPayment($payment);

        $lastOrder =  $this->getLastEntity('order',true);

        $this->assertEquals('paid', $lastOrder['status']);
    }

    public function testUpiBlock()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiBlockPaymentArray();

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertSame('authorized', $lastPayment['status']);

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNotNull($upiMetadata);

        $this->assertArraySubset([
            UpiMetadata\Entity::TYPE => 'default',
            UpiMetadata\Entity::FLOW => 'collect',
        ], $upiMetadata->toArray());

        $this->assertNotNull($upiMetadata->getExpiryTime());
    }

    public function testInAppUpiBlock()
    {
        $methods = [
            'upi'           => 1,
            'addon_methods' => [
                'upi' => [
                    'in_app' => 1
                ]
            ]
        ];
        $this->fixtures->edit('methods', '10000000000000', $methods);

        $payment = $this->getDefaultUpiBlockIntentPaymentArray();
        $payment['upi']['mode'] = 'in_app';

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertSame('authorized', $lastPayment['status']);

        //Since lastEntity() does a fetch multiple via proxy auth, upi_metadata should be set in the response
        $this->assertArrayHasKey('upi_metadata', $lastPayment);

        $expectedUpiMetadataBlock = [
            'flow' => 'in_app',
        ];

        $this->assertArraySelectiveEquals($expectedUpiMetadataBlock, $lastPayment['upi_metadata']);
    }

    public function testUpiAmountLimit()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['amount'] = 20000000; // Rs 2Lac

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertNotNull($response['payment_id']);
    }

    public function testUpiBlockFail()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['upi']['flow'] = 'intent';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testUpiOtmPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $this->doAuthPaymentViaAjaxRoute($payment);

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNotNull($upiMetadata);

        $this->assertArraySubset([
            UpiMetadata\Entity::TYPE       => 'otm',
            UpiMetadata\Entity::FLOW       => 'collect',
            UpiMetadata\Entity::START_TIME => $payment['upi']['start_time'],
            UpiMetadata\Entity::END_TIME   => $payment['upi']['end_time']
        ], $upiMetadata->toArray());

        $this->assertNotNull($upiMetadata->getExpiryTime());
    }

    public function testUpiOtmPaymentFail()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment =  $this->getDefaultUpiOtmPayment();

        $payment['upi']['end_time'] = Carbon::now()->subDays(3)->getTimestamp();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');
        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNull($payment);
        $this->assertNull($upiMetadata);
    }

    public function testUpiOtmInvalidPsp()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment =  $this->getDefaultUpiOtmPayment();

        $payment['upi']['vpa'] = 'user@hdfcbank';

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        },
        Exception\BadRequestException::class,
        'Your UPI application does not support one time mandate.');

    }

    public function testUpiInvalidProvider()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['upi']['provider'] = 454584;

        $testData = $this->testData[__FUNCTION__];

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity('payment');
        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNotNull($payment);
        $this->assertSame('authorized', $payment->getStatus());

        $this->assertNull($upiMetadata);
    }

    public function testUpiOtmWithNoDates()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        unset($payment['upi']['start_time']);
        unset($payment['upi']['end_time']);

        $this->doAuthPayment($payment);

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            UpiMetadata\Entity::FLOW => 'collect',
            UpiMetadata\Entity::TYPE => 'otm'
        ], $upiMetadata->toArray());

        $this->assertNotNull($upiMetadata->getStartTime());
        $this->assertNotNull($upiMetadata->getEndTime());

        $diffInDays = $upiMetadata->getTimeRange() / (60 * 60 * 24);

        $this->assertSame(90, $diffInDays);
    }

    public function testUpiOtmWithPastDates()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['upi']['start_time'] = Carbon::now()->subDays(4)->getTimestamp();

        $payment['upi']['end_time'] = Carbon::now()->subDays(2)->getTimestamp();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');
        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertNull($payment);
        $this->assertNull($upiMetadata);
    }

    public function testPaymentMerchantActionWhenNotAuthorized()
    {
        $config = $this->fixtures->create('config', ['type' => 'late_auth']);

        $payment = $this->fixtures->create('payment');

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/'. $payment->getPublicId() .'/merchant/actions';

        $this->startTest();
    }

    public function testPaymentMerchantActionWhenAuthorized()
    {
        $config = $this->fixtures->create('config', ['type' => 'late_auth',
            'config'     => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 1600,
                    "automatic_expiry_period": 600,
                    "refund_speed": "normal"
                }
            }'
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentFromResponse = $this->doAuthPayment($paymentArray);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] =
            '/payment/'. $paymentFromResponse['razorpay_payment_id'] .'/merchant/actions';

        $this->startTest();
    }

    public function testCreateCardPaymentWithIssuerNotMatchingConfigIssuers()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "card", "issuers": ["SBIN"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateCardPaymentWithCardTypeNotMatchingConfigCardTypes()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "card", "issuers": ["HDFC"],"types": ["debit"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateCardPaymentWithNetworkNotMatchingConfigNetworks()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "card", "issuers": ["HDFC"],"types": ["credit"], "networks" : ["Maestro"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateCardPaymentWithIinNotMatchingConfigIins()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "card", "iins": ["4111111"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateUpiPaymentWithFlowNotMatchingConfigFlows()
    {
        $paymentArray = $this->getDefaultUpiPaymentArray();

        $paymentArray['amount'] = '1000000';

        $paymentArray['upi']['flow'] = 'intent';

        unset($paymentArray['vpa']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "upi", "flows": ["qr"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthPaymentViaAjaxRoute($paymentArray);
    }

    public function testCreateNetbankingPaymentWithBankNotMatchingConfigBanks()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "upi", "flows": ["qr"]},{"method": "netbanking", "banks": ["HDFC"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateWalletPaymentWithWalletNotMatchingConfigWallets()
    {
        $paymentArray = $this->getDefaultWalletPaymentArray('airtelmoney');

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "wallet", "wallets": ["freecharge"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateEmiPaymentWithIssuerNotMatchingConfigIssuers()
    {
        $paymentArray = $this->getDefaultEmiPaymentArray(false);

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "emi", "issuers": ["SBIN"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateEmiPaymentWithCardTypeNotMatchingConfigCardTypes()
    {
        $paymentArray = $this->getDefaultEmiPaymentArray(false);

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "emi", "issuers": ["HDFC"],"types": ["debit"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateEmiPaymentWithNetworkNotMatchingConfigNetworks()
    {
        $paymentArray = $this->getDefaultEmiPaymentArray(false);

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "emi", "issuers": ["HDFC"],"types": ["credit"], "networks" : ["Maestro"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateEmiPaymentWithIinNotMatchingConfigIins()
    {
        $paymentArray = $this->getDefaultEmiPaymentArray(false);

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "emi", "iins": ["4111111"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The following payment method is not supported for this transaction');

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateEmiPaymentWithCardTypeMatchingConfigCardTypes()
    {
        $paymentArray = $this->getDefaultEmiPaymentArray(false);

        $paymentArray['amount'] = '1000000';

        $paymentArray['emi_duration'] = 6;

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions'       =>'[{"method": "emi", "issuers": ["HDFC"],"types": ["credit"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testCreateCardPaymentWithMultipleConfigsForHybridCase()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['payment_config_enabled']);

        $config = $this->fixtures->create('config', [
            'config' => '{"sequence": ["block.gpay","card","block.hdfc"],"settings": {"methods": {"upi": false}}}',
            'restrictions' => '[{"method": "card", "issuers": ["SBI"],"types": ["debit"]},{"method": "card", "issuers": ["HDFC"],"types": ["credit"]}]'
        ]);

        $order = $this->fixtures->order->create(['checkout_config_id' => $config->getId()]);

        $paymentArray['order_id'] = $order->getPublicId();

        $this->doAuthAndCapturePayment($paymentArray);
    }

    public function testPaymentCaptureForNullRefundAt()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        $this->assertNotNull($payment->getRefundAt());

        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment->refresh();

        $this->assertNull($payment->getRefundAt());
        $this->assertSame('captured', $payment->getStatus());
    }

    public function testAutoRefundDisabledPaymentRefundAtNull()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['disable_auto_refunds']);

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        $this->assertNull($payment->getRefundAt());

        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment->refresh();

        $this->assertNull($payment->getRefundAt());
        $this->assertSame('captured', $payment->getStatus());
    }

    public function testAutoRefundDisabledPaymentMerchantMail()
    {
        Mail::fake();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['disable_auto_refunds']);

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        $this->assertNull($payment->getRefundAt());

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp;

        $payment = $this->fixtures->edit(
            'payment', $payment->getId(),
            ['authorized_at' => $createdAt, 'created_at' => $createdAt]);

        $this->fixtures->payment->edit($payment->getId(), ['status' => 'authorized']);

        $this->ba->cronAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        Mail::assertSent(AuthorizedPaymentsReminder::class, function ($mail)
        {
            $this->assertTrue($mail->viewData['autoRefundsDisabledForMerchant']);

            return true;
        });
    }

    public function testAutoRefundsPaymentMerchantMail()
    {
        Mail::fake();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        $this->assertNotNull($payment->getRefundAt());

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp;

        $payment = $this->fixtures->edit(
            'payment', $payment->getId(),
            ['authorized_at' => $createdAt, 'created_at' => $createdAt]);

        $this->fixtures->payment->edit($payment->getId(), ['status' => 'authorized']);

        $this->ba->cronAuth();

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        Mail::assertSent(AuthorizedPaymentsReminder::class, function ($mail)
        {
            $this->assertFalse($mail->viewData['autoRefundsDisabledForMerchant']);

            return true;
        });
    }

    public function testPaymentRefundForNullRefundAt()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        // As sharp, will be directly authorized.
        $this->assertSame('authorized', $payment->getStatus());
        $this->assertNotNull($payment->getRefundAt());

        $after = Carbon::now()->addDays(6);

        Carbon::setTestNow($after);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->refundOldAuthorizedPayments($flag);

        if ($flag === true)
        {
            $this->updatePaymentStatus($payment->getId(), [], true);
        }

        $payment = $this->getDbLastPayment();

        $this->assertSame('refunded', $payment->getStatus());
        $this->assertNull($payment->getRefundAt());
    }

    public function testOtmIntentPaymentFails()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        unset($payment['upi']['vpa']);

        $payment['upi']['flow'] = 'intent';

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        Exception\BadRequestValidationFailureException::class,
        'Intent flow is not supported for upi mandates.');
    }

    public function testCreateExistingCardS2SPayment()
    {
        $this->ba->privateAuth();

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['name']   = '';
        $paymentArray['card']['number'] = '555555555555558';
        $paymentArray['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $response = $this->doS2SPrivateAuthPayment($paymentArray);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true );

        $card = $this->getLastEntity('card', true );

        $this->assertEquals($card['sub_type'], 'consumer');

        $this->assertEmpty($card['name']);

        $this->assertEquals($card['issuer'], 'SBIN');

        $this->assertEquals($card['network'], 'MasterCard');

        $this->assertEquals($card['type'], 'credit');

        $this->assertEquals($payment['international'], true);

        $this->fixtures->edit('iin', '555555', [
            'country' => 'IN', 'sub_type' => 'business', 'issuer' => 'HDFC',
            'network' => 'Visa', 'type' => 'debit']);

        $paymentArray['card']['name']   = 'Test Card';

        $this->doS2SPrivateAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true );

        $card = $this->getLastEntity('card', true );

        $this->assertEquals($card['sub_type'], 'business');

        $this->assertEquals($payment['international'], false);

        $this->assertEquals($card['name'], '');

        $this->assertEquals($card['issuer'], 'HDFC');

        $this->assertEquals($card['network'], 'Visa');

        $this->assertEquals($card['type'], 'debit');
    }

    public function testCreateExistingCardPayment()
    {
        $this->ba->privateAuth();

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $response = $this->doAuthPayment($paymentArray);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $card = $this->getLastEntity('card', true );

        $this->assertEquals($card['issuer'], 'SBIN');

        $this->assertEquals($card['network'], 'MasterCard');

        $this->assertEquals($card['type'], 'credit');

        $this->fixtures->edit('iin', '555555', [
            'issuer' => 'HDFC', 'network' => 'Visa', 'type' => 'debit']);

        $this->doAuthPayment($paymentArray);

        $card = $this->getLastEntity('card', true );

        $this->assertEquals($card['issuer'], 'HDFC');

        $this->assertEquals($card['network'], 'Visa');

        $this->assertEquals($card['type'], 'debit');
    }

    public function testCreatePaymentAMEXExistingCardS2SPayment()
    {
        $this->markTestSkipped(
            'Not using existing card now, new card entity will be created.'
        );

        $this->ba->privateAuth();

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '555555555555558';
        $paymentArray['card']['cvv'] = '5467';
        $paymentArray['callback_url'] = $this->getLocalMerchantCallbackUrl();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->iin->create([
            'iin'     => '555555',
            'country' => 'US',
            'network' => 'American Express',
        ]);

        $response = $this->doS2SPrivateAuthPayment($paymentArray);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true );

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['international'], false);

        $this->doS2SPrivateAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true );

        //Assert that second payment is using the existing card from previous payment
        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertEquals($payment['international'], false);
    }

    public function testCreatePaymentChargeAccountS2S()
    {
        $this->fixtures->create('terminal:direct_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->enableInternational();

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '5567630000002004';

        $paymentArray['charge_account'] = 'hitachiDirectMerchantI';

        $this->makeRequestAndCatchException(function() use ($paymentArray)
        {
            $this->doS2SPrivateAuthPayment($paymentArray);
        },
        \RZP\Exception\BadRequestValidationFailureException::class,
        'charge account is/are not required and should not be sent');

        $this->fixtures->merchant->addFeatures(['charge_account']);

        $this->makeRequestAndCatchException(function() use ($paymentArray)
        {
            $this->doS2SPrivateAuthPayment($paymentArray);
        },
        \RZP\Exception\BadRequestException::class,
        'Invalid charge account');

        $paymentArray['charge_account'] = 'hitachiDirectMerchant';

        $merchantAttr = $this->createMerchant();

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertEquals($merchant->getId(), $merchant['id']);

        $this->fixtures->merchant->addFeatures(['s2s', 'charge_account'], '1X4hRFHFx4UiXt');

        $this->ba->privateAuth($merchantAttr['key_id'], $merchantAttr['secret']);

        $paymentArray['charge_account'] = 'hitachiDirectMerchantId';
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $paymentArray
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('100HitaDirTmnl', $payment['terminal_id']);

        $this->assertEquals('1X4hRFHFx4UiXt', $payment['merchant_id']);

        $terminal = $this->getDbEntity('terminal', ['id' => $payment['terminal_id']]);

        $this->assertEquals($terminal->getMerchantId(), '10000000000000');

        $this->fixtures->merchant->addFeatures(['transaction_on_hold'], '1X4hRFHFx4UiXt');

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $payment['id']. '/capture',
            'content' => array('amount' => $payment['amount'])
        );


        $this->ba->privateAuth($merchantAttr['key_id'], $merchantAttr['secret']);

        $response = $this->makeRequestAndGetContent($request);


        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($transaction['entity_id'], $payment['id']);

        $this->assertTrue($transaction['on_hold']);
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

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $request = [
            'method' => 'POST',
            'url' => '/keys',
            'content' => [
            ]
        ];

        $keyContent = $this->makeRequestAndGetContent($request);

        $key = $this->getDbEntity('key', ['merchant_id' => '1X4hRFHFx4UiXt']);

        $this->assertEquals($key->getMerchantId(), '1X4hRFHFx4UiXt');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('1X4hRFHFx4UiXt', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $content['key_id'] = $keyContent['id'];
        $content['secret'] = $keyContent['secret'];

        return $content;
    }

    public function testCreatePaymentWithAmountGreaterThanMaxAmountAndCurrencyUSD()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::CONVERT_CURRENCY => true,
            MERCHANT::MAX_PAYMENT_AMOUNT => 10000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1001';

        $payment['currency'] = 'USD';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthPayment($payment);
        });
    }


    public function testCreateInternationalPaymentWithAmountGreaterThanMaxAmount()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::CONVERT_CURRENCY => true,
            MERCHANT::MAX_INTERNATIONAL_PAYMENT_AMOUNT => 10000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012010000000007';

        $payment['amount'] = '10001';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testIntlPaymentWhenNotAllowedForPaymentGateway()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::PRODUCT_INTERNATIONAL => '0111000000'
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY');
    }

    public function testIntlPaymentWithOrderIDWhenNotAllowedForPaymentGateway()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::PRODUCT_INTERNATIONAL => '0111000000'
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        //product_type and product_id will be null here.Hence the payment belongs to payment_gateway
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['amount'] = 1000000;
            $this->payment['order_id'] = 'order_100000000order';
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY');
    }

    public function testIntlPaymentWithOrderIDWhenNotAllowedForPaymentLinks()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::PRODUCT_INTERNATIONAL => '1011000000'
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->create('order', ['id' => '100000000order' , 'product_type' => 'payment_link']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['amount'] = 1000000;
            $this->payment['order_id'] = 'order_100000000order';
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_LINKS');
    }

    public function testIntlPaymentWithOrderIDWhenNotAllowedForPaymentPages()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::PRODUCT_INTERNATIONAL => '1101000000'
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->create('order', ['id' => '100000000order' , 'product_type' => 'payment_page']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['amount'] = 1000000;
            $this->payment['order_id'] = 'order_100000000order';
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_PAGES');
    }

    public function testIntlPaymentWithOrderIDWhenNotAllowedForInvoices()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::INTERNATIONAL => true,
            MERCHANT::PRODUCT_INTERNATIONAL => '1110000000'
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->create('order', ['id' => '100000000order' , 'product_type' => 'invoice']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function ()
        {
            $this->payment['amount'] = 1000000;
            $this->payment['order_id'] = 'order_100000000order';
            $this->payment['card']['number'] = '4012010000000007';
            $this->doAuthPayment($this->payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['gateway'], null);
        $this->assertEquals($payment['terminal_id'], null);
        $this->assertEquals($payment['status'], 'failed');
        $this->assertEquals($payment['error_code'], 'BAD_REQUEST_ERROR');
        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_INVOICES');
    }

    public function testCreatePaymentWithForceTerminalIdWithFeatureEnabled()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['force_terminal_id'] = 'term_1000SharpTrmnl';

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id']);

        $paymentFromResponse = $this->doAuthPayment($paymentArray);

        // fetching payment in admin auth.
        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals($paymentFromResponse['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testCreatePaymentWithForceTerminalIdWithoutFeatureEnabled()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['force_terminal_id'] = 'term_10000000000000';

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage('The feature force_terminal_id is not enabled for the merchant');

        $paymentFromResponse = $this->doAuthPayment($paymentArray);
    }

    public function testPaymentCreateWithMetaInfo()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();


        $payment['meta']  = [
            'action_type'       => 'authenticate',
            'reference_id' => '5081597022059105',
        ];

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('authenticate', $paymentMeta['action_type']);
        $this->assertEquals('5081597022059105', $paymentMeta['reference_id']);

        $this->ba->expressAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/meta/reference',
            'content' => [
                 'action_type'       => 'authenticate',
                 'reference_id' => '5081597022059105',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($paymentMeta['id'], $response[0]['id']);
     }

    public function testCreateVisaSafeClickCardS2SPaymentMerchantFeature()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $visaSafeClickPaymentCreateRequestData = $this->testData['visaSafeClickPaymentCreateRequestData'];

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $visaSafeClickPaymentCreateRequestData['order_id'] = $order->getPublicId();

        $visaSafeClickPaymentCreateRequestData['amount']   = $order['amount'];

        $visaSafeClickPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function () use ($visaSafeClickPaymentCreateRequestData)
        {
            $this->doS2SPrivateAuthPayment($visaSafeClickPaymentCreateRequestData);
        });
    }

    public function testPaymentCreateWithMetaInfoWithPaymentId()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $actionType = 'capture';

        $payment['meta']  = [
            'action_type'  => $actionType,
            'reference_id' => '5081597022059105',
        ];

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($actionType, $paymentMeta['action_type']);
        $this->assertEquals('5081597022059105', $paymentMeta['reference_id']);

        $this->ba->expressAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/meta/' . $paymentMeta['payment_id'] . '/' . $actionType,
        ];

        $response = $this->makeRequestAndGetContent($request);


        $this->assertEquals($paymentMeta['id'], $response['id']);
    }

    public function testPaymentCreateForLAVBBankCard()
    {
        $this->fixtures->iin->create([
            'iin'       => '608399',
            'country'   => 'US',
            'network'   => 'MasterCard',
            'issuer'    => 'LAVB',
            'enabled'   => 0,
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '6083995565723838';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            \RZP\Exception\BadRequestException::class,
            'We are unable to complete this transaction due to the restrictions on Laxmi Vilas Bank\'s operations by RBI (Gazette notification (S.O. 4127(E)) dated 17th November 2020'
            );
    }

    public function testRewardsTermRouteWithPaymentId()
    {
        $callback = null;

        $reward = $this->fixtures->create('reward', ['terms' => 'Random terms']);

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $payment = $this->getDefaultPaymentArray();

        $payment['reward_ids'] = array('reward_' . $reward->getId());

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $rewardTermsRequest = [
            'method' => 'GET',
            'url' => '/reward/'. $reward->getId(). '/'. explode('_',$paymentEntity['id'])[1] . '/terms',
            'content' => []
        ];

        $rewardTermsResponse = $this->makeRequestAndGetRawContent($rewardTermsRequest, $callback);

        $this->assertResponse('http', $rewardTermsResponse);
    }

    public function testRewardsTermRouteWithWrongPaymentId()
    {
        $callback = null;

        $reward = $this->fixtures->create('reward', ['terms' => 'Random terms']);

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $wrongPaymentId = 'pay_Wsde213wsdfrtg';

        $rewardTermsRequest = [
            'method' => 'GET',
            'url' => '/reward/'. $reward->getId(). '/'. explode('_',$wrongPaymentId)[1] . '/terms',
            'content' => []
        ];

        $rewardTermsResponse = $this->makeRequestAndGetRawContent($rewardTermsRequest, $callback);

        $rewardTermsResponse->assertSee('ERROR: Invalid Payment Id or Reward Id', false);
    }

    public function testOrderNotesAppendedInPaymentNotes()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $order = $this->createOrder(['notes' => ['optimizer_identifier_1' => 'op1', 1234 => 'op2']]);

        $payment['amount'] = 50000;

        $payment['order_id'] = $order['id'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->doAuthPayment($payment);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['notes']['optimizer_identifier_1'],'op1');

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['notes']['optimizer_identifier_1'],'op1');
        $this->assertEquals($payment['notes'][1234],'op2');

    }

    public function testOrderNotesSkippedInPaymentNotes()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->addFeatures(['skip_notes_merging']);

        $order = $this->createOrder(['notes' => ['optimizer_identifier_1' => 'op1', 1234 => 'op2']]);

        $payment['amount'] = 50000;

        $payment['order_id'] = $order['id'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->doAuthPayment($payment);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['notes']['optimizer_identifier_1'],'op1');

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);

        //checking payment notes does not contain optimizer_identifier_1
        $this->assertNotContains('optimizer_identifier_1', $payment['notes']);
    }

    public function testCreatePaymentForCardWithFeeConfigPayeeCustomerFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10200';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10200, $transaction['amount']);

        $this->assertEquals(9900, $transaction['credit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009900, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentForCardWithFeeConfigPayeeBusinessFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(9800, $transaction['credit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009800, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForCardWithFeeConfigPayeeCustomerPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(9820, $transaction['credit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009820, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForCardWithFeeConfigPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10180';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10180, $transaction['amount']);

        $this->assertEquals(9880, $transaction['credit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009880, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeCustomerFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

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

        $payment['amount'] = '10236';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10236, $transaction['amount']);

        $this->assertEquals(9882, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009882, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeBusinessFlatValue()
    {
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

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10118, $transaction['amount']);

        $this->assertEquals(9764, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009764, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeCustomerPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 40}}}}']);

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

        $payment['amount'] = '10142';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10142, $transaction['amount']);

        $this->assertEquals(9788, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009788, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "percentage_value": 40}}}}']);

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

        $payment['amount'] = '10212';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10212, $transaction['amount']);

        $this->assertEquals(9858, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009858, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeCustomerFlatValueWithAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

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

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment['amount'] = '10236';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10236, $transaction['amount']);

        $this->assertEquals(10236, $transaction['credit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $this->assertEquals(true, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010236, $balance['balance']);

        $this->assertEquals(89764, $balance['credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }


    public function testCreatePaymentForNBWithFeeConfigPayeeBusinessFlatValueWithAmountCredits()
    {
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

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10118, $transaction['amount']);

        $this->assertEquals(10118, $transaction['credit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $this->assertEquals(true, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010118, $balance['balance']);

        $this->assertEquals(89882, $balance['credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeCustomerPercentageValueWithAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 40}}}}']);

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

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment['amount'] = '10142';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10142, $transaction['amount']);

        $this->assertEquals(10142, $transaction['credit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $this->assertEquals(true, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010142, $balance['balance']);

        $this->assertEquals(89858, $balance['credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForNBWithFeeConfigPayeeBusinessPercentageValueWithAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "percentage_value": 40}}}}']);

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

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment['amount'] = '10212';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10212, $transaction['amount']);

        $this->assertEquals(10212, $transaction['credit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $this->assertEquals(true, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010212, $balance['balance']);

        $this->assertEquals(89788, $balance['credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentForWalletWithFeeConfigPayeeCustomerFlatValueWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
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

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '10236';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10236, $transaction['amount']);

        $this->assertEquals(10236, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $this->assertEquals($transaction['fee_credits'], $transaction['fee']);

        $this->assertEquals(false, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010236, $balance['balance']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentForWalletWithFeeConfigPayeeBusinessFlatValueWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "business", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '10118';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10118, $transaction['amount']);

        $this->assertEquals(10118, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $this->assertEquals($transaction['fee_credits'], $transaction['fee']);

        $this->assertEquals(false, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010118, $balance['balance']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
}

    public function testCreatePaymentForWalletWithFeeConfigPayeeCustomerPercentageValueWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "customer", "percentage_value": 40}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '10142';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10142, $transaction['amount']);

        $this->assertEquals(10142, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $this->assertEquals($transaction['fee_credits'], $transaction['fee']);

        $this->assertEquals(false, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010142, $balance['balance']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentForWalletWithFeeConfigPayeeBusinessPercentageValueWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "business", "percentage_value": 40}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => '',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '10212';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10212, $transaction['amount']);

        $this->assertEquals(10212, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $this->assertEquals($transaction['fee_credits'], $transaction['fee']);

        $this->assertEquals(false, $transaction['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010212, $balance['balance']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithPrepaid()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(300, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(999700, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaid()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(300, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(999700, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaidWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'

        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithPrepaidAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(89880, $balance['credits']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaidWithAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(89900, $balance['credits']);
    }

    public function testCreatePaymentCFBWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithPrepaidAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'

        ];

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(89880, $balance['credits']);
    }

    public function testCreatePaymentCFBWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaidWithAmountCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(0, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(89900, $balance['credits']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithPrepaidFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);
    }

    public function testCreatePaymentWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaidWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];


        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0 , $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);
    }

    public function testCreatePaymentCFBWithDSForCardWithFeeConfigPayeeCustomerPercentageValueWithPrepaidFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10120, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);
    }

    public function testCreatePaymentCFBWithDSForCardWithFeeConfigPayeeBusinessFlatValueWithPostpaidWithFeeCredits()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
             'fee_bearer' => 'customer'
        ];

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->fixtures->iin->edit('401200',[
            'country' => 'IN',
            'issuer'  => 'SBIN',
            'network' => 'Visa',
        ]);

        $this->fixtures->create('terminal:direct_settlement_axis_migs_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '10100';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $paymentFromResponse = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10100, $transaction['amount']);

        $this->assertEquals(0, $transaction['credit']);

        $this->assertEquals(0, $transaction['debit']);

        $this->assertEquals(300, $transaction['fee']);

        $this->assertEquals(0, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(10000 - $transaction['fee_credits'], $balance['fee_credits']);
    }

    public function testCreatePaymentForCardWithFeeConfigPaidByNBAndPFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '10000';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10000, $transaction['amount']);

        $this->assertEquals(9646, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009646, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentForUPIWithFeeConfig()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"upi": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

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
            'org_id'    => '100000razorpay'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId(), "payment_capture" => true]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = '10236';

        $payment['fee'] = 0;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $paymentAuth = $this->doAuthPayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10236, $transaction['amount']);

        $this->assertEquals(9882, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1009882, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

    }

    public function testCreatePaymentForCardWithFeeConfigPaidByNBAndCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId(), 'payment_capture' => true]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '10354';

        $payment['fee'] = 354;

        $payment['order_id'] = $order->getPublicId();

        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');

        $paymentAuth = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(10354, $transaction['amount']);

        $this->assertEquals(10000, $transaction['credit']);

        $this->assertEquals(354, $transaction['fee']);

        $this->assertEquals(54, $transaction['tax']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1010000, $balance['balance']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(10000, $order['amount_paid']);

        $this->assertEquals(0, $order['amount_due']);

        $this->assertEquals('paid', $order['status']);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('processed', $refund['status']);
    }

    protected  function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();
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
            'cod_fee'           => 100000,
            'net_price'         => 1100000,
            'sub_total'         => 1100000,
            'shipping_fee'      => 10000,
            'customer_details'  => $app['encrypter']->encrypt($customer),
            'line_items_total'  => 1000000,
        ];
    }

    public function test1CCOrderPaymentsWithCustomerDeatils(){
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_'.$order->getId();
        $payment["amount"] = $order->getAmount();
        $testData['request']['content'] = $payment;

        $response = $this->makeRequestParent($testData['request']);

        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function test1CCOrderPaymentsWithoutCustomerDeatils()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value' => [
                    'line_items_total' => $order->getAmount(),
                    "cod_fee" => 0,
                    "shipping_fee" => 0,
                ],
                'type' => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_' . $order->getId();
        $payment["amount"] = $order->getAmount();
        $testData['request']['content'] = $payment;

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Something went wrong, please try again after sometime.');

        $response = $this->makeRequestParent($testData['request']);

        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function testUserConsentPageWithNewCard()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = '1';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['card']['number'], $card['number']);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithNewCardRecurring()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['recurring'] = '1';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.recurringTokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['card']['number'], $card['number']);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithSavedCardRecurring()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'card';
        $payment['recurring'] = '1';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.recurringTokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $token = $this->app['encrypter']->decrypt($responseContent['input']['token']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['token'], $token);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithEmiMethodForNewCard()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableEmi();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = '1';
        $payment['method'] = 'emi';
        $payment['emi_duration'] = '9';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['card']['number'], $card['number']);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithSavedCard()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $token = $this->app['encrypter']->decrypt($responseContent['input']['token']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['token'], $token);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithEmiMethodForSavedCard()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => '111');
        $payment['_']['library'] = 'razorpayjs';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'emi';
        $payment['emi_duration'] = '9';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $token = $this->app['encrypter']->decrypt($responseContent['input']['token']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['token'], $token);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageWithMerchantConsentDisabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DISABLE_COLLECT_CONSENT]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($response['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testUserConsentPageWithInvalidLibrary()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => 111);
        $payment['_']['library'] = 'checkoutjs';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($response['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testUserConsentPageWithNonCardPaymentMethod()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'upi';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('gateway.gatewayUpiForm');
    }

    public function testUserConsentPageWithoutCustomerId()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($response['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testUserConsentPageWithoutCvv()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN, Feature\Constants::NETWORK_TOKENIZATION_PAID]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        unset($payment['card']['cvv']);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['save'] = 1;

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The cvv field is required');

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);
    }

    public function testUserConsentPageWithoutCardNumber()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN, Feature\Constants::NETWORK_TOKENIZATION_PAID]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        unset($payment['card']['number']);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['save'] = 1;

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The number field is required.');

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);
    }

    public function testUserConsentPageForSavedCardWithConsentToSaveCardParam()
    {
        $this->fixtures->merchant->addFeatures(
            [
                Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN,
                Feature\Constants::NETWORK_TOKENIZATION_PAID,
            ]
        );

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv' => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['consent_to_save_card'] = 1;
        $payment['token'] = 'token_100000custcard';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getEntityById('token', '100000custcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testUserConsentPageForNewCardWithConsentToSaveCardParam()
    {
        $this->fixtures->merchant->addFeatures(
            [
                Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN,
                Feature\Constants::NETWORK_TOKENIZATION_PAID,
            ]
        );

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['consent_to_save_card'] = '1';
        $payment['save'] = '1';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getLastEntity('token',true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testUserConsentPageWithNewCardWithoutSaveParam()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getLastEntity('token',true);
        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertNull($token['acknowledged_at']);
        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($response['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testUserConsentPageForSavedCardWithConsentPreviouslyTaken()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->edit('token', '100000custcard', ['acknowledged_at' => Carbon::now()->timestamp]);

        $payment['_']['library'] = 'razorpayjs';
        $payment['card'] = array('cvv' => 111);
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['token'] = 'token_100000custcard';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($response['razorpay_payment_id'], $paymentEntity['id']);
    }

    public function testPaymentForNewCardWithEncryptedCardDetails()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::NETWORK_TOKENIZATION_PAID]);

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['save'] = 1;
        $payment['consent_to_save_card'] = 1;

        $payment['card'] = $this->app['encrypter']->encrypt($payment['card']);

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $tokenEntity = $this->getLastEntity('token', true);

        $this->assertNotNull($tokenEntity['acknowledged_at']);
    }

    public function testPaymentForSavedCardWithEncryptedCardDetails()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::NETWORK_TOKENIZATION_PAID]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv' => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['consent_to_save_card'] = 1;
        $payment['token'] = 'token_100000custcard';

        $payment['card'] = $this->app['encrypter']->encrypt($payment['card']);
        $payment['token'] = $this->app['encrypter']->encrypt($payment['token']);

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getEntityById('token', '100000custcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testPaymentWithRefusedUserConsentToSaveCard()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv' => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['consent_to_save_card'] = 0;
        $payment['token'] = 'token_100000custcard';

        $response = $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getEntityById('token', '100000custcard', true);

        $this->assertNull($token['acknowledged_at']);
    }

    public function testUserConsentPageForSavedCardWithToken()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv' => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['token'] = '10000cardtoken';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $token = $this->app['encrypter']->decrypt($responseContent['input']['token']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['token'], $token);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testUserConsentPageForCustomLibrary()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN]);

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = array('cvv'  => 111);
        $payment['_']['library'] = 'custom';
        $payment['token'] = 'token_100000custcard';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $payment
        ];

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('tokenisation.tokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();

        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);

        $token = $this->app['encrypter']->decrypt($responseContent['input']['token']);

        $this->assertEquals($payment['card']['cvv'], $card['cvv']);

        $this->assertEquals($payment['token'], $token);

        $expectedResponse = $this->testData[__FUNCTION__]['response'];

        $this->assertArraySelectiveEquals($expectedResponse, $responseContent);
    }

    public function testRearchPaymentEsSync()
    {
        $this->enablePgRouterConfig();

          $transaction = $this->fixtures->create('transaction', [
            'entity_id' =>'GfnS1Fj048VHo2',
            'type' =>'payment',
            'merchant_id' =>'10000000000000',
            'amount' =>50000,
            'fee' =>1000,
            'mdr' =>1000,
            'tax' =>0,
            'pricing_rule_id' => NULL,
            'debit' =>0,
            'credit' =>49000,
            'currency' =>'INR',
            'balance' =>2025400,
            'gateway_amount' => NULL,
            'gateway_fee' =>0,
            'gateway_service_tax' =>0,
            'api_fee' =>0,
            'gratis' =>FALSE,
            'fee_credits' =>0,
            'escrow_balance' =>0,
            'channel' =>'axis',
            'fee_bearer' =>'platform',
            'fee_model' =>'prepaid',
            'credit_type' =>'default',
            'on_hold' =>FALSE,
            'settled' =>FALSE,
            'settled_at' =>1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' =>'10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' =>TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' =>1614262078,
            'updated_at' =>1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id' =>'10000000000000',
                'name' =>'Harshil',
                'expiry_month' =>12,
                'expiry_year' =>2024,
                'iin' =>'401200',
                'last4' =>'3335',
                'length' =>'16',
                'network' =>'Visa',
                'type' =>'credit',
                'sub_type' =>'consumer',
                'category' =>'STANDARD',
                'issuer' =>'HDFC',
                'international' =>FALSE,
                'emi' =>TRUE,
                'vault' =>'rzpvault',
                'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
                'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
                'trivia' => NULL,
                'country' =>'IN',
                'global_card_id' => NULL,
                'created_at' =>1614256967,
                'updated_at' =>1614256967,
        ]);


        $payment = $this->getDefaultPaymentArray();

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                        'body' => [
                            "data" => [
                                "payment" => [
                                    'id' => 'GfnS1Fj048VHo2',
                                    'merchant_id' => '10000000000000',
                                    'amount' => 50000,
                                    'currency' => 'INR',
                                    'base_amount' => 50000,
                                    'method' => 'card',
                                    'status' => 'captured',
                                    'two_factor_auth' => 'not_applicable',
                                    'order_id' => NULL,
                                    'invoice_id' => NULL,
                                    'transfer_id' => NULL,
                                    'payment_link_id' => NULL,
                                    'receiver_id' => NULL,
                                    'receiver_type' => NULL,
                                    'international' => FALSE,
                                    'amount_authorized' => 50000,
                                    'amount_refunded' => 0,
                                    'base_amount_refunded' => 0,
                                    'amount_transferred' => 0,
                                    'amount_paidout' => 0,
                                    'refund_status' => NULL,
                                    'description' => 'description',
                                    'card_id' => 'GfnS1Fj048VHo2',
                                    'bank' => NULL,
                                    'wallet' => NULL,
                                    'vpa' => NULL,
                                    'on_hold' => FALSE,
                                    'on_hold_until' => NULL,
                                    'emi_plan_id' => NULL,
                                    'emi_subvention' => NULL,
                                    'error_code' => NULL,
                                    'internal_error_code' => NULL,
                                    'error_description' => NULL,
                                    'global_customer_id' => NULL,
                                    'app_token' => NULL,
                                    'global_token_id' => NULL,
                                    'email' => 'a@b.com',
                                    'contact' => '+919918899029',
                                    'notes' => [
                                        'merchant_order_id' => 'id',
                                    ],
                                    'transaction_id' => 'GfnS1Fj048VHo2',
                                    'authorized_at' => 1614253879,
                                    'auto_captured' => FALSE,
                                    'captured_at' => 1614253880,
                                    'gateway' => 'hdfc',
                                    'terminal_id' => '1n25f6uN5S1Z5a',
                                    'authentication_gateway' => NULL,
                                    'batch_id' => NULL,
                                    'reference1' => NULL,
                                    'reference2' => NULL,
                                    'cps_route' => 0,
                                    'signed' => FALSE,
                                    'verified' => NULL,
                                    'gateway_captured' => TRUE,
                                    'verify_bucket' => 0,
                                    'verify_at' => 1614253880,
                                    'callback_url' => NULL,
                                    'fee' => 1000,
                                    'mdr' => 1000,
                                    'tax' => 0,
                                    'otp_attempts' => NULL,
                                    'otp_count' => NULL,
                                    'recurring' => FALSE,
                                    'save' => FALSE,
                                    'late_authorized' => FALSE,
                                    'convert_currency' => NULL,
                                    'disputed' => FALSE,
                                    'recurring_type' => NULL,
                                    'auth_type' => NULL,
                                    'acknowledged_at' => NULL,
                                    'refund_at' => NULL,
                                    'reference13' => NULL,
                                    'settled_by' => 'Razorpay',
                                    'reference16' => NULL,
                                    'reference17' => NULL,
                                    'created_at' => 1614253879,
                                    'updated_at' => 1614253880,
                                    'captured' => TRUE,
                                    'reference2' => '12343123',
                                    'entity' => 'payment',
                                    'fee_bearer' => 'platform',
                                    'error_source' => NULL,
                                    'error_step' => NULL,
                                    'error_reason' => NULL,
                                    'dcc' => FALSE,
                                    'gateway_amount' => 50000,
                                    'gateway_currency' => 'INR',
                                    'forex_rate' => NULL,
                                    'dcc_offered' => NULL,
                                    'dcc_mark_up_percent' => NULL,
                                    'dcc_markup_amount' => NULL,
                                    'mcc' => FALSE,
                                    'forex_rate_received' => NULL,
                                    'forex_rate_applied' => NULL,
                                ]
                            ]
                        ]
                    ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];


        // Ref to InvoiceTest.testCreateInvoiceAndAssertEsSync() test on why
        // this is being asserted differently.

        $expectedNotes = [
            [
                'key'   => 'merchant_order_id',
                'value' => 'id',
            ],
        ];

        $esMock = $this->getMockBuilder(EsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['bulkUpdate'])
            ->getMock();

        $this->app->instance('es', $esMock);

        $esMock->expects($this->exactly(2))
            ->method('bulkUpdate')
            ->with(
                $this->callback(
                    function ($actual) use ($expectedNotes)
                    {
                        $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                        $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                        $this->assertNotEmpty($actual['body'][1]['id']);

                        return true;
                    })
            );

        $response = $this->makeRequestParent($request);

        EsSync::dispatch('test', 'create', 'payment', 'GfnS1Fj048VHo2', true);
    }

     public function testRearchPaymentEsSyncCron()
    {
        $this->enablePgRouterConfig();

          $transaction = $this->fixtures->create('transaction', [
            'entity_id' =>'GfnS1Fj048VHo2',
            'type' =>'payment',
            'merchant_id' =>'10000000000000',
            'amount' =>50000,
            'fee' =>1000,
            'mdr' =>1000,
            'tax' =>0,
            'pricing_rule_id' => NULL,
            'debit' =>0,
            'credit' =>49000,
            'currency' =>'INR',
            'balance' =>2025400,
            'gateway_amount' => NULL,
            'gateway_fee' =>0,
            'gateway_service_tax' =>0,
            'api_fee' =>0,
            'gratis' =>FALSE,
            'fee_credits' =>0,
            'escrow_balance' =>0,
            'channel' =>'axis',
            'fee_bearer' =>'platform',
            'fee_model' =>'prepaid',
            'credit_type' =>'default',
            'on_hold' =>FALSE,
            'settled' =>FALSE,
            'settled_at' =>1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' =>'10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' =>TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' =>1614262078,
            'updated_at' =>1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id' =>'10000000000000',
                'name' =>'Harshil',
                'expiry_month' =>12,
                'expiry_year' =>2024,
                'iin' =>'401200',
                'last4' =>'3335',
                'length' =>'16',
                'network' =>'Visa',
                'type' =>'credit',
                'sub_type' =>'consumer',
                'category' =>'STANDARD',
                'issuer' =>'HDFC',
                'international' =>FALSE,
                'emi' =>TRUE,
                'vault' =>'rzpvault',
                'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
                'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
                'trivia' => NULL,
                'country' =>'IN',
                'global_card_id' => NULL,
                'created_at' =>1614256967,
                'updated_at' =>1614256967,
        ]);


        $payment = $this->getDefaultPaymentArray();

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                        'body' => [
                            "data" => [
                                "payment" => [
                                    'id' => 'GfnS1Fj048VHo2',
                                    'merchant_id' => '10000000000000',
                                    'amount' => 50000,
                                    'currency' => 'INR',
                                    'base_amount' => 50000,
                                    'method' => 'card',
                                    'status' => 'captured',
                                    'two_factor_auth' => 'not_applicable',
                                    'order_id' => NULL,
                                    'invoice_id' => NULL,
                                    'transfer_id' => NULL,
                                    'payment_link_id' => NULL,
                                    'receiver_id' => NULL,
                                    'receiver_type' => NULL,
                                    'international' => FALSE,
                                    'amount_authorized' => 50000,
                                    'amount_refunded' => 0,
                                    'base_amount_refunded' => 0,
                                    'amount_transferred' => 0,
                                    'amount_paidout' => 0,
                                    'refund_status' => NULL,
                                    'description' => 'description',
                                    'card_id' => 'GfnS1Fj048VHo2',
                                    'bank' => NULL,
                                    'wallet' => NULL,
                                    'vpa' => NULL,
                                    'on_hold' => FALSE,
                                    'on_hold_until' => NULL,
                                    'emi_plan_id' => NULL,
                                    'emi_subvention' => NULL,
                                    'error_code' => NULL,
                                    'internal_error_code' => NULL,
                                    'error_description' => NULL,
                                    'global_customer_id' => NULL,
                                    'app_token' => NULL,
                                    'global_token_id' => NULL,
                                    'email' => 'a@b.com',
                                    'contact' => '+919918899029',
                                    'notes' => [
                                        'merchant_order_id' => 'id',
                                    ],
                                    'transaction_id' => 'GfnS1Fj048VHo2',
                                    'authorized_at' => 1614253879,
                                    'auto_captured' => FALSE,
                                    'captured_at' => 1614253880,
                                    'gateway' => 'hdfc',
                                    'terminal_id' => '1n25f6uN5S1Z5a',
                                    'authentication_gateway' => NULL,
                                    'batch_id' => NULL,
                                    'reference1' => NULL,
                                    'reference2' => NULL,
                                    'cps_route' => 0,
                                    'signed' => FALSE,
                                    'verified' => NULL,
                                    'gateway_captured' => TRUE,
                                    'verify_bucket' => 0,
                                    'verify_at' => 1614253880,
                                    'callback_url' => NULL,
                                    'fee' => 1000,
                                    'mdr' => 1000,
                                    'tax' => 0,
                                    'otp_attempts' => NULL,
                                    'otp_count' => NULL,
                                    'recurring' => FALSE,
                                    'save' => FALSE,
                                    'late_authorized' => FALSE,
                                    'convert_currency' => NULL,
                                    'disputed' => FALSE,
                                    'recurring_type' => NULL,
                                    'auth_type' => NULL,
                                    'acknowledged_at' => NULL,
                                    'refund_at' => NULL,
                                    'reference13' => NULL,
                                    'settled_by' => 'Razorpay',
                                    'reference16' => NULL,
                                    'reference17' => NULL,
                                    'created_at' => 1614253879,
                                    'updated_at' => 1614253880,
                                    'captured' => TRUE,
                                    'reference2' => '12343123',
                                    'entity' => 'payment',
                                    'fee_bearer' => 'platform',
                                    'error_source' => NULL,
                                    'error_step' => NULL,
                                    'error_reason' => NULL,
                                    'dcc' => FALSE,
                                    'gateway_amount' => 50000,
                                    'gateway_currency' => 'INR',
                                    'forex_rate' => NULL,
                                    'dcc_offered' => NULL,
                                    'dcc_mark_up_percent' => NULL,
                                    'dcc_markup_amount' => NULL,
                                    'mcc' => FALSE,
                                    'forex_rate_received' => NULL,
                                    'forex_rate_applied' => NULL,
                                ]
                            ]
                        ]
                    ];
            });



        $request = [
            'content' => ['backfill' => true],
            'url'     => '/payments_cards/payments/es_sync',
            'method'  => 'post'
        ];


        // Ref to InvoiceTest.testCreateInvoiceAndAssertEsSync() test on why
        // this is being asserted differently.

        $expectedNotes = [
            [
                'key'   => 'merchant_order_id',
                'value' => 'id',
            ],
        ];

        $esMock = $this->getMockBuilder(EsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['bulkUpdate'])
            ->getMock();

        $this->app->instance('es', $esMock);

        $esMock->expects($this->exactly(1))
            ->method('bulkUpdate')
            ->with(
                $this->callback(
                    function ($actual) use ($expectedNotes)
                    {
                        $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                        $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                        $this->assertNotEmpty($actual['body'][1]['id']);

                        return true;
                    })
            );

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('GET', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input)
            {
               return ['data' => [
                'GfnS1Fj048VHo2'
                ]];
            });

       $this->ba->cronAuth();

       $this->makeRequestParent($request);
    }

    public function testCreateEncryptedS2SPayment()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['name']   = '';
        $paymentArray['card']['encrypted_number'] = 'QIfoeA8AR7vkw0Rq9gs0btihYQ6wL9ONUNQ9cjaqAeI=';
        unset($paymentArray['card']['number']);


        $response = $this->doS2SPrivateAuthPayment($paymentArray);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testCreatePosPayments()
    {
        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'hdfc_ezetap',
            'gateway_merchant_id'      => '12344',
            'gateway_acquirer'         => 'hdfc',
            'card'                       => 1,
            'type'                      => [
                'pos' => '1',
                'direct_settlement_with_refund' => '1',
                'non_recurring'             => '1'
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::RULE_FILTER]);

        $this->fixtures->create('terminal', $attributes);

        $this->fixtures->pricing->createTestPlanForPosPayments();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $response = $this->startTest();

        $input = $this->testData[__FUNCTION__]['request']['content'];

        $first6 = implode(explode("-",substr($input['card']['number'],0,7)));

        $last4  = substr($input['card']['number'],-4);

        $cardEntity = $response['card_id'];

        $cardNumber = (new Repository())->findByPublicId($cardEntity)->toArray();

        $paymentEntity = (new Payment\Repository())->findByPublicId($response['id']);

        $terminalEntity = (new \RZP\Models\Terminal\Repository())->fetchForPayment($paymentEntity)->toArray();

        $this->assertEquals($first6,$cardNumber['iin']);

        $this->assertEquals($last4,$cardNumber['last4']);

        $this->assertEquals($terminalEntity['gateway'],'hdfc_ezetap');

        $this->assertEquals('authorized', $response['status']);

    }

    public function testCreatePosPaymentsBlockAutoRefund()
    {
        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'hdfc_ezetap',
            'gateway_merchant_id'      => '12344',
            'gateway_acquirer'         => 'hdfc',
            'card'                       => 1,
            'type'                      => [
                'pos' => '1',
                'direct_settlement_with_refund' => '1',
                'non_recurring'             => '1'
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::RULE_FILTER]);

        $this->fixtures->create('terminal', $attributes);

        $this->fixtures->pricing->createTestPlanForPosPayments();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $response = $this->startTest($testData);

        $paymentEntity = (new Payment\Repository())->findByPublicId($response['id']);

        $terminalEntity = (new \RZP\Models\Terminal\Repository())->fetchForPayment($paymentEntity)->toArray();

        $this->assertEquals($terminalEntity['gateway'],'hdfc_ezetap');

        $this->assertEquals('authorized', $response['status']);

        $this->assertNull($paymentEntity->getRefundAt());
    }

    public function mockGetNetBankingConfig() {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();


        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app->dcs_config_service->method('fetchConfiguration')->willReturn([NetbankingConfig\Constants::AUTO_REFUND_OFFSET => 1200]);
    }

    public function mockCreateNetBankingConfig() {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();


        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app->dcs_config_service->method('createConfiguration')->willReturn([NetbankingConfig\Constants::AUTO_REFUND_OFFSET => 1200]);
    }

    public function testCreateCorporateNetbankingBlockAutoRefund()
    {
        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                   => 'netbanking_canara',
            'gateway_merchant_id'       => 'merchant_id',
            'enabled'                   => 1,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::RULE_FILTER]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::NETBANKING_CORPORATE_DELAY_REFUND]);

        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->fixtures->create('terminal', $attributes);

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $this->mockGetNetBankingConfig();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastPayment();

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $minuteDiff = ($payment->getRefundAt() - $currentTime) / 60;

        $this->assertTrue($minuteDiff < 1201 and $minuteDiff > 1119);
    }

    public function testCreateNBConfig()
    {
        $this->ba->adminAuth();

        $this->mockCreateNetBankingConfig();

        $this->startTest();
    }

    public function testCreateNBConfigNegative()
    {
        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->hostName = 'testing.testing.com';

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $this->orgId,
            'hostname'      => $this->hostName,
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->mockCreateNetBankingConfig();

        $this->startTest();
    }

    public function testCreatePosPaymentsForUpi()
    {
        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'hdfc_ezetap',
            'gateway_merchant_id'      => '12344',
            'gateway_acquirer'         => 'hdfc',
            'upi'                       => 1,
            'type'                      => [
                'pos' => '1',
                'direct_settlement_with_refund' => '1',
                'non_recurring'             => '1'
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->create('terminal', $attributes);

        $this->fixtures->pricing->createTestPlanForPosPayments();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $testData = $this->testData['testCreatePosPayments'];

        $testData['request']['content']['method'] = 'upi';

        unset($testData['request']['content']['card']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $response = $this->startTest($testData);

        $paymentEntity = (new Payment\Repository())->findByPublicId($response['id']);

        $terminalEntity = (new \RZP\Models\Terminal\Repository())->fetchForPayment($paymentEntity)->toArray();

        $this->assertEquals('captured', $response['status']);

        $this->assertEquals($terminalEntity['gateway'],'hdfc_ezetap');
    }

    public function testCreateExistingPosPayment(){

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['meta'] = [
            'reference_id' => '180829064415993E010034214'
        ];

        $payment['amount'] = '10236';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $paymentAuth = $this->doAuthPayment($payment);

        $testData = $this->testData['testCreatePosPayments'];

        $testData['request']['content']['method'] = 'upi';

        $testData[ 'response' ] = [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment has already been either captured or voided',
                ],
            ],
            'status_code' => 400,
        ];
        $testData['exception'] = [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED_OR_VOIDED
        ];

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $this->startTest($testData);

    }

    public function testCreatePosPaymentsForUpiWithoutPricing()
    {

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $testData = $this->testData['testCreatePosPayments'];

        $testData['request']['content']['method'] = 'upi';

        unset($testData['request']['content']['card']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData[ 'response' ] = [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ];
        $testData['exception'] = [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT
        ];

        $this->startTest($testData);

    }

    public function testCreatePosPaymentsWithoutPricing()
    {
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $testData[ 'response' ] = [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ];

        $testData['exception'] = [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT
        ];

        $this->startTest($testData);
    }

    public function testCreateReminderPaymentforPos()
    {
        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $response = $this->startTest($testData);

        $this->assertEquals('authorized', $response['status']);

        $payment_id = explode('_', $response['id']);

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/reminders/send/test/payment/capture_pos_payment/' . $payment_id[1],
            'method'  => 'post',
        ];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
            ],
        ];

        $payment = Payment\Entity::findOrFail($payment_id[1]);
        $payment->setGateway('hdfc_ezetap');
        $this->repo = (new Payment\Repository());
        $this->repo->saveOrFail($payment);

        $this->ba->reminderAppAuth();

        $this->startTest();

        $data  = DB::select('select * from payments order by created_at desc limit 1')[0];

        $this->assertEquals('captured', $data->status);

    }

    public function testCreateReminderPaymentforPosStatusCaptured()
    {
        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $response = $this->startTest($testData);

        $this->assertEquals('authorized', $response['status']);

        $payment_id = explode('_', $response['id']);

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/reminders/send/test/payment/capture_pos_payment/' . $payment_id[1],
            'method'  => 'post',
        ];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'error' =>  ['code' => 'BAD_REQUEST_ERROR']
            ],
            'status_code'   => 400,
        ];

        $this->testData[__FUNCTION__]['exception'] = [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE
        ];

        $payment = Payment\Entity::findOrFail($payment_id[1]);
        $payment->setStatus(Payment\Status::CAPTURED);
        $this->repo = (new Payment\Repository());
        $this->repo->saveOrFail($payment);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testCheckPaymentRetryIfAuthorisePaymentFailsFirstTimeForCard()
    {
        try{
            $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

            $this->ba->expressAuth('test', 'rzp_test_10000000000000');

            $testData = $this->testData['testCreatePosPayments'];

            $this->startTest($testData);
        }
        catch (\Throwable $ex)
        {
            $this->assertNotNull($ex);
        }

        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'hdfc_ezetap',
            'gateway_merchant_id'      => '12344',
            'gateway_acquirer'         => 'hdfc',
            'card'                       => 1,
            'type'                      => [
                'pos' => '1',
                'direct_settlement_with_refund' => '1',
                'non_recurring'             => '1'
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::RULE_FILTER]);

        $this->fixtures->create('terminal', $attributes);

        $this->fixtures->pricing->createTestPlanForPosPayments();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $response = $this->startTest($testData);

        $input = $testData['request']['content'];

        $first6 = implode(explode("-",substr($input['card']['number'],0,7)));

        $last4  = substr($input['card']['number'],-4);

        $cardEntity = $response['card_id'];

        $cardNumber = (new Repository())->findByPublicId($cardEntity)->toArray();

        $paymentEntity = (new Payment\Repository())->findByPublicId($response['id']);

        $terminalEntity = (new \RZP\Models\Terminal\Repository())->fetchForPayment($paymentEntity)->toArray();

        $this->assertEquals($first6,$cardNumber['iin']);

        $this->assertEquals($last4,$cardNumber['last4']);

        $this->assertEquals($terminalEntity['gateway'],'hdfc_ezetap');

        $this->assertEquals('authorized', $response['status']);

    }

    public function testCheckPaymentRetryIfAuthorisePaymentFailsFirstTimeForUpi()
    {
        try{
            $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

            $this->ba->expressAuth('test', 'rzp_test_10000000000000');

            $testData = $this->testData['testCreatePosPayments'];

            $testData['request']['content']['method'] = 'upi';

            unset($testData['request']['content']['card']);

            $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

            $this->startTest($testData);
        }
        catch (\Throwable $ex)
        {
            $this->assertNotNull($ex);
        }

        $attributes = [
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'hdfc_ezetap',
            'gateway_merchant_id'      => '12344',
            'gateway_acquirer'         => 'hdfc',
            'upi'                       => 1,
            'type'                      => [
                'pos' => '1',
                'direct_settlement_with_refund' => '1',
                'non_recurring'             => '1'
            ],
            'enabled'                   => 1,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::RULE_FILTER]);

        $this->fixtures->create('terminal', $attributes);

        $this->fixtures->pricing->createTestPlanForPosPayments();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1zD0BpqeO1qqpB']);

        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $testData['request']['content']['method'] = 'upi';

        unset($testData['request']['content']['card']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $response = $this->startTest($testData);

        $input = $testData['request']['content'];

        $paymentEntity = (new Payment\Repository())->findByPublicId($response['id']);

        $terminalEntity = (new \RZP\Models\Terminal\Repository())->fetchForPayment($paymentEntity)->toArray();

        $this->assertEquals($terminalEntity['gateway'],'hdfc_ezetap');

        $this->assertEquals('captured', $response['status']);

    }

    public function testCreateReminderPaymentforNonPos()
    {
        $this->ba->expressAuth('test','rzp_test_10000000000000');

        $testData = $this->testData['testCreatePosPayments'];

        $response = $this->startTest($testData);

        $this->assertEquals('authorized', $response['status']);

        $payment_id = explode('_', $response['id']);

        $this->testData[__FUNCTION__]['request'] =  [
            'url'     => '/reminders/send/test/payment/capture_pos_payment/' . $payment_id[1],
            'method'  => 'post',
        ];

        $this->testData[__FUNCTION__]['response'] =   [
            'content' => [
                'error' =>  ['code' => 'BAD_REQUEST_ERROR']
            ],
            'status_code'   => 400,
        ];


        $this->testData[__FUNCTION__]['exception'] = [
        'class'               => 'RZP\Exception\BadRequestException',
        'internal_error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE
        ];

        $payment = Payment\Entity::findOrFail($payment_id[1]);
        $payment->setReceiverType('nonpos');
        $this->repo = (new Payment\Repository());
        $this->repo->saveOrFail($payment);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testCheckOfferApplicabilityForPaymentUsingSavedCardWithMappingAvailable()
    {
        $this->ba->publicAuth();

        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer', [
            'iins' => ['461786'],
            'block' => true,
            'type' => 'instant',
        ]);

        $offer2 = $this->fixtures->create('offer', [
            'iins' => ['403776','400782'],
            'block' => true,
            'type' => 'instant',
        ]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getDefaultTokenPanPaymentArray();

        $payment['offer_id'] = 'offer_' . $offer2->getId();
        $payment['order_id'] = 'order_' . $order->getId();
        $payment['amount'] = $order->getAmount();

        // the card number 4044649165235890 has actual bin = 400782 and token bin = 404464916
        // which is present in offer2. so the offer2 gets applied in this case.
        // we are asserting 200 code to ensure offer got applied without errors.
        $payment['card']['number'] = '4044649165235890';

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = $payment;

        $response = $this->makeRequestParent($testData['request']);
        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function testCheckOfferApplicabilityForPaymentUsingSavedCardWithMappingUnavailable()
    {
        $this->ba->publicAuth();

        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer', [
            'iins' => ['461786'],
            'block' => true,
            'type' => 'instant',
        ]);

        $offer2 = $this->fixtures->create('offer', [
            'iins' => ['403776','400782'],
            'block' => true,
            'type' => 'instant',
        ]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getDefaultTokenPanPaymentArray();

        $payment['offer_id'] = 'offer_' . $offer1->getId();
        $payment['order_id'] = 'order_' . $order->getId();
        $payment['amount'] = $order->getAmount();

        // here we are using the default card number 4012001038443335 & it has no bin mapping at our end
        // so any offer doesn't get applied here. and throws 400 response code (which we are expecting)
        // because offer has block = true.

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = $payment;

        $this->expectException(BadRequestValidationFailureException::class);
        $response = $this->makeRequestParent($testData['request']);
        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
    }

    public function testSaveCardWhenNewCardAndNetworkTokenisationPaidFlagNotEnabledExpectsCardNotSaved()
    {
        $this->fixtures->merchant->removeFeatures(['network_tokenization_paid']);

        $paymentDetails = $this->getDefaultPaymentArray();

        $paymentDetails['_']['library'] = 'razorpayjs';

        $paymentDetails['save'] = 1;

        $paymentDetails['customer_id'] = 'cust_100000customer';

        $PaymentResponse = $this->doAuthPayment($paymentDetails);

        $payment = $this->getDbEntityById('payment', $PaymentResponse['razorpay_payment_id'], true);

        $this->assertNull($payment->localToken);

        $this->assertNull($payment->globalToken);
    }

    public function testSaveCardWhenNetworkTokenisationPaidFlagNotEnabledAndNotCustomCheckoutMerchantLibraryExpectsCardSavedAndTokenised()
    {
        $this->fixtures->merchant->removeFeatures(['network_tokenization_paid']);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live'], '10000000000000');

        $this->mockCardVaultWithMigrateToken();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $paymentDetails = $this->getDefaultPaymentArray();

        $paymentDetails['_']['library'] = 'checkoutjs';

        $paymentDetails['save'] = 1;

        $paymentDetails['customer_id'] = 'cust_100000customer';

        $PaymentResponse = $this->doAuthPayment($paymentDetails);

        $payment = $this->getDbEntityById('payment', $PaymentResponse['razorpay_payment_id'], true);

        $this->assertNotNull($payment->localToken);

        $this->assertNull($payment->globalToken);

        $this->assertNotNull($payment->localToken['acknowledged_at']);

        $this->assertEquals('visa', $payment->localToken->card->getVault());
    }

    public function testSaveCardWhenExistingSavedCardAndNetworkTokenisationPaidFlagNotEnabledExpectsCardTokenisationConsentNotSaved()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(
            [
                'cust_checkout_cnsnt_scrn',
                'network_tokenization_live',
            ]
        );

        $payment = $this->getDefaultPaymentArray();

        $this->mockCardVaultWithMigrateToken();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $payment['card'] = array('cvv' => 111);
        $payment['_']['library'] = 'razorpayjs';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['consent_to_save_card'] = 1;
        $payment['token'] = 'token_100000custcard';

        $this->doAuthPaymentViaCheckoutRoute($payment);

        $token = $this->getDbEntityById('token', '100000custcard', true);

        $this->assertNull($token['acknowledged_at']);

        $this->assertEquals('rzpvault', $token->card->getVault());
    }

    public function testFetchPaymentsCardEntity()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        $payment = $this->getLastPayment(true);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'] . '/card';

        $data = $this->startTest();

        $this->assertNotEmpty($data);

        $this->assertEquals('card', $data['entity']);
        $this->assertEquals('credit', $data['type']);
        $this->assertEquals('3335', $data['last4']);
        $this->assertEquals('Visa', $data['network']);
        $this->assertEquals('HDFC', $data['issuer']);

        // Test archival case : remove card data from test db (current) and add in live db.

        $cardId = substr($data['id'], 5);

        $cardEntity = \DB::table('cards')->select(\DB::raw("*"))->where('id', '=', $cardId)->get()->first();

        $card = (array)$cardEntity;

        // insert card into live DB
        \DB::connection('live')->table('cards')->insert($card);

        \DB::connection('test')->statement('SET FOREIGN_KEY_CHECKS=0');

        // remove card from test db
        \DB::connection('test')->table('cards')->where('id', '=', $cardId)->limit(1)->update(['id' => 'KOOmLB0xqazzXp']);

        $cardEntity = \DB::connection('test')->table('cards')->select(\DB::raw("*"))->where('id', '=', $cardId)->get()->first();

        $this->assertNull($cardEntity);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/' . $payment['id'] . '/card';

        $data = $this->startTest();

        $this->assertNotEmpty($data);

        $this->assertEquals('card', $data['entity']);
        $this->assertEquals('credit', $data['type']);
        $this->assertEquals('3335', $data['last4']);
        $this->assertEquals('Visa', $data['network']);
        $this->assertEquals('HDFC', $data['issuer']);

        \DB::connection('test')->table('cards')->where('id', '=', 'KOOmLB0xqazzXp')->limit(1)->update(['id' => $cardId]);

        \DB::connection('test')->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimit()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '2000100';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);
            print_r($response);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'amount');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Amount exceeds maximum amount allowed.');
        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Amount exceeds maximum amount allowed.');

    }

    public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimitConfigKey()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::DEFAULT_OPGSP_TRANSACTION_LIMIT_USD => "100000"
            ]);

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000100';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'amount');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Amount exceeds maximum amount allowed.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Amount exceeds maximum amount allowed.');

    }

    public function testOpgspImportPaymentWithoutInvoiceNumber()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'notes');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Invoice number field is required with in the notes.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Invoice number field is required with in the notes.');

    }

    public function testOpgspImportDuplicateInvoiceNumberForSuccessfulPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'notes');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment already exist with same invoice number.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment already exist with same invoice number.');
    }

    public function testOpgspImportDuplicateInvoiceNumberForFailedPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');

        $this->fixtures->edit('payment', $responseContent['razorpay_payment_id'], ['status' => 'failed']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
    }

    public function testOpgspImportPaymentWithUnsupportedLibrary()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doAuthPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'The payment request has invalid library');

        },
            \RZP\Exception\BadRequestException::class,
            'The payment request has invalid library');

    }

    public function testOpgspImportPaymentWithUnsupportedMethod()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method invalid / not allowed');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment method invalid / not allowed');

    }

    public function testOpgspImportPaymentPositive()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

    }

    public function testOpgspImportPaymentNBPositive()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for NB rearch pass
        $this->mockPGRouterForRearch();
        $order = $this->fixtures->order->createPaymentCaptureOrder(['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);
    }

    public function testUpdateMerchantDocumentForPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'    => '/payment/'.$paymentEntity['id'].'/update_merchant_doc',
            'method' => 'patch',
            'content' => [
                'document_id' => "doc_1234567890",
                'document_type' => "opgsp_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentSupportingDocs = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentSupportingDocs['ref_num'],'doc_1234567890');
    }

    /**
     * @param $paymentEntity
     * @param array $billingAddressArray
     */
    protected function validatePaymentBillingAddress($paymentEntity, array $billingAddressArray): void
    {
        $paymentAddressEntity = (new \RZP\Models\Address\Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        $this->assertNotNull($paymentAddressEntity);

        $this->validateBillingAddress($billingAddressArray, $paymentAddressEntity);
    }

    /**
     * @param $postal_code
     * @param $addressEntity
     */
    private function validateBillingAddress($billingAddress, $addressEntity): void
    {
        foreach (['line1', 'line2', 'city', 'state', 'country'] as $attribute) {
            $this->assertEquals($billingAddress[$attribute], $addressEntity[$attribute]);
        }

        $this->assertEquals($billingAddress['postal_code'], $addressEntity['zipcode']);
    }

    private function mockPGRouterForRearch()
    {
        $this->enablePgRouterConfig();

        // mock the experiments for s2s payment
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();
        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 's2s_card_payments_via_pg_router_v2' or
                        $feature === 'netbanking_payments_via_pg_router_disable_mid' or
                        $feature === 'netbanking_payments_via_pg_router_create_json' or
                        $feature === 'netbanking_payments_via_pg_router')
                    {
                        return 'on';
                    }
                    return 'off';
                }));

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('pg_router', $pgService);
        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'pg_router' => 'true'
                    ]
                ];
            });
    }

    public function testPaymentCreateDualWrite()
    {
        $paymentData = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($paymentData);

        $payment = $this->getDbEntityById('payment', $content['id']);

        $paymentsNew = \DB::table('payments_new')->select(\DB::raw("*"))->where('id', '=', $payment['id'])->get()->first();

        $this->assertNotNull($paymentsNew);

        $paymentsNewArray = (array) $paymentsNew;

        // dual write assertions
        $this->assertEquals($paymentsNewArray['id'], $payment['id']);
        $this->assertEquals($paymentsNewArray['merchant_id'], $payment['merchant_id']);
        $this->assertEquals($paymentsNewArray['amount'], $payment['amount']);
        $this->assertEquals($paymentsNewArray['currency'], $payment['currency']);
        $this->assertEquals($paymentsNewArray['status'], $payment['status']);
        $this->assertEquals($paymentsNewArray['disputed'], $payment['disputed']);
        $this->assertEquals($paymentsNewArray['refund_status'], $payment['refund_status']);
        $this->assertEquals($paymentsNewArray['created_at'], $payment['created_at']);
        $this->assertEquals($paymentsNewArray['updated_at'], $payment['updated_at']);
    }

    public function testPaymentDualWriteSyncRoute()
    {
        $paymentData = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($paymentData);

        $payment = $this->getDbEntityById('payment', $content['id']);

        \DB::table('payments_new')->where('id', '=', $payment['id'])->delete();

        $paymentsNew = \DB::table('payments_new')->select(\DB::raw("*"))->where('id', '=', $payment['id'])->get()->first();

        $this->assertNull($paymentsNew);

        $this->ba->cronAuth();

        $testData = [
            'request' => [
                'method'  => 'POST',
                'url'     => '/payments/dual_write/sync',
                'content' => [
                    'payment_ids' => [$payment['id']]
                ],
            ],
            'response' => [
                'content' => [
                    'synced_payment_ids' => [$payment['id']]
                ]
            ]
        ];

        $this->runRequestResponseFlow($testData);

        $paymentsNew = \DB::table('payments_new')->select(\DB::raw("*"))->where('id', '=', $payment['id'])->get()->first();

        $this->assertNotNull($paymentsNew);

        $paymentsNewArray = (array) $paymentsNew;

        $this->assertEquals($paymentsNewArray['id'], $payment['id']);
        $this->assertEquals($paymentsNewArray['merchant_id'], $payment['merchant_id']);
        $this->assertEquals($paymentsNewArray['amount'], $payment['amount']);
        $this->assertEquals($paymentsNewArray['created_at'], $payment['created_at']);
        $this->assertEquals($paymentsNewArray['updated_at'], $payment['updated_at']);

        \DB::table('payments_new')->where('id', '=', $payment['id'])->update(['amount' => 87793]);

        $paymentsNew = \DB::table('payments_new')->select(\DB::raw("*"))->where('id', '=', $payment['id'])->get()->first();

        $paymentsNewArray = (array) $paymentsNew;

        $this->assertEquals('87793', $paymentsNewArray['amount']);

        $this->runRequestResponseFlow($testData);

        $paymentsNew = \DB::table('payments_new')->select(\DB::raw("*"))->where('id', '=', $payment['id'])->get()->first();

        $this->assertNotNull($paymentsNew);

        $paymentsNewArray = (array) $paymentsNew;

        $this->assertEquals($paymentsNewArray['id'], $payment['id']);
        $this->assertEquals($paymentsNewArray['amount'], $payment['amount']);
        $this->assertEquals($paymentsNewArray['created_at'], $payment['created_at']);
        $this->assertEquals($paymentsNewArray['updated_at'], $payment['updated_at']);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndWithStandardCheckoutLibraryExpectsSuccessfulPaymentCreation(): void
    {
        $this->doPaymentCreateAndCalculateFees('checkoutjs');
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndWithHostedCheckoutLibraryExpectsSuccessfulPaymentCreation()
    {
        $this->doPaymentCreateAndCalculateFees('hosted');
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndWithCustomCheckoutLibraryExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $this->doPaymentCreateAndCalculateFees('razorpayjs', false);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndNonRazorpayOrgMerchantExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                "org_id" => Org::HDFC_ORG,
            ]
        );

        $this->doPaymentCreateAndCalculateFees('checkoutjs', false);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndOptimizerMerchantExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::RAAS]);

        $this->doPaymentCreateAndCalculateFees('checkoutjs', false);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndNonINRCurrencyExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $input['currency'] = 'USD';

        $this->doPaymentCreateAndCalculateFees('checkoutjs', false, $input);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndEmailRequiredMerchantExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $this->fixtures->merchant->addFeatures([Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->fixtures->merchant->removeFeatures([Dcs\Features\Constants::EmailOptionalOnCheckout]);

        $this->doPaymentCreateAndCalculateFees('checkoutjs', false);
    }

    public function testPaymentCreateWhenInputEmailIsPresentAndEmailRequiredMerchantExpectsSuccessfulPaymentCreation()
    {
        $this->fixtures->merchant->addFeatures([Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->fixtures->merchant->removeFeatures([Dcs\Features\Constants::EmailOptionalOnCheckout]);

        $input['email'] = 'randomemail@gmail.com';

        $this->doPaymentCreateAndCalculateFees('checkoutjs', true, $input);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndEmailOptionalMerchantExpectsSuccessfulPaymentCreation()
    {
        $this->fixtures->merchant->addFeatures([Dcs\Features\Constants::ShowEmailOnCheckout, Dcs\Features\Constants::EmailOptionalOnCheckout]);

        $this->doPaymentCreateAndCalculateFees('checkoutjs');
    }

    public function testPaymentCreateWhenInputEmailIsPresentAndEmailOptionalMerchantExpectsSuccessfulPaymentCreation()
    {
        $this->fixtures->merchant->addFeatures([Dcs\Features\Constants::ShowEmailOnCheckout, Dcs\Features\Constants::EmailOptionalOnCheckout]);

        $input['email'] = 'randomemail@gmail.com';

        $this->doPaymentCreateAndCalculateFees('checkoutjs', true, $input);
    }

    public function testPaymentCreateWhenInputEmailIsPresentAndEmailLessMerchantExpectsSuccessfulPaymentCreation()
    {
        $this->fixtures->merchant->addFeatures([Dcs\Features\Constants::ShowEmailOnCheckout, Dcs\Features\Constants::EmailOptionalOnCheckout]);

        $input['email'] = 'randomemail@gmail.com';

        $this->doPaymentCreateAndCalculateFees('checkoutjs', true, $input);
    }

    public function testPaymentCreateWhenInputEmailIsNotPresentAndCheckoutEmailLessMerchantAndS2SPaymentExpectsPaymentCreationFailureWithEmailRequiredException()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        unset($payment['email']);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->makeRequestAndCatchException(
            function () use ($payment) {
                $this->doS2SPrivateAuthPayment($payment);
            },
            BadRequestValidationFailureException::class,
            'The email field is required.'
        );
    }

    protected function doPaymentCreateAndCalculateFees($library, $expectsSuccessfulPayment = true, $input = [])
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['email']);

        if (isset($input['email'])) {
            $payment['email'] = $input['email'];
        }

        $payment['currency'] = $input['currency'] ?? 'INR';

        $payment['_']['library'] = $library;

        $paymentCreatUrls = ['/payments/create/ajax', '/payments/create/checkout', '/payments/create/fees'];

        foreach ($paymentCreatUrls as $url) {
            $this->ba->publicAuth();

            if ($url === '/payments/create/fees') {
                $this->fixtures->merchant->enableConvenienceFeeModel();
                $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);
            }

            $request = [
                'method'  => 'POST',
                'url'     => $url,
                'content' => $payment,
            ];

            if ($expectsSuccessfulPayment) {
                $response = $this->makeRequestAndGetContent($request);

                if ($url === '/payments/create/fees') {
                    $this->assertEquals(10, $response['display']['fees']);
                }
                else {
                    $this->assertNotEmpty($response['razorpay_payment_id']);

                    $currentPayment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

                    $this->assertEquals('authorized', $currentPayment['status']);

                    $this->capturePayment($response['razorpay_payment_id'], $payment['amount'], $payment['currency']);

                    $currentPayment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

                    $this->assertEquals('captured', $currentPayment['status']);

                    if (isset($input['email'])) {
                        $this->assertEquals($input['email'], $currentPayment['email']);
                    }
                }
            }
            else {
                $this->makeRequestAndCatchException(
                    function () use ($request) {
                        $this->makeRequestAndGetContent($request);
                    },
                    BadRequestValidationFailureException::class,
                    'The email field is required.'
                );
            }
        }
    }

    public function testCreatePaymentWithOptimizerOnlyFlagEnabledRaasDisabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['optimizer_only_merchant']);
        try{
            $this->doAuthPayment($payment);
        }
        catch(\Throwable $e){
            $this->assertEquals($e->getCode(),"BAD_REQUEST_OPTIMIZER_ONLY_MERCHANT_HAS_RAAS_DISABLED");
        }
    }

    public function testCreatePaymentWithOptimizerOnlyFlagEnabledRaasEnabled()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('order', ['id' => '100000000order']);

        $payment['amount'] = 1000000;

        $payment['order_id'] = 'order_100000000order';

        $this->fixtures->merchant->addFeatures(['optimizer_only_merchant']);
        $this->fixtures->merchant->addFeatures(['raas']);

        $this->doAuthPayment($payment);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        if (strpos($response->getContent(), 'mock/netbanking/axis') != null)
        {
            list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
            $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
            return $this->submitPaymentCallbackRedirect($data);
        }

        return $this->runPaymentCallbackFlowForNbplusGateway($response, $gateway, $callback);
    }

    public function testPaymentOnPartnerAuthWithSubmManualSettlementEnabled()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['type' => 'partner', 'id' => 'AwtIC8XQqM0Wet']);

        $submerchant = $this->fixtures->merchant->createWithBalance();

        $featureParams = [
            Feature\Entity::ENTITY_ID   => '10000000000000',
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_manual_settlement',
        ];

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->fixtures->create('feature', $featureParams);

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create('merchant_access_map', ['entity_id' => $client->getApplicationId(), 'merchant_id' => $submerchant->getId()]);

        $this->mockAllSplitzTreatment();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doPartnerAuthPayment($payment, $client->getId(), $submerchant->getId());

        $this->capturePaymentByPartnerAuth($response['razorpay_payment_id'], $payment['amount'], $client, 'acc_' . $submerchant->getId());

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertTrue($transaction['on_hold']);
    }

    public function testPaymentOnOAuthWithSubmManualSettlementEnabled()
    {
        $accessToken = $this->setPurePlatformContext(Mode::TEST);

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_manual_settlement',
        ];

        $this->fixtures->create('feature', $featureParams);

        $this->mockAllSplitzTreatment();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $this->capturePaymentByOAuth($response['razorpay_payment_id'], $payment['amount'], $accessToken);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertTrue($transaction['on_hold']);
    }

    public function testPaymentOnOAuthWithSubmManualSettlementEnabledOnAppId()
    {
        $accessToken = $this->setPurePlatformContext(Mode::TEST);

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_PLATFORM_APP_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::APPLICATION,
            Feature\Entity::NAME        => 'subm_manual_settlement',
        ];

        $this->fixtures->create('feature', $featureParams);

        $this->mockAllSplitzTreatment();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $this->capturePaymentByOAuth($response['razorpay_payment_id'], $payment['amount'], $accessToken);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertTrue($transaction['on_hold']);
    }

    public function testPaymentOnOAuthWithSubmManualSettlementEnabledOnAppIdAndExpDisabled()
    {
        $accessToken = $this->setPurePlatformContext(Mode::TEST);

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_PLATFORM_APP_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::APPLICATION,
            Feature\Entity::NAME        => 'subm_manual_settlement',
        ];

        $this->fixtures->create('feature', $featureParams);

        $splitzResponse = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockAllSplitzTreatment($splitzResponse);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $this->capturePaymentByOAuth($response['razorpay_payment_id'], $payment['amount'], $accessToken);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertFalse($transaction['on_hold']);
    }

    public function testPaymentOnOAuthWithSubmManualSettlementDisabled()
    {
        $accessToken = $this->setPurePlatformContext(Mode::TEST);

        $this->mockAllSplitzTreatment();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $this->capturePaymentByOAuth($response['razorpay_payment_id'], $payment['amount'], $accessToken);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertFalse($transaction['on_hold']);
    }

    public function testPaymentS2SOnPartnerAuthWithSubmManualSettlementEnabled()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['type' => 'partner', 'id' => 'AwtIC8XQqM0Wet']);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create(['entity_type' => 'application', 'entity_id' => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->feature->create(['entity_type' => 'merchant', 'entity_id' => '10000000000000', 'name' => 'subm_manual_settlement']);

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create('merchant_access_map', ['entity_id' => $client->getApplicationId(), 'merchant_id' => $sub->getId()]);

        $payment = $this->getDefaultPaymentArray();

        $this->mockAllSplitzTreatment();

        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->capturePaymentByPartnerAuth($response['razorpay_payment_id'], $payment['amount'], $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals($pay['public_id'], $response['razorpay_payment_id']);

        $this->assertEquals($pay['status'], 'captured');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertTrue($transaction['on_hold']);
    }

    public function testPaymentOnPartnerAuthWithSubmManualSettlementDisabled()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['type' => 'partner', 'id' => 'AwtIC8XQqM0Wet']);

        $submerchant = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create('merchant_access_map', ['entity_id' => $client->getApplicationId(), 'merchant_id' => $submerchant->getId()]);

        $this->mockAllSplitzTreatment();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doPartnerAuthPayment($payment, $client->getId(), $submerchant->getId());

        $this->capturePaymentByPartnerAuth($response['razorpay_payment_id'], $payment['amount'], $client, 'acc_' . $submerchant->getId());

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals($payment['public_id'], $response['razorpay_payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertFalse($transaction['on_hold']);
    }
    public function testRearchPaymentCreateAjaxSodexo()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes

        $order = $this->fixtures->order->createPaymentCaptureOrder();

        $this->enablePgRouterConfig();

        $payment = [
            'amount'            => '40000',
            'currency'          => 'INR',
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => [
                'merchant_order_id' => 'random order id',
            ],
            'description'       => 'random description',
            'provider'          => 'sodexo',
        ];

        $payment['card'] = array(
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2032',
            'cvv'               => '566',
        );

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'data' => [
                            'pg_router' => 'true'
                        ]
                    ]

                ];
            });

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $response = $this->makeRequestParent($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['data']['pg_router'], 'true');
    }

}
