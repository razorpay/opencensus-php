<?php

namespace RZP\Tests\Unit\Models\Payment;

use RZP\Models\Payment\Gateway;
use RZP\Tests\TestCase;

class GatewayConfigTest extends TestCase
{
    public function testGetTerminalsForValidateVpa()
    {
        $live = Gateway::getTerminalsForValidateVpaForMode('live');
        $test = Gateway::getTerminalsForValidateVpaForMode('test');

        // Picked from the Gateway::$upiValidateVpaTerminals
        $this->assertSame(['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'], $live);

        // Set from .env.default
        $this->assertSame(['1000SharpTrmnl', '100UPIMindgate', '100UPIMgateSbi', '100UPIICICITml'], $test);
    }

    public function getTerminalsForValidateVpaForMode()
    {
        $cases = [];

        // Live Environment
        $message = 'LiveEmptyString';
        $expected = ['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'];
        $cases[$message] = ['live', '', $expected];

        $message = 'LiveNull';
        $expected = ['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'];
        $cases[$message] = ['live', null, $expected];

        $message = 'LiveSingleValue';
        $expected = ['SampleTid00001'];
        $cases[$message] = ['live', 'SampleTid00001', $expected];

        $message = 'LiveTwoValues';
        $expected = ['SampleTid00001', 'SampleTid00002'];
        $cases[$message] = ['live', 'SampleTid00001, SampleTid00002', $expected];

        $message = 'LiveSingleIncorrectIdValue';
        $expected = ['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'];
        $cases[$message] = ['live', 'SampleTid', $expected];

        $message = 'LiveOneIncorrectIdValue';
        $expected = ['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'];
        $cases[$message] = ['live', 'SampleTid00001, SampleTid', $expected];

        // Test Environment
        $message = 'TestEmptyString';
        $expected = ['1000SharpTrmnl'];
        $cases[$message] = ['test', '', $expected];

        $message = 'TestNull';
        $expected = ['1000SharpTrmnl'];
        $cases[$message] = ['test', null, $expected];

        $message = 'TestSingleValue';
        $expected = ['SampleTid00001'];
        $cases[$message] = ['test', 'SampleTid00001', $expected];

        $message = 'TestTwoValues';
        $expected = ['SampleTid00001', 'SampleTid00002'];
        $cases[$message] = ['test', 'SampleTid00001, SampleTid00002', $expected];

        $message = 'TestSingleIncorrectIdValue';
        $expected = ['1000SharpTrmnl'];
        $cases[$message] = ['test', 'SampleTid', $expected];

        $message = 'TestOneIncorrectIdValue';
        $expected = ['1000SharpTrmnl'];
        $cases[$message] = ['test', 'SampleTid00001, SampleTid', $expected];

        return $cases;
    }

    /**
     * @dataProvider getTerminalsForValidateVpaForMode
     */
    public function testGetTerminalsForValidateVpaForMode(
        string $mode,
        string $value = null,
        array $expected = [])
    {
        config()->set('gateway.validate_vpa_terminal_ids.' . $mode, $value);

        $output = Gateway::getTerminalsForValidateVpaForMode($mode);

        $this->assertSame($expected, $output);
    }
}
