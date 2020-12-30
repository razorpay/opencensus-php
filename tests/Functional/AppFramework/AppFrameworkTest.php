<?php

namespace RZP\Tests\Functional\AppFramework;

use DB;
use Mail;
use Hash;
use Queue;
use Config;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Tests\Functional\Fixtures\Entity\App;

class AppFrameworkTest extends TestCase
{
    use PaymentTrait;

    private $ownerRoleUser;

    private $merchant;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AppFrameworkTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateApp()
    {
        $this->startTest();
    }

    public function testUpdateApp()
    {
        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/app/' . $x_app['id'];

        $this->startTest();
    }

    public function testGetApp()
    {
        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/app/' . $x_app['id'];

        $this->startTest();
    }

    public function testCreateAppMapping()
    {
        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['list'] = [
            $x_app['id'],
        ];

        $this->startTest();
    }

    public function testCreateMerchantTag()
    {
        $this->ba->proxyAuth();

        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->fixtures->create('application_mapping',
            [
                'tag'    => 'ecommerce',
                'app_id' => $x_app['id'],
            ]);

        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['url'] = '/merchant/' . $merchant['id'] . '/tag';

        $this->startTest();
    }

    public function testDeleteTag()
    {
        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->fixtures->create('application_mapping',
            [
                'tag'    => 'ecommerce',
	            'app_id' => $x_app['id'],
            ]);

        $this->startTest();
    }

    public function testCreateAppMerchantMapping()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $x_app = $this->fixtures->create('application',
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['content']['merchant_id'] = $merchant->getId();

        $this->testData[__FUNCTION__]['request']['content']['app_id'] = $x_app['id'];

        $this->startTest();

        $xAppMerchantMapping = $this->getLastEntity('application_merchant_mapping', true);

        $this->assertEquals($merchant->getId(), $xAppMerchantMapping['merchant_id']);

        $this->assertEquals($x_app['id'], $xAppMerchantMapping['app_id']);
    }
}
