<?php

namespace RZP\Gateway\Upi\Icici;

class Status
{
    const TXN_INITIATED = 92;

    const TXN_SUCCESS = 0;

    const INVALID_VPA   = 5007;

    const INVALID_PSP   = 5008;

    const SUCCESS    = 'SUCCESS';

    const PENDING    = 'PENDING';

    const FAILURE    = 'FAILURE';

    const FAIL       = 'FAIL';

    const REJECT     = 'REJECT';

    const DEEMED     = 'DEEMED';

    const NO_RECORDS = 'original record not found';

    const NO_RECORDS2 = 'merchant tranid is not available';

    const PAUSE_SUCCESS = 'SUSPEND-SUCCESS';

    const RESUME_SUCCESS = 'REACTIVATE-SUCCESS';

    const REVOKE_SUCCESS = 'REVOKE-SUCCESS';

    const REVOKED_SUCCESS = 'REVOKED-SUCCESS';

    const REVOKE_STATUS     = ['VA', 'QC', '3002'];

    const PAUSE_STATUS      = ['3001', 'QA', '3003'];
}
