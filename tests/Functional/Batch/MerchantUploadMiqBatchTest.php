<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Permission\Name as PName;

class MerchantUploadMiqBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MerchantUploadMiqBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testValidateFileEntryMerchantUploadMIQSuccess()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidateFileHeaderMerchantUploadMIQFailed()
    {
        $entries = $this->getDefaultFileEntries();

        // validate missing header field
        // removing header from each entry
        foreach ($entries as & $entry)
        {
            unset($entry[Header::MIQ_BANK_ACC_NUMBER]);
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateBatchMerchantUploadMIQSuccess()
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $permission = $this->fixtures->create('permission', ['name' => PName::MERCHANT_BULK_UPLOAD_MIQ]);

        $roleOfAdmin->permissions()->attach($permission->getId());

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateBatchMerchantUploadMIQSuccess_OnlyDS()
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $permission = $this->fixtures->create('permission', ['name' => PName::MERCHANT_BULK_UPLOAD_MIQ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::ORG_PROGRAM_DS_CHECK,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $roleOfAdmin->permissions()->attach($permission->getId());

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateBatchMerchantUploadMIQFailed_OnlyDS()
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $permission = $this->fixtures->create('permission', ['name' => PName::MERCHANT_BULK_UPLOAD_MIQ]);

        $roleOfAdmin->permissions()->attach($permission->getId());

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $this->assertNotNull($response['error']);
    }

    public function testCreateBatchMerchantUploadMIQInvalidPermission()
    {
        // validate permission for batch create
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateMerchantUploadMIQSuccess()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);
    }

    public function testCreateMerchantUploadMIQFailed()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantWithKYCSuccess()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['response']['content'] =  
            [
                Header::STATUS                          => 'failure',
                Header::ERROR_CODE                      => 'SERVER_ERROR',
                Header::ERROR_DESCRIPTION               => 'Failed to submit activation details',
            ];

        $response = $this->startTest();

        // Creating the merchant even if there is a failure in KYC submission,
        // then the banking operations team will take it manually.
        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('SERVER_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantWithoutWebsiteDetailsSuccess()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = '';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);
    }

    public function testCreateMerchantDynamicFeeBearerSuccess()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_NB_FEE_BEARER] = 'Customer';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
        $this->assertEquals('dynamic', $response[Header::MIQ_OUT_FEE_BEARER]);
        $this->assertEmpty($response[Header::ERROR_CODE]);
        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);
    }

    public function testCreateMerchantWithoutPricingPlan()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_NB_FEE_TYPE] = 'NA';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

    }

    public function testCreateMerchantSuccess_OnlyDS()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::ORG_PROGRAM_DS_CHECK,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
    }
    protected function getDefaultFileEntries(): array
    {
        return [
            [
                Header::MIQ_MERCHANT_NAME                 => 'Test Merchant',
                Header::MIQ_DBA_NAME                      => 'Test Merchant',
                Header::MIQ_WEBSITE                       => 'https://www.vas.com',
                Header::MIQ_WEBSITE_ABOUT_US              => 'https://www.vas.com',
                Header::MIQ_WEBSITE_TERMS_CONDITIONS      => 'https://www.vas.com',
                Header::MIQ_WEBSITE_CONTACT_US            => 'https://www.vas.com',
                Header::MIQ_WEBSITE_PRIVACY_POLICY        => 'https://www.vas.com',
                Header::MIQ_WEBSITE_PRODUCT_PRICING       => 'https://www.vas.com',
                Header::MIQ_WEBSITE_REFUNDS               => 'https://www.vas.com',
                Header::MIQ_WEBSITE_CANCELLATION          => 'https://www.vas.com',
                Header::MIQ_WEBSITE_SHIPPING_DELIVERY     => 'https://www.vas.com',
                Header::MIQ_CONTACT_NAME                  => 'Test Merchant',
                Header::MIQ_CONTACT_EMAIL                 => 'upload.miq@razorpay.com',
                Header::MIQ_TXN_REPORT_EMAIL              => 'upload.miq@razorpay.com',
                Header::MIQ_ADDRESS                       => 'rzp,1st Floor, SJR',
                Header::MIQ_CITY                          => 'Bengaluru',
                Header::MIQ_PIN_CODE                      => '560030',
                Header::MIQ_STATE                         => 'Karnataka',
                Header::MIQ_CONTACT_NUMBER                => '9999999999',
                Header::MIQ_BUSINESS_TYPE                 => 'Trust',
                Header::MIQ_CIN                           => 'U67190TN2014PTC096978',
                Header::MIQ_BUSINESS_PAN                  => 'AARCA5484G',
                Header::MIQ_BUSINESS_NAME                 => 'ABC Ltd',
                Header::MIQ_AUTHORISED_SIGNATORY_PAN      => 'BOVPD4792K',
                Header::MIQ_PAN_OWNER_NAME                => 'ABC',
                Header::MIQ_BUSINESS_CATEGORY             => 'E-Commerce',
                Header::MIQ_SUB_CATEGORY                  => 'Market Place',
                Header::MIQ_GSTIN                         => '27AAAATO288L1Z6',
                Header::MIQ_BUSINESS_DESCRIPTION          => 'Merchant is into apparel business , dealing on ecommerce model.',
                Header::MIQ_ESTD_DATE                     => '12/5/2021',
                Header::MIQ_FEE_MODEL                     => 'Prepaid',
                Header::MIQ_NB_FEE_TYPE                   => 'Flat',
                Header::MIQ_NB_FEE_BEARER                 => 'Platform',
                Header::MIQ_AXIS                          => 10,
                Header::MIQ_HDFC                          => 9,
                Header::MIQ_ICICI                         => 7,
                Header::MIQ_SBI                           => 23,
                Header::MIQ_YES                           => 12,
                Header::MIQ_NB_ANY                        => 2,
                Header::MIQ_DEBIT_CARD_FEE_TYPE           => 'Flat',
                Header::MIQ_DEBIT_CARD_FEE_BEARER         => 'Platform',
                Header::MIQ_DEBIT_CARD_0_2K               => 2,
                Header::MIQ_DEBIT_CARD_2K_1CR             => 5,
                Header::MIQ_RUPAY_FEE_TYPE                => 'Flat',
                Header::MIQ_RUPAY_FEE_BEARER              => 'Platform',
                Header::MIQ_RUPAY_0_2K                    => 3,
                Header::MIQ_RUPAY_2K_1CR                  => 3,
                Header::MIQ_UPI_FEE_TYPE                  => 'Flat',
                Header::MIQ_UPI_FEE_BEARER                => 'Platform',
                Header::MIQ_UPI                           => 23,
                Header::MIQ_WALLETS_FEE_TYPE              => 'Flat',
                Header::MIQ_WALLETS_FEE_BEARER            => 'Platform',
                Header::MIQ_WALLETS_FREECHARGE            => 4,
                Header::MIQ_WALLETS_ANY                   => 2,
                Header::MIQ_CREDIT_CARD_FEE_TYPE          => 'Flat',
                Header::MIQ_CREDIT_CARD_FEE_BEARER        => 'Platform',
                Header::MIQ_CREDIT_CARD_0_2K              => 3,
                Header::MIQ_CREDIT_CARD_2K_1CR            => 2,
                Header::MIQ_INTERNATIONAL                 => 'Yes',
                Header::MIQ_INTL_CARD_FEE_TYPE            => 'Flat',
                Header::MIQ_INTL_CARD_FEE_BEARER          => 'Platform',
                Header::MIQ_INTERNATIONAL_CARD            => 30,
                Header::MIQ_BUSINESS_FEE_TYPE             => 'Flat',
                Header::MIQ_BUSINESS_FEE_BEARER           => 'Platform',
                Header::MIQ_BUSINESS                      => 5,
                Header::MIQ_BANK_ACC_NUMBER               => '921010040934567',
                Header::MIQ_BENEFICIARY_NAME              => 'ABC LTD',
                Header::MIQ_BRANCH_IFSC_CODE              => 'UTIB0004651',
            ],
        ];
    }

    public function testCreateMerchantUploadMIQInvalidAddress()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_ADDRESS] = 'NA';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCity()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CITY] = 'NA';

        $response = $this->startTest();
        
        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidPin()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_PIN_CODE] = 'NA';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidAlphaNumericPin()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_PIN_CODE] = 'rzp123';

        $response = $this->startTest();
        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidState()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_STATE] = 'NA';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidContactNumber()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusinessType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = 'test';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCIN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidPublicCIN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'U67190TN2014PTC09697';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQValidPublicCIN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'U67190TN2014PTC096979';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
    }

    public function testCreateMerchantUploadMIQInvalidLLPIN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
         $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = 'llp';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'ABC123';

        $response = $this->startTest();
        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }


    public function testCreateMerchantUploadMIQInvalidPAN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = 'AARPA5484G';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidUnregisteredPAN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
                $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = 'not_yet_registered';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = 'AARCA5484G';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusinessName()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_NAME] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidSignatoryPAN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidPANOwnerName()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_PAN_OWNER_NAME] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusinessDescription()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_DESCRIPTION] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidESTDDate()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_ESTD_DATE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidFeeModal()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_FEE_MODEL] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_NB_FEE_TYPE] = 'test';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_NB_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBAxis()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AXIS] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBHDFC()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_HDFC] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBICICI()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_ICICI] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBSBI()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_SBI] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBYes()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_YES] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidNBAny()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_NB_ANY] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

     public function testCreateMerchantUploadMIQInvalidDebitCardFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_DEBIT_CARD_FEE_TYPE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

     public function testCreateMerchantUploadMIQInvalidDebitCardFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_DEBIT_CARD_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

     public function testCreateMerchantUploadMIQInvalidDebitCardFee2K()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_DEBIT_CARD_0_2K] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

     public function testCreateMerchantUploadMIQInvalidDebitCardFee1CR()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_DEBIT_CARD_2K_1CR] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidRupayFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_RUPAY_FEE_TYPE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidRupayFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_RUPAY_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidRupay2K()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_RUPAY_0_2K] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidRupay1CR()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_RUPAY_2K_1CR] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidUPIFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_UPI_FEE_TYPE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidUPIFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_UPI_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidUPI()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_UPI] = '';

        $response = $this->startTest();
        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidWalletsFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WALLETS_FEE_TYPE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidWalletsFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WALLETS_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidWalletsFreecharge()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WALLETS_FREECHARGE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidWalletsAny()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WALLETS_ANY] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCreditCardFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CREDIT_CARD_FEE_TYPE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCreditCardFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CREDIT_CARD_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCreditCard2K()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CREDIT_CARD_0_2K] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidCreditCard1CR()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CREDIT_CARD_2K_1CR] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidInternational()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_INTERNATIONAL] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidInternationalFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_INTL_CARD_FEE_BEARER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidInternationalCard()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_INTERNATIONAL_CARD] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusinessFeeType()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_FEE_TYPE] = 'test';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusinessFeeBearer()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_FEE_BEARER] = 'test';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBusiness()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidBankAccountNumber()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BANK_ACC_NUMBER] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }


    public function testCreateMerchantUploadMIQInvalidBenificiaryName()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BENEFICIARY_NAME] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQInvalidIFSC()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BRANCH_IFSC_CODE] = '';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }
}
