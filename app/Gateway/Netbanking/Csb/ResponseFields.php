<?php

namespace RZP\Gateway\Netbanking\Csb;

class ResponseFields
{
    /**
     * This ResponseFields class developed as per API contract.
     * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
     */

    const PAYEE_ID       = 'PID';
    const BANK_REF_NUM   = 'BRN';
    const AMOUNT         = 'AMT';
    const MODE           = 'MODE';
    const NARRATION      = 'NAR';
    const DATE_TIME      = 'DT';
    const TRAN_REF_NUM   = 'TID';

    const QOUT           = 'Qout';

    const DATA           = 'DATA';

    /**
     * Status of the transaction
     */
    const STATUS         = 'STATUS';

    const BANKID         = 'BankId';
    const CHNPGCODE      = 'CHNPGCODE';

    const CHECKSUM       = 'CHECKSUM';

    /**
     * Verification XML element
     */
    const VERIFICATION   = 'Verification';
    const STATUS_UCFIRST = 'Status';
}
