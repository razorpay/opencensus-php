<?php

return array(
    'enabled' => env('DISTRIBUTED_TRACING_ENABLED', false),
    'host' => env('JAEGER_HOSTNAME',env('NODE_NAME')),
    'port' => env('JAEGER_PORT', 6831),
    'app_mode' => env('INSTANCE_TYPE', ''),
    'tag_service_version' => env('GIT_COMMIT_HASH', ''),
    'tag_app_env' => env('APP_ENV', '')
);
