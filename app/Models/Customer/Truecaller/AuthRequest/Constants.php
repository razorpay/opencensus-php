<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

class Constants
{
    /** Cache related constants */
    public const CACHE_VALUE_SEPARATOR = ':';
    public const TRUECALLER_USER_PROFILE_TTL = 600; // 600 seconds i.e. 10 minutes
    public const TRUECALLER_REQUEST_ID_TTL = 3600; // 3600 seconds i.e. 60 minutes
    public const CACHE_PREFIX = 'truecaller_auth_request_';
    public const DEFAULT_SERVICE = 'checkout';

    /** Entity Status constants*/
    public const ACTIVE   = 'active';
    public const RESOLVED = 'resolved';

    /** Authentication statuses from truecaller */
    public const USER_REJECTED       = 'user_rejected';
    public const USE_ANOTHER_NUMBER = 'use_another_number';
    public const ACCESS_DENIED       = 'access_denied';
    public const PENDING             = 'pending';
    public const SUCCESSFUL          = 'successful';

    public const TRUECALLER_MOCK_ENDPOINT = '/customers/truecaller/user_profile';

    public const TRUECALLER_REQUEST_TIMEOUT = 5; // 5 seconds

    public const REJECTED_STATUES = [
        self::USER_REJECTED => 1,
        self::USE_ANOTHER_NUMBER => 2,
    ];

    public const VALID_STATUSES = [
        self::ACTIVE => 1,
        self::RESOLVED => 2,
    ];
}
