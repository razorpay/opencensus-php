<?php

namespace RZP\Gateway\Hdfc;

final class Constants
{
    const ENROLL_RESULT_LENGTH = 20;

    const STATUS_LENGTH = 50;

    const AUTH_RESULT_LENGTH = 255;

    const ECI_LENGTH = 2;

    const AUTH_LENGTH = 6;

    const REF_LENGTH = 12;

    const AVR_LENGTH = 3;

    const POSTDATE_LENGTH = 6;

    const ERROR_CODE_LENGTH = 8;

    const ERROR_TEXT_LENGTH = 100;

    // Time :- 02/05/2018 20:30:00
    const IPAY_MIGRATION_CHECK_SOFT = 1525273200;
    // Time :- 03/05/2018 02:00:00
    const IPAY_MIGRATION_CHECK_HARD = 1525293000;

    const PRE_AUTH_TYPE  = 'VPAS';

    // Constants
    const DEBIT_SECOND_RECURRING_PAYMENT_CAVV = 'CAACB3mHZ4IggwVGFYdnAAAAAAA=';

    const DEBIT_SECOND_RECURRING_PAYMENT_XID  = 'CdCzZGjEKfLvQ5E4SU27DSC918k=';

    // HDFC DEBIT SI authorize request for 2nd recurring payment
    const DEBIT_SECOND_RECURRING_PAYMENT_ENROLLMENT_FLAG     = 'Y';

    const DEBIT_SECOND_RECURRING_PAYMENT_AUTHENTICATION_FLAG = 'Y';

}
