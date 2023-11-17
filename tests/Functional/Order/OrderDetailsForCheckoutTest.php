<?php

namespace Functional\Order;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as OrderMetaFields;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class OrderDetailsForCheckoutTest extends TestCase
{
    use EntityActionTrait, RequestResponseFlowTrait;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/OrderDetailsForCheckoutTestData.php';

        parent::setUp();
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

    public function testFetchTPVOrderDetailsForCheckout(): void
    {
        $this->fixtures->merchant->addFeatures([
            FeatureConstants::DEBIT_CARD_VALIDATION,
            FeatureConstants::ONE_CLICK_CHECKOUT,
        ]);

        $orderData = [
            'amount'        => 50000,
            'receipt'       => 'rcptid42',
            'bank_account'  => [
                'account_number'    => '040304030403040',
                'ifsc'              => 'FDRL0003098',
                'name'              => 'ThisIsAwesome',
            ],
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
            'bank' => 'FDRL',
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
            OrderEntity::AMOUNT                              => 50000,
            OrderEntity::RECEIPT                             => 'R1',
            OrderEntity::BANK_ACCOUNT                        => [
                'account_number' => '040304030403040',
                'ifsc'           => 'UTIB0003098',
                'name'           => 'ThisIsAwesome',
            ],
            OrderEntity::METHOD                              => 'netbanking',
            OrderMetaFields::LINE_ITEMS_TOTAL => 50000,
            OrderMetaFields::LINE_ITEMS       => [
                [
                    OrderMetaFields::LINE_ITEM_NAME     => 'Line Item 1',
                    OrderMetaFields::LINE_ITEM_PRICE    => 10000,
                    OrderMetaFields::LINE_ITEM_QUANTITY => 1,
                ],
                [
                    OrderMetaFields::LINE_ITEM_NAME     => 'Line Item 2',
                    OrderMetaFields::LINE_ITEM_PRICE    => 20000,
                    OrderMetaFields::LINE_ITEM_QUANTITY => 2,
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

}
