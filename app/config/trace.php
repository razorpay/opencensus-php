<?php

return array(

    /**
     * The following debug options come
     * into play only 'debug' is true.
     *
     * They define the places where the
     * logs will be written.
     */
    'debug_options' => array(
        'screen' => true,
        'browser' => true,
        'chrome' => true),

    /**
     * Displays line/file/class/method from which the log call originated
     */
    'introspection' => false,

    /*
    |--------------------------------------------------------------------------
    | Path for trace logs
    |--------------------------------------------------------------------------
    */

    'logpath' => storage_path().'/logs/trace.log',

    'instance_data_file' => storage_path().'/logs/instance.json',
);