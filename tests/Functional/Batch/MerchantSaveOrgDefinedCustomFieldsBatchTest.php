<?php

namespace RZP\Tests\Functional\Batch;

use Config;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class MerchantSaveOrgDefinedCustomFieldsBatchTest extends TestCase
{
    use BatchTestTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->adminAuth();
    }

    protected function getDefaultFileEntries(): array
    {
        return
            [
                Header::ORG_ID                            => 'org_100000razorpay',
                Header::MERCHANT_ID                       => '',
                Header::FIELD1                            => '',
                Header::FIELD2                            => '',
                Header::FIELD3                            => '',
                Header::FIELD4                            => '',
                Header::FIELD5                            => '',
                Header::FIELD6                            => '',
                Header::FIELD7                            => '',
                Header::FIELD8                            => '',
                Header::FIELD9                            => '',
                Header::FIELD10                            => '',
                Header::FIELD11                            => '',
                Header::FIELD12                            => '',
                Header::FIELD13                            => '',
                Header::FIELD14                            => '',
                Header::FIELD15                            => '',
            ];
    }

    protected function getSuccessFieldEntries() : array
    {
        return
            [
                Header::ORG_ID                            => 'org_100000razorpay',
                Header::MERCHANT_ID                       => '',
                Header::FIELD1                            => 'testName',
                Header::FIELD2                            => 999999,
                Header::FIELD3                            => 'test@email.com',
                Header::FIELD4                            => '',
                Header::FIELD5                            => '',
                Header::FIELD6                            => '',
                Header::FIELD7                            => '',
                Header::FIELD8                            => '',
                Header::FIELD9                            => '',
                Header::FIELD10                            => '',
                Header::FIELD11                            => '',
                Header::FIELD12                            => '',
                Header::FIELD13                            => '',
                Header::FIELD14                            => '',
                Header::FIELD15                            => ''
            ];
    }

    protected function getSuccessFieldEntriesForEasyPayOrg() : array
    {
        return
            [
                Header::ORG_ID                            => 'org_100000razorpay',
                Header::MERCHANT_ID                       => '',
                Header::FIELD1                            => 'testName',
                Header::FIELD2                            => 'RM name',
                Header::FIELD3                            => 'RM data',
                Header::FIELD4                            => '',
                Header::FIELD5                            => '',
                Header::FIELD6                            => '',
                Header::FIELD7                            => '',
                Header::FIELD8                            => '',
                Header::FIELD9                            => '',
                Header::FIELD10                            => '',
                Header::FIELD11                            => '',
                Header::FIELD12                            => '',
                Header::FIELD13                            => '',
                Header::FIELD14                            => '',
                Header::FIELD15                            => ''
            ];
    }

    protected function getFailureFieldEntries() : array
    {
        return
            [
                Header::ORG_ID                            => 'org_100000razorpay',
                Header::MERCHANT_ID                       => '',
                Header::FIELD1                            => 'testName',
                Header::FIELD2                            => '999999a',
                Header::FIELD3                            => 'test-email.com',
                Header::FIELD4                            => '',
                Header::FIELD5                            => '',
                Header::FIELD6                            => '',
                Header::FIELD7                            => '',
                Header::FIELD8                            => '',
                Header::FIELD9                            => '',
                Header::FIELD10                            => '',
                Header::FIELD11                            => '',
                Header::FIELD12                            => '',
                Header::FIELD13                            => '',
                Header::FIELD14                            => 'random',
                Header::FIELD15                            => ''
            ];
    }

    public function testMerchantSaveOrgDefinedCustomFieldsDefaultSuccess()
    {
        $this->ba->appAuth();

        $mid = random_alphanum_string(14);
        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->create('merchant',
            [
                'id'                    => $mid,
                'email'                 => 'test@razorpay.com',
                'billing_label'         => 'Test Merchant',
                'activated_at'          => time(),
                'category'              => '5399',
                'product_international' => '1111000000',
                'org_id'                => OrgEntity::AXIS_ORG_ID
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $mid,
            'contact_mobile'    => '1234567124',
        ]);

        $testData = $this->getDefaultFileEntries();
        $testData[Header::MERCHANT_ID]  = $mid;

        $response = (new Detail\Upload\Core)->processAdditionalMerchantFieldsEntry($testData);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
    }

    public function testMerchantSaveOrgDefinedCustomFieldsSuccess()
    {
        $this->ba->appAuth();

        $mid = random_alphanum_string(14);

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->create('merchant',
            [
                'id'                    => $mid,
                'email'                 => 'test@razorpay.com',
                'billing_label'         => 'Test Merchant',
                'activated_at'          => time(),
                'category'              => '5399',
                'product_international' => '1111000000',
                'org_id'                => OrgEntity::AXIS_ORG_ID
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $mid,
            'contact_mobile'    => '1234567124',
        ]);

        $testData = $this->getSuccessFieldEntries();
        $testData[Header::MERCHANT_ID]  = $mid;

        $response = (new Detail\Upload\Core)->processAdditionalMerchantFieldsEntry($testData);

        $merchantDetails = (new Detail\Repository())->getByMerchantId($mid);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($businessDetailMetadata['org_defined_merchant_fields']);
    }

    public function testMerchantSaveOrgDefinedCustomFieldsFailure()
    {
        $this->ba->appAuth();

        $mid = random_alphanum_string(14);

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->create('merchant',
            [
                'id'                    => $mid,
                'email'                 => 'test@razorpay.com',
                'billing_label'         => 'Test Merchant',
                'activated_at'          => time(),
                'category'              => '5399',
                'product_international' => '1111000000',
                'org_id'                => OrgEntity::AXIS_ORG_ID
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $mid,
            'contact_mobile'    => '1234567124',
        ]);

        $testData = $this->getFailureFieldEntries();
        $testData[Header::MERCHANT_ID]  = $mid;

        $response = (new Detail\Upload\Core)->processAdditionalMerchantFieldsEntry($testData);

        $merchantDetails = (new Detail\Repository())->getByMerchantId($mid);
        $businessDetailMetadata = $merchantDetails->businessDetail;

        $this->assertEquals('failed', $response[Header::STATUS]);

        $this->assertNotEmpty($response[Header::ERROR_CODE]);

        $this->assertNotEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertEmpty($businessDetailMetadata);
    }

    public function testMerchantSaveOrgDefinedCustomFieldsDefaultSuccessForForEasyPayOrg()
    {
        $this->ba->appAuth();

        $mid = random_alphanum_string(14);
        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_EASYPAY_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->create('merchant',
            [
                'id'                    => $mid,
                'email'                 => 'test@razorpay.com',
                'billing_label'         => 'Test Merchant',
                'activated_at'          => time(),
                'category'              => '5399',
                'product_international' => '1111000000',
                'org_id'                => OrgEntity::AXIS_EASYPAY_ORG_ID
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $mid,
            'contact_mobile'    => '1234567124',
        ]);

        $testData = $this->getDefaultFileEntries();
        $testData[Header::MERCHANT_ID]  = $mid;

        $response = (new Detail\Upload\Core)->processAdditionalMerchantFieldsEntry($testData);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);
    }
    public function testMerchantSaveOrgDefinedCustomFieldsSuccessForForEasyPayOrg()
    {
        $this->ba->appAuth();

        $mid = random_alphanum_string(14);

        $org = $this->fixtures->create('org', [
            'id' => OrgEntity::AXIS_EASYPAY_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->create('merchant',
            [
                'id'                    => $mid,
                'email'                 => 'test@razorpay.com',
                'billing_label'         => 'Test Merchant',
                'activated_at'          => time(),
                'category'              => '5399',
                'product_international' => '1111000000',
                'org_id'                => OrgEntity::AXIS_EASYPAY_ORG_ID
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $mid,
            'contact_mobile'    => '1234567124',
        ]);

        $testData = $this->getSuccessFieldEntriesForEasyPayOrg();
        $testData[Header::MERCHANT_ID]  = $mid;

        $response = (new Detail\Upload\Core)->processAdditionalMerchantFieldsEntry($testData);

        $merchantDetails = (new Detail\Repository())->getByMerchantId($mid);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($businessDetailMetadata['org_defined_merchant_fields']);
    }
}
