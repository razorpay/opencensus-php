<?php

namespace RZP\Constants;

class HttpRequestHeader
{
    const USER_AGENT                    = 'user-agent';
    const REFERER                       = 'referer';
    const X_FORWARDED_FOR               = 'x-forwarded-for';
    const REMOTE_ADDR                   = 'remote-addr';
    const WWW_AUTHENTICATE              = 'WWW-Authenticate';
    const CACHE_CONTROL                 = 'Cache-Control';
    const PRAGMA                        = 'Pragma';
    const EXPIRES                       = 'Expires';
    const ACCESS_CONTROL_ALLOW_ORIGIN   = 'Access-Control-Allow-Origin';
    const X_FRAME_OPTIONS               = 'X-Frame-Options';
    const CONTENT_TYPE                  = 'content-type';
    const X_RAZORPAY_SIGNATURE          = 'X-Razorpay-Signature';
}