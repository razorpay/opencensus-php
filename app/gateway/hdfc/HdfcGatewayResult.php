<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayResult
{
    /**
     * Result codes received in response for card enrollment
     */
    const ENROLLED = 'ENROLLED';
    const NOT_ENROLLED = 'NOT ENROLLED';
    const FSS001 = 'FSS001';

    /**
     * Result codes received in response for transaction
     * authorization
     */
    const CAPTURED = 'CAPTURED';
    const APPROVED = 'APPROVED';
    const NOT_CAPTURED = 'NOT CAPTURED';
    const NOT_APPROVED = 'NOT APPROVED';
    const DENIED_BY_RISK = 'DENIED BY RISK';
    const HOST_TIMEOUT = 'HOST TIMEOUT';
}