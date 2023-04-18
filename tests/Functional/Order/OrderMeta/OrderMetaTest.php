<?php

namespace RZP\Tests\Functional\Order\OrderMeta;

use DB;
use RZP\Models\Order;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class OrderMetaTest
 *
 * @package Functional\Order\OrderMeta
 */
class OrderMetaTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/OrderMetaTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testOrderMetaWithTaxInvoice()
    {
        $request = $this->getOrderMetaArrayWithTaxInvoice();

        $response = $this->startOrderMetaFlow($request);

        $orderMeta = $this->getDbLastOrderMeta();

        $order = $this->getDbLastOrder();

        $this->assertCount(1, ($order->orderMetas), 'Number of order meta for same order should be 1');

        $this->assertSame($order->getId(), $orderMeta->getOrderId(), 'Ids should be same');

        $this->assertEquals('tax_invoice', $orderMeta->getType());

        $this->assertSame($response['tax_invoice'], $orderMeta->getValue());
    }

    public function testOrderMetaWithoutMandatoryGstFields()
    {
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'method'      => 'upi',
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'supply_type'    => 'interstate',
                    'cess_amount'    => 12500,
                ],
            ]
        );

        $response = $this->startOrderMetaFlow($baseRequest);

        //Since flow is not gst, then orderMeta entity should not be created and,
        //response should not have invoice block.
        $this->assertArrayNotHasKey('tax_invoice', $response);

        $this->assertNull($this->getDbLastEntity('order_meta'));
    }

    public function testOrderMetaWithTaxInvoiceOddValues()
    {
        $request = $this->getOrderMetaArrayWithTaxInvoice();

        $request['tax_invoice']['gst_amount'] = 15;

        $response = $this->startOrderMetaFlow($request);

        $orderMeta = $this->getDbLastOrderMeta();

        $order = $this->getDbLastOrder();

        $this->assertCount(1, ($order->orderMetas), 'Number of order meta for same order should be 1');

        $this->assertSame($response['tax_invoice'], $orderMeta->getValue());
    }

    public function testOrderMetaWithUnsupportedMethod()
    {
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'method'      => 'netbanking',
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 10000,
                    'supply_type'    => 'intrastate',
                    'cess_amount'    => 12500,
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ],
            ]
        );

        $response = $this->startOrderMetaFlow($baseRequest);

        $this->assertArrayNotHasKey('tax_invoice', $response);

        $orderMeta = $this->getDbLastEntity('order_meta');

        $this->assertNull($orderMeta);
    }

    /*********************** Tax Invoice Validation Test cases *************************/

    /**
     * @dataProvider functionValidateOrderMetaTaxInvoiceCreate
     *
     * @param array $request
     * @param null $expectionClass
     * @param null $exceptionMessage
     */
    public function testValidateOrderMetaTaxInvoiceCreate(array $request, $expectionClass = null,
                                                                $exceptionMessage = null)
    {
        $this->makeRequestAndCatchException(
            function () use ($request)
            {
                $this->startOrderMetaFlow($request);
            },
            $expectionClass,
            $exceptionMessage
        );

        $orderMeta = $this->getDbLastEntity('order_meta');

        $this->assertNull($orderMeta);
    }

    public function functionValidateOrderMetaTaxInvoiceCreate()
    {
        // Missing GST Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice();
        unset($baseRequest['tax_invoice']['gst_amount']);
        $cases['missing_gst_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount field is required when supply type is present.',
        ];

        // Missing Cess Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice();
        unset($baseRequest['tax_invoice']['cess_amount']);
        $cases['missing_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount field is required when supply type is present.',
        ];

        // Invalid GSTIN length
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '1234345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                ],
            ]
        );
        $cases['invalid_gstin_length'] =
            [
                $baseRequest,
                BadRequestValidationFailureException::class,
                'The business gstin must be 15 characters.',
            ];


        // Invalid Supply Type
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'random_supply_type',
                ],
            ]
        );
        $cases['invalid_supply_type'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'random_supply_type is not a valid supply type',
        ];

        // Negative Cess Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'cess_amount'    => -1000,
                ],
            ]
        );
        $cases['negative_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount must be at least 0.',
        ];

        // Negative GST Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => -1000,
                ],
            ]
        );
        $cases['negative_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount must be at least 0.',
        ];

        // Invalid date - float input
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => 1000,
                    'date'           => 12.56,
                ],
            ]
        );
        $cases['invalid_invoice_date - float_input'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'date must be an integer.',
        ];

        // Invalid date - out of range
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => 1000,
                    'date'           => 626286666,
                ],
            ]
        );
        $cases['invalid_invoice_date - outside_allowed_range'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'date must be between 946684800 and 4765046400',
        ];

        //CESS Amount & GST Amount passed, Supply Type missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 10000,
                    'cess_amount'    => 12500,
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ],
            ]
        );
        $cases['supply_type_absent'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The supply type field is required.',
        ];

        //GST Amount & Supply Type passed, CESS Amount missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 12500,
                    'supply_type'    => 'interstate',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ],
            ]
        );
        $cases['supply_type_present_cess_amount_missing'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount field is required when supply type is present.',
        ];

        //CESS Amount & Supply Type passed, GST Amount missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'cess_amount'    => 12500,
                    'supply_type'    => 'interstate',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ],
            ]
        );
        $cases['supply_type_present_gst_amount_missing'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount field is required when supply type is present.',
        ];

        return $cases;
    }

    public function test1CCOrderCreate()
    {
        self::setUp1CCMerchant();
        $this->ba->privateAuth();

        $response = $this->startTest();

        $orderId = $response['id'];

        Order\Entity::verifyIdAndSilentlyStripSign($orderId);

        $orderMeta = DB::select('select * from order_meta')[0];

        $this->assertEquals($orderId, $orderMeta->order_id);

        $this->assertNotEmpty($orderMeta->value);
    }

    public function testNon1CCOrderCreateFor1CCMerchant()
    {
        self::setUp1CCMerchant();
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testUpdateCustomerDetailsFor1CCOrder()
    {
        self::setUp1CCMerchant();
        $orderId = self::create1CCOrder();
        $this->ba->publicAuth();
        $url = "/orders/1cc/$orderId/customer/";

        $cacheKey = "SHIPPING_INFO_10000000000000_"
            . $orderId
            . "_1000_110085_Delhi_in";

        $this->app['cache']->put($cacheKey, [
            "serviceable"  => true,
            "cod"          => true,
            "cod_fee"      => 50,
            "shipping_fee" => 60,
        ],2400);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;
        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateNocodeAppsCustomerDetailsFor1CCOrder()
    {
        $order = $this->fixtures->order->create([
            'receipt'       => 'receipt',
            'product_type'  => Order\ProductType::PAYMENT_STORE
        ]);
        $orderId = $order->getPublicId();
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);


        $this->ba->publicAuth();
        $url = "/orders/1cc/$orderId/customer/";

        $cacheKey = "SHIPPING_INFO_10000000000000_"
            . $orderId
            . "_1000_110085_Delhi_in";

        $this->app['cache']->put($cacheKey, [
            "serviceable"  => true,
            "cod"          => false,
            "cod_fee"      => 0,
            "shipping_fee" => 0,
        ],2400);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;
        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateCustomerDetailsFor1CCOrderForNonServiceableAddress()
    {
        self::setUp1CCMerchant();
        $orderId = self::create1CCOrder();
        $this->ba->publicAuth();
        $url = "/orders/1cc/$orderId/customer/";

        $cacheKey = "SHIPPING_INFO_10000000000000_"
            . $orderId
            . "_1000_110085_Delhi_in";

        $this->app['cache']->put($cacheKey, [
            "serviceable"  => false,
            "cod"          => true,
            "cod_fee"      => 50,
            "shipping_fee" => 60,
        ],2400);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;
        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateCustomerDetailsFor1CCOrderWithoutServiceabilityDetailsInCache()
    {
        self::setUp1CCMerchant();

        $orderId = self::create1CCOrder();

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/orders/1cc/$orderId/customer/";

        $this->startTest();
    }

    public function testUpdateCustomerDetailsForNon1CCOrder()
    {
        self::setUp1CCMerchant();
        $order = $this->fixtures->order->create();
        $orderId = $order->getPublicId();

        $this->ba->publicAuth();

        $url = "/orders/1cc/$orderId/customer/";

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateCustomerDetailsForNon1CCMerchant()
    {
        $order = $this->fixtures->order->create();
        $orderId = $order->getPublicId();

        $this->ba->publicAuth();

        $url = "/orders/1cc/$orderId/customer/";

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateCustomerDetailsForPaid1CCOrder()
    {
        $this->setUp1CCMerchant();
        $orderId = $this->create1CCOrder();
        $this->fixtures->order->edit($orderId, ['status' => 'paid']);
        $this->ba->publicAuth();
        $url = "/orders/1cc/$orderId/customer/";

        $cacheKey = "SHIPPING_INFO_10000000000000_"
            . $orderId
            . "_1000_110085_Delhi_in";

        $this->app['cache']->put($cacheKey, [
            "serviceable"  => true,
            "cod"          => true,
            "cod_fee"      => 50,
            "shipping_fee" => 60,
        ], 2400);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;
        $this->runRequestResponseFlow($testData);
    }

    public function testReset1CCOrder()
    {
        self::setUp1CCMerchant();
        $orderId = self::create1CCOrder();
        $url = "/orders/1cc/$orderId/reset/";

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }

    public function testReset1CCOrderWithNon1CCOrder()
    {
        $this->setUp1CCMerchant();
        $order = $this->fixtures->order->create();
        $orderId = $order->getPublicId();
        $url = "/orders/1cc/$orderId/reset/";

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }

    public function testReset1CCOrderWithPaidOrder()
    {
        $this->setUp1CCMerchant();
        $orderId = $this->create1CCOrder();
        $this->fixtures->order->edit($orderId, ['status' => 'paid']);

        $url = "/orders/1cc/$orderId/reset/";

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }

    protected function create1CCOrder($overrideInput=[])
    {
        $this->ba->privateAuth();
        $payload = self::get1CCOrderCreatePayload();
        $order = $this->startOrderMetaFlow($payload, $overrideInput);
        return $order['id'];
    }

    protected  function setUp1CCMerchant()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
    }

    protected  function get1CCOrderCreatePayload()
    {
        return [
            'amount'           => 1000,
            'currency'         => "INR",
            'receipt'          => "rec1",
            'line_items_total' => 1000,
        ];
    }

    protected function startOrderMetaFlow(array $payload, $overrideInput=[])
    {
        $this->ba->privateAuth();

        $payload = array_merge($payload, $overrideInput);

        $request = [
            'content' => $payload,
            'method'  => 'POST',
            'url'     => '/orders',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function getOrderMetaArrayWithTaxInvoice($overrideWith = []): array
    {
        $taxInvoice = [
            'amount'      => 50000,
            'currency'    => 'INR',
            'receipt'     => 'rcptid42',
            'method'      => 'upi',
            'tax_invoice' => [
                'business_gstin' => '123456789012345',
                'gst_amount'     => 3000,
                'supply_type'    => 'intrastate',
                'cess_amount'    => 1000,
                'customer_name'  => 'Gaurav',
                'number'         => '1234',
                'date'           => 1626286666,
            ],
        ];

        return array_merge($taxInvoice, $overrideWith);
    }

    public function testCustomerAdditionalInfoOrderCreate()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT);
        $this->ba->privateAuth();

        $response = $this->startTest();
        $orderId = $response['id'];

        Order\Entity::verifyIdAndSilentlyStripSign($orderId);

        $orderMeta = DB::select('select * from order_meta')[0];

        $this->assertEquals($orderId, $orderMeta->order_id);

        $this->assertNotEmpty($orderMeta->value);

    }

    public function testCustomerAdditionalInfoEmptyOrderCreate()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT);
        $this->ba->privateAuth();

        $this->startTest();

    }

    public function testCustomerAdditionalInfoEmptyValuesOrderCreate()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT);
        $this->ba->privateAuth();

        $this->startTest();

    }

    public function testCustomerAdditionalInfoFeatureNotPresentOrderCreate()
    {
        $this->ba->privateAuth();

        $this->startTest();

    }

    public function testCustomerAdditionalInfoMissingIdOrderCreate()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT);
        $this->ba->privateAuth();

        $this->startTest();

    }

    public function testUpdateCustomerDetailsForPlaced1CCOrder()
    {
        $this->setUp1CCMerchant();
        $orderId = $this->create1CCOrder();
        $this->fixtures->order->edit($orderId, ['status' => 'placed']);
        $this->ba->publicAuth();
        $url = "/orders/1cc/$orderId/customer/";

        $cacheKey = "SHIPPING_INFO_10000000000000_"
                    . $orderId
                    . "_1000_110085_Delhi_in";

        $this->app['cache']->put($cacheKey, [
            "serviceable"  => true,
            "cod"          => true,
            "cod_fee"      => 50,
            "shipping_fee" => 60,
        ], 2400);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;
        $this->runRequestResponseFlow($testData);
    }

    public function testReset1CCOrderWithPlacedOrder()
    {
        $this->setUp1CCMerchant();
        $orderId = $this->create1CCOrder();
        $this->fixtures->order->edit($orderId, ['status' => 'placed']);

        $url = "/orders/1cc/$orderId/reset/";

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $url;

        $this->runRequestResponseFlow($testData);
    }
}
