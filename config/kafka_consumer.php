<?php

return array(
    'consumer_poll_timeout_ms' => 120 * 1000,

    'client_id' => 'api-rx-kafka',

    'fts_status_update_retry_topic' => env('FTS_STATUS_UPDATE_RETRY_TOPIC', 'rx-fts-status-update-retry-events')
);
