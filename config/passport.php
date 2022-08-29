<?php

return [
    'public_key' => str_replace('\n', PHP_EOL, env('PASSPORT_PUBLIC_KEY')),
];
