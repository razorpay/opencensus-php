<?php

namespace Functional\Batch;

use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;

class BulkDeviceToQrMappingUnmappingTest extends TestCase
{
    use BatchTestTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BulkDeviceToQrMappingUnmappingTestData.php';

        parent::setUp();
    }

    public function testBulkQrDeviceMappingValidateFile()
    {
        $this->ba->proxyAuth();
        $entries = $this->getDefaultFileEntriesForQrMapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceMappingMissingData()
    {
        $this->ba->proxyAuth();

        $entries = $this->getFileEntriesWithMissingDataForQrMapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceMappingValidateFileHavingExtraHeaders()
    {
        $this->ba->proxyAuth();
        $entries = $this->getFileEntriesWithExtraFieldsForQrMapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceMappingValidateFileHavingHeadersInDifferentOrder()
    {
        $this->ba->proxyAuth();
        $entries = $this->getFileEntriesForQrMappingWithDifferntOrder();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceUnmappingValidateFile()
    {
        $this->ba->proxyAuth();
        $entries = $this->getDefaultFileEntriesForQrUnmapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceUnmappingMissingData()
    {
        $this->ba->proxyAuth();

        $entries = $this->getFileEntriesWithMissingDataForQrUnmapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceUnmappingValidateFileHavingExtraHeaders()
    {
        $this->ba->proxyAuth();
        $entries = $this->getFileEntriesWithExtraFieldsForQrUnmapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkQrDeviceUnmappingValidateFileHavingHeadersInDifferentOrder()
    {
        $this->ba->proxyAuth();
        $entries = $this->getFileEntriesInDifferentOrderForQrUnmapping();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    // QR Mapping File Entries
    protected function getDefaultFileEntriesForQrMapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_QR_CODE_STRING           => 'abc',
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
            ],
        ];
    }

    protected function getFileEntriesForQrMappingWithDifferntOrder()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_MAPPING_VPA                      => 'abc@okhdfc',
                Batch\Header::DEVICE_TO_QR_MAPPING_QR_CODE_STRING           => 'abc',
                Batch\Header::DEVICE_TO_QR_MAPPING_EPN_MERCHANT_TYPE        => 'TYPE_1',

            ],
        ];
    }

    protected function getFileEntriesWithExtraFieldsForQrMapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_QR_CODE_STRING           => 'abc',
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_MAPPING_VPA                      => 'abc@okhdfc',
            ],
        ];
    }

    protected function getFileEntriesWithMissingDataForQrMapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_QR_CODE_STRING           => 'abc',
                Batch\Header::DEVICE_TO_QR_MAPPING_VPA                      => 'abc@okhdfc',
            ],
        ];
    }


    // Qr Unmapping Entries File
    protected function getDefaultFileEntriesForQrUnmapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_RZP_MID                => 'true',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_FROM_QR                     => 'true'
            ],
        ];
    }

    protected function getFileEntriesWithExtraFieldsForQrUnmapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_RZP_MID                => 'true',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_FROM_QR                     => 'true',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_USER_ID                => 'true'
            ],
        ];
    }

    protected function getFileEntriesWithMissingDataForQrUnmapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_RZP_MID                => 'true',
            ],
        ];
    }

    protected function getFileEntriesInDifferentOrderForQrUnmapping()
    {
        return [
            [
                Batch\Header::DEVICE_TO_QR_MAPPING_DSN                      => '12345678',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_USER_ID                => 'true',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_FROM_QR                     => 'true',
                Batch\Header::DEVICE_TO_QR_UNMAPPING_RZP_MID                => 'true',
            ],
        ];
    }

}
