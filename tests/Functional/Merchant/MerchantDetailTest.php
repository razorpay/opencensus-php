<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mockery;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;


class MerchantDetailTest extends TestCase
{
    use FileHandlerTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDetailTestData.php';

        parent::setUp();
    }

    public function testUpdateIFSCCode()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateIFSCCodeWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateDetailForLockedMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }
}
