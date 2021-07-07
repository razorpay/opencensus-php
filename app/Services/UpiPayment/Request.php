<?php

namespace RZP\Services\UpiPayment;

class Request
{
    // request body parameters
    const PAYMENT   = 'payment';
    const METADATA  = 'metadata';
    const TERMINAL  = 'terminal';
    const MERCHANT  = 'merchant';
    const ACTION    = 'action';

    // request uri
    const AUTHORIZE_URI = 'authorize';

    // request constants
    const URL       = 'url';
    const METHOD    = 'method';
    const CONTENT   = 'content';
    const HEADERS   = 'headers';
    const OPTIONS   = 'options';

    // http Methods
    const POST = 'POST';

    // header parameters
    const CONTENT_TYPE_HEADER       = 'Content-Type';
    const ACCEPT_HEADER             = 'Accept';
    const X_RAZORPAY_APP_HEADER     = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER  = 'X-Razorpay-TaskId';
    const X_REQUEST_ID              = 'X-Request-ID';
    const X_RAZORPAY_TRACKID        = 'X-Razorpay-TrackId';
    const AUTH_HEADER               = 'auth';

    //Content Type
    const APPLICATION_JSON          = 'application/json';
}
