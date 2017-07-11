<?php

namespace RZP\Http\BasicAuth;

class Type
{
    /**
     * Admin auth when a organization admin (person) is making a request.
     */
    const ADMIN_AUTH        = 'admin';

    /**
     * Basically means no auth is being used.
     */
    const DIRECT_AUTH       = 'direct';

    /**
     * Private auth to be used by merchants.
     */
    const PRIVATE_AUTH      = 'private';

    const PROXY_AUTH        = 'proxy';

    /**
     * For app auth where an app is behaving as an admin.
     * Over time, this will be shifted to oauth model
     */
    const PRIVILEGE_AUTH    = 'privilege';

    const DEVICE_AUTH       = 'device';

    /**
     * Public auth based on just key id
     */
    const PUBLIC_AUTH       = 'public';
}
