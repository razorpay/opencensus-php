<?php

namespace RZP\Models\Merchant\Credits;

class Constants
{
    const MERCHANT_CREDIT_TYPE_MUTEX_PREFIX = 'merchant_credit_type_';
    const MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT = 30; // seconds
    const MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT = 5;
}
