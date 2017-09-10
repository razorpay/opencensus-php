<?php

namespace RZP\Tests\Functional\Setting;

use RZP\Constants\Table;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class SettingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SettingTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth('10000000000000');
    }

    public function testGetOpenwalletDefinedSettings()
    {
        $this->startTest();
    }

    public function testGetDefinedSettingsInvalidModule()
    {
        $this->startTest();
    }

    public function testSaveOpenwalletSettings()
    {
        $this->startTest();

        $settings = \DB::connection('test')
                       ->table(Table::SETTING)
                       ->pluck('value', 'key');

        $expected = [
            'key1'            => 'value1',
            'nested_key.key2' => 'value2'
        ];

        $this->assertArraySelectiveEquals($expected, $settings->toArray());
    }
}
