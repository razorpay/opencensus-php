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
    const ACCESS_CONTROL_ALLOW_HEADER   = 'Access-Control-Allow-Headers';
    const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
    // Uses obscure abbreviation because it is temporarily returned to public requests .
    const X_PASSPORT_ATTRS_MISMATCH     = 'X-PAM';
    // User for internal use. This will be consumed by edge layer
    const X_ROUTE_NAME                  = 'X-Route-Name';
    // Used to detect if extra header need to pass in response
    const X_EDGE_ROUTE_DETAILS          = 'X-Edge-Route-Details';
    // Used to pass the product for filtering out logs on developer-console
    const X_DC_PRODUCT_NAME             = 'X-DC-Product-Name';
}
