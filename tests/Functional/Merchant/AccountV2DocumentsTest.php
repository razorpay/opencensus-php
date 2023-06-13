<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\Document\Metric;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use RZP\Models\Merchant\Document as MerchantDocument;

class AccountV2DocumentsTest extends OAuthTestCase
{
    use TestsMetrics;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AccountsV2DocumentsTestData.php';
        parent::setUp();
    }

    public function testDocumentUploadWrongPurpose()
    {
        $this->setUpPartnerAuthAndGetSubMerchantId(false);

        $this->updateUploadDocumentData(__FUNCTION__);

        $this->startTest();

    }

    public function testDocumentUploadDownload()
    {
        $this->setUpPartnerAuthAndGetSubMerchantId(false);

        $this->updateUploadDocumentData(__FUNCTION__);

        $uploadResponse = $this->startTest();

        $this->assertFalse(empty($uploadResponse), false);

        $file_id = $uploadResponse['id'];

        $testData = $this->testData['testDocumentDownloadSuccess'];

        $testData['request']['url'] = '/v2/documents/' . $file_id;

        $downloadResponse = $this->runRequestResponseFlow($testData);

        $this->assertFalse(empty($downloadResponse));

        $testData = $this->testData['testDocumentDownloadInvalidExpiryUpperLimit'];

        $testData['request']['url'] = '/v2/documents/' . $file_id. '?expiry=1000';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testDocumentDownloadInvalidExpiryLowerLimit'];

        $testData['request']['url'] = '/v2/documents/' . $file_id. '?expiry=-1';

        $this->runRequestResponseFlow($testData);

    }

    public function testDocumentUploadDownloadV1Routes()
    {
        $this->ba->privateAuth();

        $this->updateUploadDocumentData(__FUNCTION__);

        $uploadResponse = $this->startTest();

        $this->assertFalse(empty($uploadResponse), false);

        $file_id = $uploadResponse['id'];

        $testData = $this->testData['testDocumentDownloadSuccess'];

        $testData['request']['url'] = '/documents/' . $file_id;

        $downloadResponse = $this->runRequestResponseFlow($testData);

        $this->assertFalse(empty($downloadResponse));
    }

    public function testValidationsForInvalidInput()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $this->updateUploadDocumentData('testInvalidDocumentTypeForDocumentPost');
        $testData = $this->testData['testInvalidDocumentTypeForDocumentPost'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);
    }

    public function testValidationsForInvalidEntityData()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => Constants::DEFAULT_MERCHANT_ID
        ]);

        $this->updateUploadDocumentData('testStakeholderDoesnotBelongToMerchantDocumentPost');
        $testData                   = $this->testData['testStakeholderDoesnotBelongToMerchantDocumentPost'];
        $testData['request']['url'] = '/v2/accounts/acc_'.$subMerchant->getId() . '/stakeholders/sth_'. $stakeholder->getId() . '/documents';
        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testSendStakeholderDocsForAccount');
        $testData = $this->testData['testSendStakeholderDocsForAccount'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);
    }

    public function testPostAccountDocument()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();
        $this->updateUploadDocumentData(__FUNCTION__);

        $metricMock = $this->createMetricsMock();

        $metricCaptured = false;
        $expectedMetricData = $this->getMetricDataForDocumentUpload('merchant', 'shop_establishment_certificate', $partner);
        $this->mockAndCaptureCountMetric(Metric::DOCUMENT_UPLOAD_V2_SUCCESS_TOTAL, $metricMock, $metricCaptured, $expectedMetricData);

        $testData    = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/documents';

        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);


        $insertedDocument = $this->getDbLastEntity('merchant_document');

        $this->assertEquals('merchant', $insertedDocument['entity_type']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['entity_id']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);

        $metricCaptured = false;
        $expectedMetricData = $this->getMetricDataForDocumentFetch('merchant', $partner);
        $this->mockAndCaptureCountMetric(Metric::DOCUMENT_FETCH_V2_SUCCESS_TOTAL, $metricMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testAccountDocumentFetch'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);
    }

    public function testPostAccountAdditionalDocuments()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();
        $this->updateUploadDocumentData(__FUNCTION__);

        $this->fixtures->merchant_detail->on('live')->edit($subMerchant->getId(), [
            'business_type'        => 7,
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $this->fixtures->merchant_detail->on('test')->edit($subMerchant->getId(), [
            'business_type'        => 7,
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/documents';

        $this->runRequestResponseFlow($testData);

        $insertedDocument = $this->getDbLastEntity('merchant_document');

        $this->assertEquals('merchant', $insertedDocument['entity_type']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['entity_id']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);
    }

    public function testPostStakeholderDocument()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();
        $this->updateUploadDocumentData(__FUNCTION__);

        $testData    = $this->testData[__FUNCTION__];

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $metricMock = $this->createMetricsMock();

        $metricCaptured = false;

        $expectedMetricData = $this->getMetricDataForDocumentUpload('stakeholder', 'aadhar_front', $partner);

        $this->mockAndCaptureCountMetric(Metric::DOCUMENT_UPLOAD_V2_SUCCESS_TOTAL, $metricMock, $metricCaptured, $expectedMetricData);

        $testData['request']['url'] = '/v2/accounts/acc_' . $subMerchant->getId() . '/stakeholders/sth_' . $stakeholder->getId() . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);

        $insertedDocument = $this->getDbEntity('merchant_document');

        $this->assertEquals('stakeholder', $insertedDocument['entity_type']);
        $this->assertEquals($stakeholder->getId(), $insertedDocument['entity_id']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);

        $metricCaptured = false;

        $expectedMetricData = $this->getMetricDataForDocumentFetch('stakeholder', $partner);

        $this->mockAndCaptureCountMetric(Metric::DOCUMENT_FETCH_V2_SUCCESS_TOTAL, $metricMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testStakeholderDocumentFetch'];
        $testData['request']['url'] = '/v2/accounts/acc_' . $subMerchant->getId() . '/stakeholders/sth_' . $stakeholder->getId() . '/documents';

        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

    }

    public function testEveryDocumentMappedToProofType()
    {
        $missingDocuments = [];
        foreach (MerchantDocument\Type::VALID_DOCUMENTS as $document)
        {
            if (array_key_exists($document, MerchantDocument\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING) === false)
            {
                $missingDocuments[] = $document;
            }
        }

        $this->assertEmpty($missingDocuments, 'Every document should be mapped to a proof type');
    }

    public function testAccDocSubmitForNoDocMerchantInNCState()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $attribute = ['activation_status' => 'needs_clarification'];

        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $subMerchant->getId(),
            'entity_type' => 'merchant'
        ]);

        $this->updateUploadDocumentData('testPostAccountDocument');
        $testData = $this->testData['testPostAccountDocument'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/documents';

        $this->runRequestResponseFlow($testData);
    }

    public function testStakeholderDocSubmitForNoDocMerchantInNCState()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $attribute = ['activation_status' => 'needs_clarification'];

        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), $attribute);

        $this->fixtures->create('feature', [
            'name'        => 'no_doc_onboarding',
            'entity_id'   => $subMerchant->getId(),
            'entity_type' => 'merchant'
        ]);

        $this->updateUploadDocumentData('testPostStakeholderDocument');
        $testData = $this->testData['testPostStakeholderDocument'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/stakeholders/sth_' . $stakeholder->getId() . '/documents';

        $this->runRequestResponseFlow($testData);
    }

    public function testUploadCancelledChequeVideo()
    {
        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $this->updateUploadMp4Video(__FUNCTION__);

        $this->fixtures->merchant_detail->on('live')->edit($subMerchant->getId(), [
            'business_type'        => 7,
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $this->fixtures->merchant_detail->on('test')->edit($subMerchant->getId(), [
            'business_type'        => 7,
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/documents';

        $this->runRequestResponseFlow($testData);

        $insertedDocument = $this->getDbLastEntity('merchant_document');

        $this->assertEquals('merchant', $insertedDocument['entity_type']);

        $this->assertEquals($subMerchant->getId(), $insertedDocument['entity_id']);

        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);

        $this->assertEquals($testData['request']['content']['document_type'], $insertedDocument['document_type']);
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return [$subMerchant, $partner];
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Storage/k.png',
            'a.png',
            'image/png',
            null,
            true);
    }

    protected function updateUploadMp4Video(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Batch/files/input.mp4',
            'input.mp4',
            'video/mp4',
            null,
            true);
    }

    private function getMetricDataForDocumentUpload(string $entity, string $documentType, $partner)
    {
        return [
            'entity'        => $entity,
            'document_type' => $documentType
        ];
    }

    private function getMetricDataForDocumentFetch(string $entity, $partner)
    {
        return [
            'entity'     => $entity,
        ];
    }

}
