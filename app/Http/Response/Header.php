<?php

namespace RZP\Http\Response;

class Header
{
    const PRAGMA                        = 'Pragma';
    const EXPIRES                       = 'Expires';
    const REQUEST_ID                    = 'Request-Id';
    const REMOTE_ADDR                   = 'remote-addr';
    const CONTENT_TYPE                  = 'content-type';
    const CACHE_CONTROL                 = 'Cache-Control';
    const X_FORWARDED_FOR               = 'x-forwarded-for';
    const X_FRAME_OPTIONS               = 'X-Frame-Options';
    const WWW_AUTHENTICATE              = 'WWW-Authenticate';
    const X_RAZORPAY_SIGNATURE          = 'X-Razorpay-Signature';
    const ACCESS_CONTROL_ALLOW_ORIGIN   = 'Access-Control-Allow-Origin';
    // Uses obscure abbreviation because it is temporarily returned to public requests .
    const X_PASSPORT_ATTRS_MISMATCH     = 'X-PAM';
}
