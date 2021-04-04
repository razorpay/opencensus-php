<?php

namespace RZP\Tests\Unit\GSTIN;

use Lib\Gstin;
use RZP\Tests\TestCase;

class GstinTest extends TestCase
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/GstinTestData.php';

        parent::setUp();
    }

    public function testValidGstin()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $gstin)
        {
            $this->assertTrue(Gstin::isValid($gstin), 'Failed validity test for GSTIN: ' . $gstin);
        }
    }

    public function testInvalidGstin()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $gstin)
        {
            $this->assertFalse(Gstin::isValid($gstin), 'Failed invalid test for GSTIN: ' . $gstin);
        }
    }

    public function testValidStateCode()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $code)
        {
            $this->assertTrue(
                Gstin::isValidStateCode($code),
                'Failed validity test for GST State Code: ' . $code);
        }
    }

    public function testInvalidStateCode()
    {
        $list = $this->testData[__FUNCTION__];

        foreach ($list as $code)
        {
            $this->assertFalse(
                Gstin::isValidStateCode($code),
                'Failed invalid test for GST State Code: ' . $code);
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

        $actual = Gstin::getGstinStateMetadata();

        $this->assertEquals($expected, $actual);
    }
}
