<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use RZP\Models\Merchant\Document as MerchantDocument;

class AccountV2DocumentsTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
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

    }

    public function testValidationsForInvalidInput()
    {
        $subMerchant = $this->setupPrivateAuthForPartner();

        $testData                   = $this->testData['testInvalidProofTypeDocumentLink'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);

        $testData                   = $this->testData['testInvalidDocumentTypeDocumentLink'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);

    }

    public function testValidationsForInvalidEntityData()
    {
        $subMerchant = $this->setupPrivateAuthForPartner();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => Constants::DEFAULT_MERCHANT_ID
        ]);

        $testData                   = $this->testData['testStakeholderDoesnotBelongToMerchantDocumentLink'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/stakeholders/' . $stakeholder->getId() . '/documents';
        $this->runRequestResponseFlow($testData);

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $subMerchant->getId(),
        ]);

        $testData = $this->testData['testSendStakeholderDocsForAccountLink'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testSendIncorrectDocumentForProofType'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);
    }

    public function testAccountDocumentLink()
    {
        $subMerchant = $this->setupPrivateAuthForPartner();
        $testData    = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';

        $this->runRequestResponseFlow($testData);

        $insertedDocument = $this->getDbLastEntity('merchant_document');

        $this->assertEquals('merchant', $insertedDocument['entity_type']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['entity_id']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);

        $testData = $this->testData['testAccountDocumentFetch'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/documents';
        $this->runRequestResponseFlow($testData);
    }

    public function testStakeholderDocumentLink()
    {
        $subMerchant = $this->setupPrivateAuthForPartner();
        $testData    = $this->testData[__FUNCTION__];

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/stakeholders/' . $stakeholder->getId() . '/documents';

        $this->runRequestResponseFlow($testData);

        $insertedDocument = $this->getDbEntity('merchant_document');

        $this->assertEquals('stakeholder', $insertedDocument['entity_type']);
        $this->assertEquals($stakeholder->getId(), $insertedDocument['entity_id']);
        $this->assertEquals($subMerchant->getId(), $insertedDocument['merchant_id']);

        $testData = $this->testData['testStakeholderDocumentFetch'];
        $testData['request']['url'] = '/v2/accounts/' . $subMerchant->getId() . '/stakeholders/' . $stakeholder->getId() . '/documents';

        $this->runRequestResponseFlow($testData);
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

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return $subMerchant;
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData                             = &$this->testData[$callee];
        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Storage/k.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/k.png'),
            null,
            true);
    }

}
