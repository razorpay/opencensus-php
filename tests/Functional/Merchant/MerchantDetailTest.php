<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantDetailTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDetailTestData.php';

        parent::setUp();
    }

    public function testGetMerchantDetails()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->proxyAuth('rzp_test_' .$merchant['id']);

        $this->startTest();
    }

    public function testUpdateIFSCCode()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testSubmit()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testSubmitWithInvalidFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:invalid_fields');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateIFSCCodeWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmail()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmailWithFailure()
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

    public function testLockMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCommentMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCommentForLockedMerchant()
    {
        $params = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $params);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCommentMerchantWithNoMerchantDetail()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $merchantId = $merchant['id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testUnlockMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testUnlockMerchant2()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantDetailIfNotExist()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->proxyAuth('rzp_test_' .$merchant['id']);

        $this->startTest();
    }
}
