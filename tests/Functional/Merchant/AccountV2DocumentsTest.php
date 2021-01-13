<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class AccountV2DocumentsTest extends TestCase
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

        $testData['request']['url'] = '/v2/documents/'. $file_id;

        $downloadResponse = $this->runRequestResponseFlow($testData);

        $this->assertFalse(empty($downloadResponse));

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