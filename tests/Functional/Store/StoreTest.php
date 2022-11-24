<?php
namespace RZP\Tests\Functional\Store;

use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Store\Constants;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Store;
use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Models\Settings;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Services\Elfin\Impl\Gimli;

class StoreTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const TEST_STORE_ID    = '100000000000pl';
    const TEST_PPI_ID      = '10000000000ppi';
    const TEST_ORDER_ID    = '10000000000ord';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/StoreTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateStore()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateStoreWithoutTitle()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateStoreWithoutSlug()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateStoreWithDuplicateSlug()
    {
        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')->willThrowException(new Exception\RuntimeException(
            'Unexpected response code received from gimli service.',
            [
                'status_code' => 400,
                'res_body'    => 'Duplicate url',
            ]));

        $this->app->instance('elfin', $elfin);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchStore()
    {
       $this->testCreateStore();

        $this->startTest();
    }

    public function testFetchWithoutStore()
    {
        $this->startTest();
    }

    public function testCreateStoreWithExistingStore()
    {
        $this->testCreateStore();

        $this->startTest();
    }

    public function testEditStore()
    {
        $this->testCreateStore();

        $this->startTest();
    }

    public function testCreateStoreAddProduct()
    {
        $this->testCreateStore();

        $this->testAddProductToStore();
    }

    protected function testAddProductToStore()
    {
        $this->startTest();
    }

    public function testAddProductToStoreWithoutDiscountedPrice()
    {
        $this->testCreateStore();

        $this->startTest();
    }

    public function testAddProductToStoreWithoutSellingPrice()
    {
        $this->testCreateStore();

        $this->startTest();
    }

    public function testAddProductToStoreWithoutStock()
    {
        $this->testCreateStore();

        $this->startTest();
    }

    public function testUpdateProduct()
    {
        $this->testCreateStoreAddProduct();

        $product = $this->getDbLastEntity('payment_page_item');

        $this->testData[__FUNCTION__]['request']['url'] = '/store/products/'.$product->getPublicId();

        $this->startTest();
    }

    public function testPatchProduct()
    {
        $this->testCreateStoreAddProduct();

        $product = $this->getDbLastEntity('payment_page_item');

        $this->testData[__FUNCTION__]['request']['url'] = '/store/products/'.$product->getPublicId();

        $this->startTest();
    }

    public function testFetchInactiveProduct()
    {
        $this->testPatchProduct();

        $product = $this->getDbLastEntity('payment_page_item');

        $this->testData[__FUNCTION__]['request']['url'] = '/store/products/'.$product->getPublicId();

        $this->startTest();
    }

    public function testFetchPublicStoreData()
    {
        $this->testCreateStore();

        $store = $this->getDbLastEntity('store');

        $gimli = $this->createMock(Gimli::class);

        $gimli->method('expandAndGetMetadata')->willReturn([
            'mode' => 'test',
            'id'   => $store->getPublicId()
        ]);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('driver')->willReturn($gimli);

        $this->app->instance('elfin', $elfin);

        $this->testAddProductToStore();

        $this->testAddProductToStore();

        $this->startTest();
    }

    public function testFetchPublicStoreDataWithInactiveProduct()
    {
        $this->testPatchProduct();

        $store = $this->getDbLastEntity('store');

        $gimli = $this->createMock(Gimli::class);

        $gimli->method('expandAndGetMetadata')->willReturn([
            'mode' => 'test',
            'id'   => $store->getPublicId()
        ]);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('driver')->willReturn($gimli);

        $this->app->instance('elfin', $elfin);

        $this->testAddProductToStore();

        $this->startTest();
    }

    public function testCreateOrder()
    {
        $store = $this->createStore();

        $this->addStoreToMerchantSettings();

        $product =  $this->addProductToStore();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMakePaymentAndCheckStoreStatus()
    {
        $store = $this->createStore();

        $product = array($this->addProductToStore());

        $order = $this->createOrderForStore($product);

        $this->ba->publicAuth();

        $this->startTest();

        $this->assertEquals($store->getStatus(), 'active');

    }

    protected function createStore(string $id = self::TEST_STORE_ID, array $attributes = []): Store\Entity
    {
        $attributes[Store\Entity::ID]      = $id;
        $attributes[Store\Entity::USER_ID] = USER::MERCHANT_USER_ID;

        return $this->fixtures->create('store', $attributes);
    }

    protected function addProductToStore(
        string $id = self::TEST_PPI_ID,
        string $storeId = self::TEST_STORE_ID,
        int $stock = 1,
        array $attributes = [])
    {

        $attributes[PaymentPageItem\Entity::ID]              = $id;
        $attributes[PaymentPageItem\Entity::PAYMENT_LINK_ID] = $storeId;
        $attributes[PaymentPageItem\Entity::STOCK]           = $stock;

        $defaultItem = [
            Item\Entity::ID     => $id,
            Item\Entity::TYPE   => Item\Type::PAYMENT_PAGE,
            Item\Entity::NAME   => 'amount',
            Item\Entity::AMOUNT => 1000
        ];

        $defaultItem = array_merge($defaultItem, array_pull($attributes, PaymentPageItem\Entity::ITEM, []));

        $item = $this->fixtures->create('item', $defaultItem);

        $attributes[PaymentPageItem\Entity::ITEM_ID] = $item->getId();

        return $this->fixtures->create('payment_page_item', $attributes);
    }

    protected function addStoreToMerchantSettings()
    {
        $merchant = $this->getDbEntityById('merchant', 10000000000000);

        Settings\Accessor::for($merchant, Settings\Module::PAYMENT_STORE)
            ->upsert([Constants::MERCHANT_SETTING_STORE_KEY => 'store_' . self::TEST_STORE_ID])
            ->save();
    }

    protected function createOrderForStore($paymentPageItems, array $orderAttribute = [])
    {
        $data = [];

        $totalAmount = 0;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentPageItem\Entity::ITEM];
            $amount = empty($item->getAmount()) === true ? 10000 : $item->getAmount();

            $totalAmount += 1 * $amount;
        }

        $orderAttribute = array_merge(
            [
                Order\Entity::AMOUNT => $totalAmount,
                Order\Entity::PAYMENT_CAPTURE => true,
                Order\Entity::ID => self::TEST_ORDER_ID
            ],
            $orderAttribute
        );

        $order = $this->fixtures->create('order', $orderAttribute);

        $data['order'] = $order;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentPageItem\Entity::ITEM];

            $itemForLineItemAttributes = [
                Item\Entity::ID     => UniqueIdEntity::generateUniqueId(),
                Item\Entity::AMOUNT => empty($item->getAmount()) === true ? 10000 : $item->getAmount()
            ];

            $itemForLineItem = $this->fixtures->create('item', $itemForLineItemAttributes);

            $lineItem = $this->fixtures->create('line_item', [
                LineItem\Entity::ID          => $paymentPageItem->getId(),
                LineItem\Entity::ITEM_ID     => $itemForLineItem->getId(),
                LineItem\Entity::REF_TYPE    => 'payment_page_item',
                LineItem\Entity::REF_ID      => $paymentPageItem->getId(),
                LineItem\Entity::ENTITY_ID   => $order->getId(),
                LineItem\Entity::ENTITY_TYPE => 'order',
                LineItem\Entity::AMOUNT      => $itemForLineItem->getAmount(),
            ]);

            $data['line_items'][] = $lineItem->toArrayPublic();
        }

         return $data;
    }
}
