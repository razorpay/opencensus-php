<?php

namespace App\Http;

class Headers
{
    const CSRF_TOKEN = 'X-CSRF-TOKEN';

    const JWT_TOKEN  = 'X-JWT-TOKEN';

    const OAUTH_SOURCE = 'X-OAUTH-SOURCE';
    // The uberctx-dev-serve-user header is passed to upstream service for routing to
    // devserve environment if applicable.
    const DEV_SERVE_USER  = 'uberctx-dev-serve-user';
}
