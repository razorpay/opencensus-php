<?php

namespace RZP\Tests\Functional\AppFramework;

use DB;
use Mail;
use Hash;
use Queue;
use Config;

use RZP\Constants;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\App;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class AppFrameworkTest extends TestCase
{
    use PaymentTrait;

    private $ownerRoleUser;

    private $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AppFrameworkTestData.php';

        parent::setUp();

        $this->createAndAssignPermission();

        $this->ba->adminAuth();
    }

    public function createAndAssignPermission()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permRegistration = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::APP_REGISTRATION]);

        $permAppMapping = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::APP_MAPPING]);

        $role->permissions()->attach($permRegistration->getId());

        $role->permissions()->attach($permAppMapping->getId());
    }

    public function detachPermission(string $permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permissionId = (new Permission\Repository)->retrieveIdsByNames([$permissionName])[0];

        $role->permissions()->detach($permissionId);
    }

    public function testCreateApp()
    {
        $this->startTest();
    }

    public function testCreateWithoutRegistrationRole()
    {
        $this->detachPermission(Permission\Name::APP_REGISTRATION);

        $this->startTest();
    }

    public function testUpdateApp()
    {
        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
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
        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
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
        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
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

    public function testCreateAppMappingWithoutMappingRole()
    {
        $this->detachPermission(Permission\Name::APP_MAPPING);

        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
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

        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->fixtures->create(Constants\Entity::APPLICATION_MAPPING,
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
        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $this->fixtures->create(Constants\Entity::APPLICATION_MAPPING,
            [
                'tag'    => 'ecommerce',
	            'app_id' => $x_app['id'],
            ]);

        $this->startTest();
    }

    public function testCreateAppMerchantMapping()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $x_app = $this->fixtures->create(Constants\Entity::APPLICATION,
            [
                'name' => 'Test App',
                'title' => 'Test App',
                'type' => 'app',
                'home_app' => true,
                'description' => 'This is test app',
            ]);

        $merchant = $this->fixtures->create(Constants\Entity::MERCHANT);

        $this->testData[__FUNCTION__]['request']['content']['merchant_id'] = $merchant->getId();

        $this->testData[__FUNCTION__]['request']['content']['app_id'] = $x_app['id'];

        $this->startTest();

        $xAppMerchantMapping = $this->getLastEntity(Constants\Entity::APPLICATION_MERCHANT_MAPPING, true);

        $this->assertEquals($merchant->getId(), $xAppMerchantMapping['merchant_id']);

        $this->assertEquals($x_app['id'], $xAppMerchantMapping['app_id']);
    }
}
