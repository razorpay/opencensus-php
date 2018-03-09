<?php

namespace RZP\Tests\Unit\GSTIN;

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

    /**
     * Asserts the state metadata returned by the lib function.
     *
     * Note: Failsafe against accidental edits, since changes will
     * break client usage
     */
    public function testGstinStateMap()
    {
        $expected = $this->testData[__FUNCTION__];

        $actual = GSTIN::getStatesToTinIdMap();

        $this->assertEquals($expected, $actual);
    }
}
