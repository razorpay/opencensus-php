<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Http\UploadedFile;

use RZP\Models\FileStore;
use RZP\Models\Batch as BatchModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

trait BatchTestTrait
{
    use FileHandlerTrait;
    use PaymentTrait;

    public function createAndPutExcelFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToExcelFile($entries, 'file', 'files/batch');

        $uploadedFile = $this->createUploadedFile($url);

        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    public function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
                        $url,
                        'file.xlsx',
                        $mime,
                        filesize($url),
                        null,
                        true);
    }

    public function assertInputFileExistsForBatch(string $id)
    {
        return $this->assertFileExistsForBatchOfType($id, FileStore\Type::BATCH_INPUT);
    }

    public function assertOutputFileExistsForBatch(string $id)
    {
        return $this->assertFileExistsForBatchOfType($id, FileStore\Type::BATCH_OUTPUT);
    }

    public function assertFileExistsForBatchOfType(string $id, string $type)
    {
        BatchModel\Entity::verifyIdAndSilentlyStripSign($id);

        $file = FileStore\Entity::where(FileStore\Entity::TYPE, $type)
                                ->where(FileStore\Entity::ENTITY_TYPE, 'batch')
                                ->where(FileStore\Entity::ENTITY_ID, $id)
                                ->first();

        $this->assertNotNull($file);

        return $file;
    }
}
