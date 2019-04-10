<?php

namespace RZP\Gateway\Upi\Axis;

class Status
{
    /**
     * @see
     */

    const TOKEN_SUCCESS             = '000';

    const TOKEN_CHECKSUM_FAILED     = '500';

    const TOKEN_CHECKSUM_MISMATCH   = '444';

    const TOKEN_INCOMPLETE          = '111';

    const TOKEN_VALIDATION_ERROR    = '125';

    const TOKEN_DUPLICATE           = '303';

    const COLLECT_SUCCESS           = '00';

    const COLLECT_DUPLICATE         = '111';

    const COLLECT_TOKEN_NOT_FOUND   = '111';

    const COLLECT_INVALID_VPA       = 'ZH';

    const VERIFY_FAILED             = 'F';

    const VERIFY_DEEMED             = 'D';

    const VERIFY_PENDING            = 'P';

    const VERIFY_EXPIRED            = 'E';

    const VERIFY_REJECT             = 'R';

    const VERIFY_SUCCESS            = 'S';

    const CALLBACK_SUCCESS          = '00';

    const CALLBACK_FAILED           = 'U30';

    const CALLBACK_REJECTED         = 'ZA';

    const REFUND_SUCCESS            = '000';

    const REFUND_ABSENT             = '111';
}
