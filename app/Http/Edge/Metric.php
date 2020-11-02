<?php

namespace RZP\Http\Edge;

final class Metric
{
    const PASSPORT_JWT_PARSE_FAILED_TOTAL  = 'passport_jwt_parse_failed_total';
    const PASSPORT_ATTRS_MISMATCH_TOTAL    = 'passport_attrs_mismatch_total';
    const AUTHZ_ENFORCEMENT_MISMATCH_TOTAL = 'edge_authz_enforcement_mismatch_total';
}
