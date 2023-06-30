<?php

namespace RZP\Http\Edge;

final class Metric
{
    const PASSPORT_JWT_PARSE_FAILED_TOTAL  = 'passport_jwt_parse_failed_total';
    const PASSPORT_ATTRS_MISMATCH_TOTAL    = 'passport_attrs_mismatch_total';
    const AUTHZ_ENFORCEMENT_MISMATCH_TOTAL = 'edge_authz_enforcement_mismatch_total';
    const APP_AUTH_SUCCESS_TOTAL           = 'app_auth_success_count_total';
    const AUTHN_MISMATCH_TOTAL             = 'edge_authn_mismatch_total';
    const EDGE_AUTHFLOW_MISMATCH_TOTAL     = 'edge_authflow_mismatch_total';
    const MIDDLEWARE_PREAUTH_DURATION_MS   = 'middleware_preauthenticate_duration_ms';
    const MIDDLEWARE_POSTAUTH_DURATION_MS  = 'middleware_postauthenticate_duration_ms';
}
