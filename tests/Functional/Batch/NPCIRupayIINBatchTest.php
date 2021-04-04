<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class NPCIRupayIINBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NPCIRupayIINBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkIinUpdate()
    {
        $text = $this->getFileText();

        $this->createAndPutTxtFileInRequest('file.dat', $text, __FUNCTION__);

        $this->ba->adminAuth();

        $response = $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(11, $batch['processed_count']);
        $this->assertEquals(11, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals('processed', $batch['status']);

        $iin = $this->getEntityById('iin', '360001', true);

        $this->assertEquals(null, $iin['issuer']);

        $iin = $this->getEntityById('iin', '608229', true);

        $this->assertEquals("IDFB", $iin['issuer']);
    }

    protected function getFileText()
    {
        $data = [
            "HDR19052701.00",
            "    065000160726100060726199916S010101E&M01D356IN140513000000N",
            "IDFC065000160822900060822999916S010103EMV01D356IN170904000000N",
            "ABPB769000160739700060739799916D010101EMV01D356IN180202000000N",
            "ALLA010000160701600060701699916S010101EMV01D356IN190206000000N",
            "ALLA010000160711700060711799916S010101MAG01D356IN190206000000N",
            "ALLA010000160713700060713799916S010122EMV01D356IN190206000000N",
            "ALLA010000160735200060735299916S010101EMV01D356IN171031000000N",
            "ALLA010000160810200060810299916S010101EMV01D356IN190206000000N",
            "ALLA010000160817100060817199916S010101EMV01D356IN190206000000N",
            "NDCI500000136000100036000199914D020511DEF04I558NI020330000000N",
            "TRL02956301.00"
        ];

        $txt = "";

        foreach ($data as $row)
        {
            $txt = $txt . $row . PHP_EOL;
        }

        return $txt;
    }
}
