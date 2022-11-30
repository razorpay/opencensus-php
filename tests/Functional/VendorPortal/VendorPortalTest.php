<?php

namespace RZP\Tests\Functional\VendorPortal;

use App;
use Mockery;
use RZP\Models\User\Entity as UserEntity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class VendorPortalTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected $config;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VendorPortalTestData.php';

        parent::setUp();

        $this->fixtures->create('user', [ 'id' => 'VendPortalUser' ]);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => 'VendPortalUser',
            'role'        => 'vendor',
            'product'     => 'banking',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', 'VendPortalUser');

        $this->config = App::getFacadeRoot()['config'];
    }

    public function testListVendorInvoices()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('listVendorInvoices')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('listVendorInvoices');
    }

    public function testGetVendorInvoice()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('getVendorInvoiceById')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getVendorInvoiceById');
    }

    public function testListTdsCategories()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('listTdsCategories')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('listTdsCategories');
    }

    public function testGetInvoiceSignedUrl()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('getInvoiceSignedUrl')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getInvoiceSignedUrl');
    }

    public function testListVendorPortalInvites()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('listVendorPortalInvites')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('listVendorPortalInvites');
    }

    public function testCreate()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('create')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('create');
    }

    public function testUploadInvoice()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('uploadInvoice')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('uploadInvoice');
    }

    public function testGetOcrData()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('getOcrData')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getOcrData');
    }

    public function testVendorFetchUser()
    {
        $this->fixtures->create('merchant',[ 'id' => '1DummyMerchant' ]);

        $this->fixtures->create('user',[ 'id' => 'ExistingUserId', 'email' => 'vendorportal@razorpay.com' ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    return 'control';
                }));

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '1DummyMerchant',
            'user_id'     => 'ExistingUserId',
            'role'        => 'vendor',
            'product'     => 'banking',
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . 'ExistingUserId';

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = 'ExistingUserId';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testVendorEditUser()
    {
        $this->fixtures->create('merchant',[ 'id' => '1DummyMerchant' ]);

        $this->fixtures->create('user',[ 'id' => 'ExistingUserId', 'email' => 'vendorportal@razorpay.com' ]);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '1DummyMerchant',
            'user_id'     => 'ExistingUserId',
            'role'        => 'vendor',
            'product'     => 'banking',
        ]);

        $this->ba->proxyAuth('rzp_test_1DummyMerchant', 'ExistingUserId');

        $this->startTest();
    }

    public function testGetVendorPreferences()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('getVendorPreferences')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getVendorPreferences');
    }

    public function testUpdateVendorPreferences()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPortal\Service');

        $vpMock->shouldReceive('updateVendorPreferences')->andReturn([]);

        $this->app->instance('vendor-portal', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('updateVendorPreferences');
    }
}
