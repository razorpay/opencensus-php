<?php

namespace RZP\Models\Settings;

use Illuminate\Support\Manager;
use Illuminate\Foundation\Application;
use anlutro\LaravelSettings\JsonSettingStore;
use anlutro\LaravelSettings\MemorySettingStore;
use anlutro\LaravelSettings\DatabaseSettingStore;


class CustomSettingsManager extends \anlutro\LaravelSettings\SettingsManager
{
    
    public function createDatabaseDriver()
    {
        $connectionName = $this->getConfig('anlutro/l4-settings::connection');
        $connection = $this->getSupportedContainer()['db']->connection($connectionName);
        $table = $this->getConfig('anlutro/l4-settings::table');
        $keyColumn = $this->getConfig('anlutro/l4-settings::keyColumn');
        $valueColumn = $this->getConfig('anlutro/l4-settings::valueColumn');

        $store = new CustomDatabaseSettingStore($connection, $table, $keyColumn, $valueColumn);

        return $this->wrapDriver($store);
    }
}