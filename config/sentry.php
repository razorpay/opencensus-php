<?php

return [
    'dsn'                      => env('SENTRY_DSN'),
    'breadcrumbs.sql_bindings' => true,
    'user_context'             => true,
    'trace'                    => true,
    'mock'                     => env('SENTRY_MOCK', true),
    'curl_method'              => 'exec',
];
