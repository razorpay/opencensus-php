<?php

namespace RZP\Tests\Functional\Merchant;

use DB;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;


class MerchantDetailTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;

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

    public function testUpdateIfscCode()
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

    public function testSubmitAutoActivate()
    {
        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000000');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $this->fixtures->edit('merchant', $merchantId, ['linked_account_kyc' => 0, 'parent_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testSubmitWithInvalidFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:invalid_fields');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateIfscCodeWithFailure()
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

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    public function testLockMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCommentMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCommentForLockedMerchant()
    {
        $params = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $params);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCommentMerchantWithNoMerchantDetail()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $merchantId = $merchant['id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testUnlockMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testUnlockMerchant2()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCreateMerchantDetailIfNotExist()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->proxyAuth('rzp_test_' .$merchant['id']);

        $this->startTest();
    }

    public function testZohoMerchantHeaders()
    {
        $this->fixtures->merchant->addFeatures(['zoho', 'charge_at_will']);
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->mockTokenex();

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $response['token_id'];

        $data = $this->testData[__FUNCTION__];

        // Second recurring payment fails if attempted without the right headers
        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doS2sRecurringPayment($payment);
        });

        // Second recurring payment succeeds with the header
        $requestServer = [
            'HTTP_X_AGGREGATOR' => \Config::get('applications.zoho.header')
        ];

        $this->doS2sRecurringPayment($payment, $requestServer);
    }

    public function testMerchantDetailsFetch()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000002',
                                                         'email' => 'razorpay@razorpay.com']);

        $this->fixtures->create('merchant:add_payment_banks', ['merchant_id' => '10000000000002']);

        $this->fixtures->merchant->enableInternational('10000000000002');

        $admin = $this->ba->getAdmin();

        $merchant->admins()->attach($admin);

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testGetPreSignupDetails()
    {
        $this->fixtures->create('merchant', ['id'    => '10000000000155',
                                             'email' => 'razorpay@razorpay.com']);
        $merchantDetailData = [
            'merchant_id'        => '10000000000155',
            'business_type'      => 1,
            'transaction_volume' => 5,
            'department'         => 6,
            'contact_mobile'     => 8722627189,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $this->ba->proxyAuth('rzp_live_10000000000155');

        $this->startTest();
    }

    public function testPutPreSignupDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_'.$merchantDetail['merchant_id']);

        $this->startTest();
    }
}
