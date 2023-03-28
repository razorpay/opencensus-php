<?php

namespace RZP\Tests\Functional\Batch;


use RZP\Tests\Functional\TestCase;
use RZP\Models\FileStore;
use RZP\Models\Admin\Permission\Name;


class IrctcRefundTest extends TestCase{

    use BatchTestTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/IrctcRefundTestData.php';

        parent::setUp();

    }

    public function testUploadIrctcWithAdminAuthPermissionRefundFile()
    {
        $entries = $this->getValidIrctcFileEntries();

        $this->createAndPutTxtFileInRequest('irctc.txt',$entries, __FUNCTION__);

        $this->ba->adminAuthWithPermission(Name::MERCHANT_BATCH_UPLOAD);

        $this->startTest();

    }
    public function testUploadIrctcWithAdminAuthRefundFile()
    {

        $entries = $this->getValidIrctcFileEntries();

        $this->createAndPutTxtFileInRequest('irctc.txt',$entries,__FUNCTION__);

        $this->expectExceptionMessage('Access Denied');

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testIrctcRefundCreationProcessWithValidFile(){

        $entries = $this->getValidIrctcFileEntries();

        $this->createAndPutTxtFileInRequest('irctc.txt',$entries, __FUNCTION__);

        $this->ba->adminAuthWithPermission(Name::MERCHANT_BATCH_UPLOAD);
        $response = $this->startTest();

        $entity = $this->getDbLastEntity('batch');


        $this->assertEquals(2, $entity['success_count']);

        $this->assertEquals(0, $entity['failure_count']);


        $this->assertInputFileExistsForBatch($response['irctc_refund']);

        $this->assertOutputFileExistsForBatch($response['irctc_refund']);



        $file = FileStore\Entity::where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
            ->first();

        $this->assertNotNull($file);


    }


    public function testIrctcRefundCreationProcessWithInValidFile(){

        $entries = $this->getInValidIrctcFileEntries();

        $this->createAndPutTxtFileInRequest('irctc.txt',$entries, __FUNCTION__);

        $this->ba->adminAuthWithPermission(Name::MERCHANT_BATCH_UPLOAD);
        $response = $this->startTest();

        $entity = $this->getDbLastEntity('batch');


        $this->assertEquals(0, $entity['success_count']);

        $this->assertEquals(2, $entity['failure_count']);


        $this->assertInputFileExistsForBatch($response['irctc_refund']);

        $this->assertOutputFileExistsForBatch($response['irctc_refund']);



        $file = FileStore\Entity::where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
            ->first();

        $this->assertNotNull($file);


    }

    protected function getValidIrctcFileEntries(): string
    {
        $payment2 = $this->doAuthAndCapturePayment();

        $data = [

            "100013812439349|C|1.00|".$payment2['id']."|20221203|100.00|100000713916801",
            "100013812439332|C|1.00|".$payment2['id']."|20221204|100.00|100000713921213",

        ];

        $txt = "";

        foreach ($data as $row)
        {
            $txt = $txt . $row . PHP_EOL;
        }

        return $txt;
    }

    protected function getInValidIrctcFileEntries()
    {
        $payment2 = $this->doAuthAndCapturePayment();

        $data = [

            "100013812439349|C|1.00|".$payment2['id']."|20221203|100.00|******713916801",
            "10001381243****|C|1.00|".$payment2['id']."|20221204|100.00|100000713921213",

        ];

        $txt = "";

        foreach ($data as $row)
        {
            $txt = $txt . $row . PHP_EOL;
        }

        return $txt;
    }

}
