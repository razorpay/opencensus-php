<?php

namespace RZP\Tests\Functional\Setting;

use RZP\Constants\Table;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class SettingTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
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

    public function testGetForInvalidModule()
    {
        $this->startTest();
    }

    public function testSaveAndRetrieveOpenwalletSettings()
    {
        $this->startTest();

        $settings = \DB::connection('test')
                       ->table(Table::SETTINGS)
                       ->pluck('value', 'key');

        $expected = [
            'key1'            => 'value1',
            'nested_key.key2' => 'value2'
        ];

        $this->assertCount(2, $settings->toArray());
        $this->assertArraySelectiveEquals($expected, $settings->toArray());

        $this->checkGetAllSettings();

        $this->checkGetSingleSetting();
    }

    public function testDeleteSettingKey()
    {
        $createSettingsData = $this->testData['testSaveAndRetrieveOpenwalletSettings'];

        $this->runRequestResponseFlow($createSettingsData);

        $this->startTest();

        $settings = \DB::connection('test')
                       ->table(Table::SETTINGS)
                       ->pluck('value', 'key');

        $expected = [
            'key1'            => 'value1'
        ];

        $this->assertCount(1, $settings->toArray());
        $this->assertArraySelectiveEquals($expected, $settings->toArray());
    }

    protected function checkGetAllSettings()
    {
        $getSettingsData = $this->testData['testGetAllOpenwalletSettings'];

        $this->runRequestResponseFlow($getSettingsData);
    }

    protected function checkGetSingleSetting()
    {
        $getSettingsData = $this->testData['testGetSingleOpenwalletSetting'];

        $this->runRequestResponseFlow($getSettingsData);
    }
}
