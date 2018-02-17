<?php

namespace RZP\Tests\Unit\Fetch;

use Lib\GSTIN;
use RZP\Tests\TestCase;

class GSTINTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/GSTINTestData.php';

        parent::setUp();
    }

    public function testValidGstin()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $gstin)
        {
            $this->assertTrue(GSTIN::isValid($gstin), 'Failed validity test for GSTIN: '. $gstin);
        }
    }

    public function testInvalidGstin()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $gstin)
        {
            $this->assertFalse(GSTIN::isValid($gstin), 'Failed invalid test for GSTIN: '. $gstin);
        }
    }
}
