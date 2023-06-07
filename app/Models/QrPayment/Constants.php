<?php

namespace RZP\Models\QrPayment;

class Constants
{
    public const CREATED = 'created';

    public const CACHE_VALUE_SEPARATOR = ':';

    public const SUCCESS_STATUS_TTL = 300; // 300 seconds i.e. 5 minutes
    public const CREATED_STATUS_TTL = 720; // 720 seconds i.e. 12 minutes
    public const DEFAULT_CACHE_TTL = 720; // 720 seconds i.e. 12 minutes

    public const PAYMENT_TYPE_OFFLINE = 'offline';
    public const PAYMENT_TYPE_KEY     = 'receiver_type';

}
