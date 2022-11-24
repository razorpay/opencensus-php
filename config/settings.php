<?php

return [
    // valid options: 'json', 'database'
    'store'       => 'database',

    // JSON file path if Json store is used
    'path'        => storage_path() . '/settings.json',

    // Table name, for database store
    'table'       => \RZP\Constants\Table::SETTINGS,

    // For database store, which connection to use.
    // Set to null because set custom connections for test and live
    'connection'  => null,

    // Custom column names for the database table
    'keyColumn'   => 'key',
    'valueColumn' => 'value',
    'defaults'    => []
];
