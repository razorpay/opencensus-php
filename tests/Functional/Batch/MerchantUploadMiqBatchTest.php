<?php

namespace RZP\Tests\Functional\Batch;

use DB;
use Config;
use RZP\Constants\Mode;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Permission\Name as PName;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\Merchant;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Admin\Org;
use RZP\Tests\Traits\MocksSplitz;

class MerchantUploadMiqBatchTest extends TestCase
{
    use BatchTestTrait;
    use HeimdallTrait;
    use MocksSplitz;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MerchantUploadMiqBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    private function mockSplitzExperimentUploadMiq($variant = 'false')
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => $variant,
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->andReturn($output);
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

        $this->mockSplitzExperimentUploadMiq();

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

        $this->mockSplitzExperimentUploadMiq();

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

        $this->mockSplitzExperimentUploadMiq();
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

        $this->mockSplitzExperimentUploadMiq();
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

        $this->mockSplitzExperimentUploadMiq();
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

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
    }

    public function testCreateMerchantSuccessMultiAccountAlreadyExistingUser()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);

        $this->fixtures->create('user', [
            'email' =>  'banking-pod2969@razorpay.com',
            'contact_mobile' => '9565656576'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '9565656576';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
    }

    public function testCreateMerchantSuccessMultiAccountNewUser()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '9565656576';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithEmail()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);

        $this->fixtures->create('user', [
            'email' =>  'banking-pod2969@razorpay.com',
            'contact_mobile' => '9565656222'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '9565656576';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('Email ID already associated with another mobile number', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithEmailWithCaseInsensitive()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);

        $this->fixtures->create('user', [
            'email' =>  'banking-pod2969@razorpay.com',
            'contact_mobile' => '9565656222'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'BANKING-POD2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '9565656576';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('Email ID already associated with another mobile number', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithMobile()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);


        $this->fixtures->create('user', [
            'email' =>  'banking-pod22323@razorpay.com',
            'contact_mobile' => '9565656575'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '9565656575';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertStringContainsString('Mobile number already associated with another email ID', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithMobileNumberWithCountryCodeWithPlusWithSpace()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);


        $this->fixtures->create('user', [
            'email' =>  'banking-pod22323@razorpay.com',
            'contact_mobile' => '9565656575'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '+91 9565656575';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        
        $this->assertStringContainsString('Mobile number already associated with another email ID', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithMobileNumberWithCountryCodeWithoutSpace()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);


        $this->fixtures->create('user', [
            'email' =>  'banking-pod22323@razorpay.com',
            'contact_mobile' => '9565656575'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '+919565656575';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        
        $this->assertStringContainsString('Mobile number already associated with another email ID', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithMobileNumberWithCountryCodeWithSpaceWithoutPlus()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);


        $this->fixtures->create('user', [
            'email' =>  'banking-pod22323@razorpay.com',
            'contact_mobile' => '9565656575'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '91 9565656575';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        
        $this->assertStringContainsString('Mobile number already associated with another email ID', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantFailureMultiAccountAlreadyExistingUserWithMobileNumberWithCountryCodeWithoutPlusWithoutSpace()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_ORG_IDENTIFIER],'100000razorpay');

        $perm = $this->fixtures->create('permission', ['name' => 'custom_invite_merchant_flow']);

        $permissionMapData = [
            'permission_id'   => $perm->getId(),
            'entity_id'       => '100000razorpay',
            'entity_type'     => 'org',
            'enable_workflow' => false
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);


        $this->fixtures->create('user', [
            'email' =>  'banking-pod22323@razorpay.com',
            'contact_mobile' => '9565656575'
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_EMAIL] = 'banking-pod2969@razorpay.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CONTACT_NUMBER] = '919565656575';
        $this->testData[__FUNCTION__]['response']['content']= [];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        
        $this->assertStringContainsString('Mobile number already associated with another email ID', $response[Header::ERROR_DESCRIPTION]);
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
                Header::FIELD1                            => '',
                Header::FIELD2                            => '',
                Header::FIELD3                            => '',
                Header::FIELD4                            => '',
                Header::FIELD5                            => '',
                Header::FIELD6                            => '',
                Header::FIELD7                            => '',
                Header::FIELD8                            => '',
                Header::FIELD9                            => '',
                Header::FIELD10                           => '',
                Header::FIELD11                           => '',
                Header::FIELD12                           => '',
                Header::FIELD13                           => '',
                Header::FIELD14                           => '',
                Header::FIELD15                           => ''
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

        $this->mockSplitzExperimentUploadMiq();
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

    public function testCreateMerchantUploadMIQSuccessMIQCIN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = 'llp';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'ABC-1234';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantUploadMIQSuccessWithNonRZPWebsiteUrl()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_ABOUT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_TERMS_CONDITIONS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CONTACT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRIVACY_POLICY] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRODUCT_PRICING] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_REFUNDS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CANCELLATION] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_SHIPPING_DELIVERY] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_GSTIN] = '27AARCA5484G2ZP';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = 'AARCA5484G';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'U74999MH2013pLc247916';

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
    }


    public function testCreateMerchantUploadMIQFailureAuthorisedSignatoryPAN()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_ABOUT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_TERMS_CONDITIONS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CONTACT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRIVACY_POLICY] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRODUCT_PRICING] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_REFUNDS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CANCELLATION] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_SHIPPING_DELIVERY] = 'https://amazon.com';

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] ='BOVKD4792K';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

        $this->assertEquals('Invalid Authorised Signatory PAN : BOVKD4792K', $response[Header::ERROR_DESCRIPTION]);

    }

    public function testCreateMerchantUploadMIQFailureBusinessPan($entry = null)
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_ABOUT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_TERMS_CONDITIONS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CONTACT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRIVACY_POLICY] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRODUCT_PRICING] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_REFUNDS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CANCELLATION] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_SHIPPING_DELIVERY] = 'https://amazon.com';

        if($entry !== null)
        {
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = $entry[Header::MIQ_BUSINESS_TYPE];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = $entry[Header::MIQ_BUSINESS_PAN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_GSTIN] = $entry[Header::MIQ_GSTIN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = $entry[Header::MIQ_CIN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] = $entry[Header::MIQ_AUTHORISED_SIGNATORY_PAN];

            if($entry[Header::MIQ_BUSINESS_TYPE] === Detail\BusinessType::LLP)
            {
                $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'ABC-1234';
            }
        }
        else
        {
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN]= 'AARAA5484G';
        }

        $BusinessType = $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

        $this->assertEquals('The '.Header::MIQ_BUSINESS_PAN. ' is invalid for '.$BusinessType, $response[Header::ERROR_DESCRIPTION]);

    }

    public function testCreateMerchantUploadMIQSuccessBusinessPan($entry = null)
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->mockSplitzExperimentUploadMiq();
        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_ABOUT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_TERMS_CONDITIONS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CONTACT_US] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRIVACY_POLICY] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRODUCT_PRICING] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_REFUNDS] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CANCELLATION] = 'https://amazon.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_SHIPPING_DELIVERY] = 'https://amazon.com';

        if($entry !== null)
        {
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = $entry[Header::MIQ_BUSINESS_TYPE];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = $entry[Header::MIQ_BUSINESS_PAN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_GSTIN] = $entry[Header::MIQ_GSTIN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = $entry[Header::MIQ_CIN];
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] = $entry[Header::MIQ_AUTHORISED_SIGNATORY_PAN];

            if($entry[Header::MIQ_BUSINESS_TYPE] === Detail\BusinessType::LLP)
            {
                $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'ABC-1234';
            }
        }
        else
        {
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = Detail\BusinessType::PUBLIC_LIMITED;
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = 'AAICM8084F';
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_GSTIN] = '27AAICM8084F2ZP';
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'U74999MH2013pLc247916';
            $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] = 'AAAPD0367L';
        }

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForPartnership()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PARTNERSHIP,
            Header::MIQ_BUSINESS_PAN => 'AAICM8084F',
            Header::MIQ_GSTIN => '27AAICM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForPartnership()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PARTNERSHIP,
            Header::MIQ_BUSINESS_PAN => 'AAAFD0367L',
            Header::MIQ_GSTIN => '27AAAFD0367L2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForLLP()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::LLP,
            Header::MIQ_BUSINESS_PAN => 'AAYCA3144L',
            Header::MIQ_GSTIN => '27AAYCA3144L2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForLLP()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::LLP,
            Header::MIQ_BUSINESS_PAN => 'AAYFA3144L',
            Header::MIQ_GSTIN => '27AAYFA3144L2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForPrivateLimited()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PRIVATE_LIMITED,
            Header::MIQ_BUSINESS_PAN => 'AAIAM8084F',
            Header::MIQ_GSTIN => '27AAIAM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForPrivateLimited()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PRIVATE_LIMITED,
            Header::MIQ_BUSINESS_PAN => 'AAICM8084F',
            Header::MIQ_GSTIN => '27AAICM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForNGO()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::NGO,
            Header::MIQ_BUSINESS_PAN => 'AAILM8084F',
            Header::MIQ_GSTIN => '27AAILM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForNGO()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::NGO,
            Header::MIQ_BUSINESS_PAN => 'AAIBM8084F',
            Header::MIQ_GSTIN => '27AAIBM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForSociety()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::SOCIETY,
            Header::MIQ_BUSINESS_PAN => 'AAIJM8084F',
            Header::MIQ_GSTIN => '27AAIJM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForSociety()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::SOCIETY,
            Header::MIQ_BUSINESS_PAN => 'AAILM8084F',
            Header::MIQ_GSTIN => '27AAILM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQFailureBusinessPanForPublicLimited()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PUBLIC_LIMITED,
            Header::MIQ_BUSINESS_PAN => 'AAIHM8084F',
            Header::MIQ_GSTIN => '27AAIHM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQFailureBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForPublicLimited()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PUBLIC_LIMITED,
            Header::MIQ_BUSINESS_PAN => 'AAICM8084F',
            Header::MIQ_GSTIN => '27AAICM8084F2ZP',
            Header::MIQ_CIN => 'U74999MH2013pLc247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }

    public function testCreateMerchantUploadMIQSuccessBusinessPanForProprietorship()
    {
        $dataInput = [
            Header::MIQ_BUSINESS_TYPE => Detail\BusinessType::PROPRIETORSHIP,
            Header::MIQ_BUSINESS_PAN => '',
            Header::MIQ_GSTIN => '27AAAPD0367L2ZP',
            Header::MIQ_CIN => 'U74999MH2013PTC247916',
            Header::MIQ_AUTHORISED_SIGNATORY_PAN => 'AAAPD0367L'
        ];

        $this->testCreateMerchantUploadMIQSuccessBusinessPan($dataInput);
    }
    public function testCreateMerchantUploadMIQFailureWithRZPWebsiteUrl()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

        $this->assertEquals('Invalid Website : https://razorpay.com', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantUploadMIQFailureWithDummyWebsiteUrl()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->fixtures->org->addFeatures([Feature::VAS_KYC_RBI],'100000razorpay');

        $this->mockSplitzExperimentUploadMiq();

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_ABOUT_US] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_TERMS_CONDITIONS] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CONTACT_US] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRIVACY_POLICY] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_PRODUCT_PRICING] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_REFUNDS] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_CANCELLATION] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_WEBSITE_SHIPPING_DELIVERY] = 'https://www.fictionalwebsite.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = Detail\BusinessType::PRIVATE_LIMITED;
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_PAN] = 'AAICM8084F';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_GSTIN] = '27AAICM8084F2ZP';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_CIN] = 'U74999MH2013PTC247916';
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_AUTHORISED_SIGNATORY_PAN] = 'AAAPD0367L';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

        $this->assertEquals('The Website is not a valid URL.', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantUploadMIQFailureBusinessTypeIndividual()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];

        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_BUSINESS_TYPE] = 'individual';

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

        $this->assertEquals('Invalid Business Type', $response[Header::ERROR_DESCRIPTION]);
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

    public function testCreateMerchantUploadMIQRearchFlowOnboardingViaPGOSSuccess()
    {
        Config::set('pgos.proxy.request.mock', true);

        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->andReturn($output);

        $input = $this->testData['defaultSuccess']['request']['content'];

        $response = (new Detail\Upload\Core)->processMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

    }

    public function testCreateMerchantUploadMIQRearchFlowOnboardingViaAPISuccess()
    {
        Config::set('pgos.proxy.request.mock', true);

        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'off',
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->andReturn($output);

        $input = $this->testData['defaultSuccess']['request']['content'];
        $response = (new Detail\Upload\Core)->processMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

    }

    public function testCreateMerchantUploadMIQRearchFlowOnboardingViaAPIValidationFailure()
    {
        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'off',
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->andReturn($output);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_ADDRESS] = null;

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);

    }

    public function testCreateMerchantUploadMIQRearchFlowOnboardingViaPGOSValidationFailure()
    {
        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->andReturn($output);

        $this->testData[__FUNCTION__] = $this->testData['defaultFailure'];
        $this->testData[__FUNCTION__]['request']['content'][Header::MIQ_ADDRESS] = null;

        $response = $this->startTest();

        $this->assertEquals('failure', $response[Header::STATUS]);
        $this->assertEquals('BAD_REQUEST_ERROR', $response[Header::ERROR_CODE]);
    }

    public function testCreateMerchantUploadMIQBvsKYCValidationsSuccess()
    {
        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->mockRazorxTreatment();

        $input = $this->testData['defaultSuccess']['request']['content'];
        $response = (new Detail\Upload\Core)->processMerchantEntry($input);
        $mid = $response['Merchant_id'];
        $merchantDetailsData['merchant_id'] = $mid;

        (new Merchant\MerchantTest)->mockRazorX('testCreateBvsValidationPoi',
            'bvs_auto_kyc',
            'on',
            $mid);

        $bvsMockResponse = $this->triggerMockBvsVerification('testCreateBvsValidationPoi', $merchantDetailsData);
        $bvsResponse = (new Detail\Core)->getBVSResponseforKYCValidations($mid);
        $response['Error Code'] = $bvsResponse[0];
        $response['Error Description'] = $bvsResponse[1];

        $this->assertEmpty($bvsResponse[0]);
        $this->assertEquals('', $response[Header::ERROR_CODE]);
        $this->assertEquals('', $response[Header::ERROR_DESCRIPTION]);
    }

    public function testCreateMerchantUploadMIQBvsKYCValidationsFailure()
    {
        $this->ba->appAuth();

        $this->app->instance("rzp.mode", Mode::TEST);

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Feature::KYC_VERIFICATION_FOR_VAS,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->mockRazorxTreatment();

        $input = $this->testData['defaultSuccess']['request']['content'];
        $response = (new Detail\Upload\Core)->processMerchantEntry($input);
        $mid = $response['Merchant_id'];
        $merchantDetailsData['merchant_id'] = $mid;

        (new Merchant\MerchantTest)->mockRazorX('testCreateBvsValidationPoi',
            'bvs_auto_kyc',
            'on',
            $mid);

        $bvsMockResponse = $this->triggerMockBvsVerification('testCreateBvsValidationPoi', $merchantDetailsData, true, 'failed');
        $bvsResponse = (new Detail\Core)->getBVSResponseforKYCValidations($mid);
        $response['Error Code'] = $bvsResponse[0];
        $response['Error Description'] = $bvsResponse[1];


        $this->assertEquals(',personal_pan:NO_PROVIDER_ERROR', $response[Header::ERROR_CODE]);
        $this->assertEquals(',personal_pan:Karza gateway timed out', $response[Header::ERROR_DESCRIPTION]);
    }

    protected function triggerMockBvsVerification(string $test,
                                               array $merchantDetailsData,
                                               bool $bvsMock = true,
                                               string $responseSuccess = 'success')
    {
        $mid = $merchantDetailsData['merchant_id'];
        if ($responseSuccess === 'success') {
            $bvsFixture = $this->fixtures->create('bvs_validation',
                [
                    'owner_id'      => $mid,
                    'artefact_type' => Constant::PERSONAL_PAN,
                    'owner_type'    => 'merchant',
                    'platform'      => 'pg',
                    'validation_status' => $responseSuccess,
                ]);
        } else {
            $bvsFixture = $this->fixtures->create('bvs_validation',
                [
                    'owner_id'      => $mid,
                    'artefact_type' => Constant::PERSONAL_PAN,
                    'owner_type'    => 'merchant',
                    'platform'      => 'pg',
                    'validation_status' => $responseSuccess,
                    'error_code' => 'NO_PROVIDER_ERROR',
                    'error_description' => 'Karza gateway timed out',
                ]);
        }

        $this->ba->proxyAuth();

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', $bvsMock);
        Config::set('services.bvs.response', $responseSuccess);

        $testData = &$this->testData[$test];

        $this->startTest($testData);

        return $this->getDbEntity('bvs_validation', ['owner_id' => $mid, 'owner_type' => 'merchant']);
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === RazorxTreatment::PERFORM_KYC_VALIDATIONS_VASMERCHANTS)
                    {
                        return 'on';
                    }
                    return "default";
                })
            );
    }

    public function testCreateMerchantUploadMiqAdditionalFieldsSuccess()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => Org\Entity::AXIS_ORG_ID,
            'entity_type'   => 'org',
        ]);

        $org = $this->fixtures->create('org', [
            'id' => Org\Entity::AXIS_ORG_ID
        ]);

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($org->getId());
        $org1 = (new Org\Service())->edit('org_' . Org\Entity::AXIS_ORG_ID, ['default_pricing_plan_id' => '1hDYlICxbxOCYx', 'merchant_session_timeout_in_seconds' => 600,]);

        $this->addAssignablePermissionsToOrg($org);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::ORG_ID] = 'org_' . Org\Entity::AXIS_ORG_ID;
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD1] = 'testName';
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD2] = 9999999;
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD3] = 'test@email.com';

        $response = $this->startTest();

        $merchantDetails = (new Detail\Repository())->getByMerchantId($response[Header::MIQ_MERCHANT_ID]);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

        $this->assertNotEmpty($businessDetailMetadata['org_defined_merchant_fields']);
    }

    public function testCreateMerchantUploadMiqAdditionalFieldsFailure()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name' => Feature::SKIP_KYC_VERIFICATION,
            'entity_id' => Org\Entity::AXIS_ORG_ID,
            'entity_type' => 'org',
        ]);

        $org = $this->fixtures->create('org', [
            'id' => Org\Entity::AXIS_ORG_ID
        ]);

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($org->getId());
        $org1 = (new Org\Service())->edit('org_' . Org\Entity::AXIS_ORG_ID, ['default_pricing_plan_id' => '1hDYlICxbxOCYx', 'merchant_session_timeout_in_seconds' => 600,]);

        $this->addAssignablePermissionsToOrg($org);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::ORG_ID] = 'org_' . Org\Entity::AXIS_ORG_ID;
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD1] = 'testName';
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD2] = '9999999a';
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD3] = 'test-email.com';
        $this->testData[__FUNCTION__]['request']['content'][Header::FIELD14] = 'random text';

        $response = $this->startTest();

        $merchantDetails = (new Detail\Repository())->getByMerchantId($response[Header::MIQ_MERCHANT_ID]);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertNotEmpty($response[Header::ERROR_CODE]);

        $this->assertNotEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertEmpty($businessDetailMetadata['org_defined_merchant_fields']);
    }
}

