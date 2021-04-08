<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class HitachiVisaIINBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/HitachiVisaIINBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

//    public function testBulkIinVisaUpdate()
//    {
//        $text = $this->getFileText();
//
//        $this->createAndPutTxtFileInRequest('file.txt', $text, __FUNCTION__);
//
//        $this->ba->adminAuth();
//
//        $response = $this->startTest();
//
//        $batch = $this->getLastEntity('batch', true);
//
//        $this->assertEquals(21, $batch['processed_count']);
//        $this->assertEquals(21, $batch['success_count']);
//        $this->assertEquals(0, $batch['failure_count']);
//        $this->assertEquals('processed', $batch['status']);
//
//        $iin = $this->getEntityById('iin', '000055', true);
//
//        $this->assertEquals(null, $iin['issuer']);
//    }

    public function testBulkIinViaBatchService()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $data =
        [
            "400079648   400079647   400079116  424436W6RU  6RU        N1 Y       C   Y      ",
            "400079721   400079721   400079116  424436W6RU  6RU        N1 Y       C   Y      ",
            "400079853   400079853   400079116  424436W6RU  6RU        N  Y       C   Y      ",
            "400090000   400090000   400090116  400801W1US  1XY      AAA          C      ",

        ];
        for ($index = 0; $index < 4; $index++) {
            $this->testData[__FUNCTION__]['request']['content'][$index]["row"] = $data[$index];
        }

        $this->startTest();
    }

    protected function getFileText()
    {
        $data = [
            "400055109   400055000   400055116  400800W1US  1US     YAAG          C",
            "000055110   000055110   000055116  400800W1US  1US     YAAG          C",
            "000057999   000057000   000057116  402206W1US  1US      AAJ3         P      ",
            "014262999   000062000   000062116  400800W1US  1US      AAG          C      ",
            "400064009   400064000   400064116  400088W1US  1US      AAG  Y       D      ",
            "400071999   400071000   400071116  490801W6TM  6TM        P  Y       D      ",
            "400076999   400076000   400076116  400076W6MU  6MU        N          D   Y      ",
            "400077999   400077000   400077116  400076W6MU  6MU        G          D   Y      ",
            "400079347   400079347   400079116  424436W6RU  6RU        N1 Y       C   Y      ",
            "400079351   400079351   400079116  424436W6RU  6RU        N  Y       C   Y      ",
            "400079529   400079529   400079116  424436W6RU  6RU        N  Y       C   Y      ",
            "400079648   400079647   400079116  424436W6RU  6RU        N1 Y       C   Y      ",
            "400079721   400079721   400079116  424436W6RU  6RU        N1 Y       C   Y      ",
            "400079853   400079853   400079116  424436W6RU  6RU        N  Y       C   Y      ",
            "400090000   400090000   400090116  400801W1US  1US      AAA          C      ",
            "400090062   400090062   400090116  400801W1US  1US      AAA          C      ",
            "400090500   400090500   400090116  400801W1US  1US      AAA          C      ",
            "400090702   400090702   400090116  400801W1US  1US      AAA          C      ",
            "400093000   400093000   400093116  400801W1US  1US      AAA          C      ",
            "400093500   400093500   400093116  400801W1US  1US      AAA          C      ",
            "400095000   400095000   400095116  400801W1US  1        AAA          C      ",
        ];
        $txt = "";

        foreach ($data as $row)
        {
            $txt = $txt . $row . PHP_EOL;
        }

        return $txt;
    }
}
