<?php
namespace RZP\Tests\Functional\Store;

use RZP\Exception;
use RZP\Services\Elfin\Impl\Gimli;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class StoreTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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
}
