<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantEsCreateTest extends TestCase
{
    use RequestResponseFlowTrait;

    //
    // TODOs:
    // - Test if test and lives are getting updated and shit is fine.
    //

    //
    // Assert with admins, groups, details, tags, is_marketplace, referrer
    // Create and assert that it exists in ES
    // Write a trait to get documents from ES
    //

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantEsCreateTestData.php';

        parent::setUp();
    }

    public function testCreateMerchant()
    {
        $this->ba->appAuthTest();

        $this->startTest();
    }

    public function testUpdateMerchantWithBasicDatapoints()
    {
    }

    public function testUpdateMerchantWithAdmins()
    {
    }

    public function testUpdateMerchantWithGroups()
    {
    }

    public function testUpdateMerchantWithAdminsAndGroups()
    {
    }

    public function testAddMerchantTags()
    {
    }

    public function testRemoveMerchantTag()
    {
    }
}
