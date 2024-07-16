<?php

namespace RZP\Models\IdempotencyKey;

final class Metric
{
    // This metric is used to record all errors that transpire during the process of idempotency check.
    const IDEMPOTENCY_CHECK_ERRORS = 'idempotency_check_errors';
}
