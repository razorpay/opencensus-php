<?php

namespace RZP\Services\Kafka\Utils;

class Constants
{
    // log constants
    const CONTENT          = 'content';
    const CASE             = 'case';
    const ERROR            = 'error';
    const EXCEPTION        = 'exception';
    const INFO             = 'info';

    const MODE             = 'mode';
    const CONSUMER         = 'consumer';
    const TOPICS           = 'topics';
    const GROUP_ID         = 'groupId';
    const PAYLOAD          = 'payload';
    const RESPONSE         = 'response';
    const RETRY_ATTEMPT_NO = 'retry_attempt_no';
    const RETRY_TIMESTAMP  = 'retry_timestamp';

    // clusters
    const SHARED_CLUSTER = 'shared_cluster';
    const RX_CLUSTER     = 'rx_cluster';
}
