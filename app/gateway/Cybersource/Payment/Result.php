<?php

namespace Gateway\Cybersource\Payment;

use Gateway\Cybersource;

final class Result
{
    /**
     * Result codes received in response for card enrollment
     */

    const ENROLLED      = 475;

    const NOT_ENROLLED  = 100;

    const CAPTURED      = 100;

    const AUTHORIZED    = 100;

    const VALIDATED     = 100;

}