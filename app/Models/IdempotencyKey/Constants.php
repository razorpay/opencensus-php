<?php

namespace RZP\Models\IdempotencyKey;

class Constants
{
    // This constant is used to make ikey mandatory for all requests.
    const IKEY_MANDATORY                  = 'ikey_mandatory';

    // This constant is used to make ikey mandatory only when the corresponding feature flag is enabled.
    // This supersedes the IKEY_MANDATORY constant.
    const FEATURE_FLAG_FOR_MANDATORY_IKEY = 'feature_flag_for_mandatory_ikey';
}
