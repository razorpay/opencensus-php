<?php

namespace RZP\Http\Response;

class Header
{
    const PRAGMA                        = 'Pragma';
    const EXPIRES                       = 'Expires';
    const REMOTE_ADDR                   = 'remote-addr';
    const CONTENT_TYPE                  = 'content-type';
    const CACHE_CONTROL                 = 'Cache-Control';
    const X_FORWARDED_FOR               = 'x-forwarded-for';
    const X_FRAME_OPTIONS               = 'X-Frame-Options';
    const WWW_AUTHENTICATE              = 'WWW-Authenticate';
    const X_RAZORPAY_SIGNATURE          = 'X-Razorpay-Signature';
    const ACCESS_CONTROL_ALLOW_ORIGIN   = 'Access-Control-Allow-Origin';
    const X_RATELIMIT_LIMIT             = 'X-RateLimit-Limit';
    const X_RATELIMIT_REMAINING         = 'X-RateLimit-Remaining';
    const X_RATELIMIT_RESET             = 'X-RateLimit-Reset';
    const X_RATELIMIT_RETRYAFTER        = 'X-RateLimit-RetryAfter';
}
