<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;

class ActivationTest extends TestCase
{
    use EntityActionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

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

    public function testPostInstantActivationRequiredField()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->getHoldFunds());

        $this->assertFalse($merchant->merchantDetail->isSubmitted());

        $this->assertEquals($merchant->getWebsite(), 'https://example.com');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');
    }

    public function testPostInstantActivationLinkedAccount()
    {
        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $merchantId = $linkedAccount->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testUpdateActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('greylist', $liveMerchant->merchantdetail->getActivationFlow());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('greylist', $testMerchant->merchantdetail->getActivationFlow());
    }

    public function testUpdateCategoryDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame(6211, $liveMerchant->getCategory());
        $this->assertSame('mutual_funds', $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame(6211, $testMerchant->getCategory());
        $this->assertSame('mutual_funds', $testMerchant->getCategory2());
    }

    /**
     * The instant activation route should not accept the request if the merchant is already activated
     */
    public function testPostInstantActivationByActivatedMerchant()
    {
        $this->fixtures->merchant->activate(self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * for blacklist activation flow
     */
    public function testBlacklistInstantActivation()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'contact_email' => "test@razorpay.com",
        ]);

        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGreylistInstantActivation()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'contact_email' => "test@razorpay.com",
        ]);

        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Blacklist merchant should be able to resubmit L1 activation form (basic activation form)
     */
    public function testL1ResubmissionForBlacklist()
    {
        $merchantDetail = $this->fixtures->create(
            'merchant_detail',
            [MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST,]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('whitelist', $liveMerchant->merchantdetail->getActivationFlow());
    }

    public function testKycSubmissionForInstantlyActivatedMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);
        // Adding the file upload attributes for simplicity of the test
        $otherMerchantDetailAttributes = [
            'address_proof_url'    => '124',
            'business_pan_url'     => '124',
            'business_proof_url'   => '124',
            'promoter_address_url' => '124',
        ];
        $data = array_merge($data, $otherMerchantDetailAttributes);
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $this->startTest();

        $testData = $this->testData['submitKyc'];
        $this->startTest($testData);
    }

    public function testKYCVerificationForInstantlyActivatedMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $data = $this->getKycSubmittedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        // @todo: Lock the form once submitted. Change this to assertTrue then
        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId, 'test');
        $this->assertFalse($merchantDetail->isLocked());

        // under_review to rejected
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'rejected');
        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertFalse($merchant->isLive());
        $this->assertTrue($merchant->getHoldFunds());

        // rejected to under_review
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'under_review');
        $this->startTest($testData);

        // under_review to activated
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');
        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());

        // Changing activation_status to activated should lock the form
        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId, 'test');
        $this->assertTrue($merchantDetail->isLocked());
    }

    /**
     * Asserts that the funds cannot be released if the bank account entity is not specified
     */
    public function testReleaseFundsWithoutBankAccount()
    {
        $merchantId = $this->fixtures->create('merchant')->getId();

        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);
        $this->fixtures->create('merchant_detail', $data);

        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchants/' . $merchantId . '/action';

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $this->startTest();
    }

    protected function changeActivationStatus(& $requestContent, & $responseContent, $newStatus)
    {
        $requestContent['activation_status'] = $newStatus;

        $responseContent['activation_status'] = $newStatus;
    }

    protected function getInstantlyActivatedMerchantData()
    {
        return [
            'activated'    => 1,
            'activated_at' => 1539542931,
            'hold_funds'   => 1,
        ];
    }

    protected function getInstantlyActivatedMerchantDetailData($merchantId)
    {
        return [
            'merchant_id'          => $merchantId,
            'business_category'    => 'ecommerce',
            'business_subcategory' => 'fashion_and_lifestyle',
            'promoter_pan'         => 'ABCDE0000Z',
            'promoter_pan_name'    => 'John Doe',
            'activation_status'    => 'instantly_activated',
            'activation_flow'      => 'whitelist',
        ];
    }

    protected function getKycSubmittedMerchantData()
    {
        return [
            'activated'    => 1,
            'activated_at' => 1539542931,
            'hold_funds'   => 1,
        ];
    }

    protected function getKycSubmittedMerchantDetailData($merchantId)
    {
        return [
            'merchant_id'                 => $merchantId,
            'business_category'           => 'ecommerce',
            'business_subcategory'        => 'fashion_and_lifestyle',
            'promoter_pan'                => 'ABCDE0000Z',
            'promoter_pan_name'           => 'John Doe',
            'activation_status'           => 'instantly_activated',
            'activation_flow'             => 'whitelist',
            'contact_name'                => 'test',
            'contact_mobile'              => '9123456789',
            'business_type'               => '1',
            'business_name'               => 'Acme',
            'business_dba'                => 'Acme',
            'bank_account_name'           => 'test',
            'bank_account_number'         => '123456789012345',
            'bank_branch_ifsc'            => 'ICIC0000001',
            'business_operation_address'  => 'Test address',
            'business_operation_state'    => 'Karnataka',
            'business_operation_city'     => 'Bengaluru',
            'business_operation_pin'      => '560030',
            'business_registered_address' => 'Test address',
            'business_registered_state'   => 'Karnataka',
            'business_registered_city'    => 'Bengaluru',
            'business_registered_pin'     => '560030',
            'submitted'                   => 1,
            'submitted_at'                => 1539543931,
        ];
    }
}
