<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantActivationCategoriesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantActivationCategoriesTestData.php';

        parent::setUp();
    }

    public function testMerchantActivationCategoriesResponse()
    {
        $request = [
            'method'    => 'GET',
            'url'       => '/merchant/activation/business_categories_details'
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($this->testData, $response, 'merchant activation categories response is not as expected');
    }
}
