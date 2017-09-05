<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantEsFetchTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantEsFetchTestData.php';

        parent::setUp();
    }

    //
    // VERIFY:
    // - Have a trait method to create a hierarchy of merchants, groups, admins
    //   with lots of other details.
    // - Run following tests against a variety of admin ids.
    //

    public function testGetMerchantsFromEsByQ()
    {
        $this->ba->adminAuth('test', '10000000000011');

        // $response = $this->startTest();

        // sd($response);
    }

    public function testGetMerchantsFromEsByAccountStatus()
    {
    }

    public function testGetMerchantsFromEsByQAndAccountStatus()
    {
    }

    public function testGetMerchantsFromEsBySubAccounts()
    {
    }

    public function testGetMerchantsFromEsByQAndSubAccounts()
    {
    }

    public function testGetMerchantsFromEsByAccountStatusAndSubAccounts()
    {
    }
}
