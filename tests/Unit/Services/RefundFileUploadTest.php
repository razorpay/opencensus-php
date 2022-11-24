<?php

namespace Unit\Services;

use RZP\Tests\Functional\TestCase;
use RZP\Services\Mock\UfhService as MockUfhService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RefundFileUploadTest extends TestCase
{
    protected $gateway         = 'upi_sbi';

    protected $storageFilePath = 'sbi/sbi_upi_refund/outgoing';

    public function testUploadFile()
    {
        $file = new UploadedFile(__DIR__ . '/RefundTestFile.csv', 'RefundTestFile.csv', 'csv', null, true);

        $fileName        = $file->getClientOriginalName();
        $storageFileName = $this->storageFilePath . '/' . $fileName;

        $this->uploadFileToUfh($file, $storageFileName, 'sbi_upi_refund');
    }

    protected function uploadFileToUfh(UploadedFile $file, string $storageFileName, string $type)
    {
        $isUfhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if ($isUfhServiceMock === true)
        {
            $ufhService = new MockUfhService($this->app, null);
        }
        $response = $ufhService->uploadFileAndGetResponse($file,
                                                          $storageFileName,
                                                          $type,
                                                          null,
                                                          $metadata = []);
        // Asserts file_id in response
        $this->assertNotEmpty($response['id'],'File id not found');
    }
}
