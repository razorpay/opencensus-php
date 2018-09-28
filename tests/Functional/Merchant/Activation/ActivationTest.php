<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ActivationTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ActivationTestData.php';

        parent::setUp();
    }

    public function testMerchantActivationCategoriesResponseForAdminAuth()
    {
        $this->ba->adminAuth();
        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $this->startTest();
    }

    public function testMerchantActivationCategoriesResponseForNonAdminAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
