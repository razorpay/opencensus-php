<?php

namespace RZP\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        'checkcookie',
        // We use razorpay_api_session_v2_partitioned only in the customer-logout
        // route to unset it on the browser.
        'razorpay_api_session_v2_partitioned',
    ];
}
