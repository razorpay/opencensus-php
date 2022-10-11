<?php

namespace RZP\Http\Edge;

final class Constant
{
    const AUTHZ_RESULT_HEADER           = 'X-AUTHORIZATION-RESULT';
    const AUTHZ_RESULT_ALLOWED          = 'allowed';
    const AUTHZ_RESULT_DENIED           = 'denied';
    const AUTHN_RESULT_HEADER           = 'X-AUTHENTICATION-RESULT';
    const IMPERSONATION_RESULT_HEADER   = 'X-IMPERSONATION-RESULT';
    const AUTHN_RESULT_ALLOWED          = 'true';
    const AUTHN_RESULT_DENIED           = 'false';
}
