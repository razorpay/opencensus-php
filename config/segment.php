<?php

return [
    'write_key'     => env('SEGMENT_WRITE_KEY'),
    'storage_path'  => storage_path(). '/logs/segment.log',
    'debug'         => env('SEGMENT_DEBUG'),
];
