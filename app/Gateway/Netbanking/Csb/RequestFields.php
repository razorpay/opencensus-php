<?php

namespace RZP\Gateway\Netbanking\Csb;

class RequestFields
{
    /**
     * This RequestFields class developed as per API contract.
     * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
     */

    /**
     * Below are the request fields for the authorize request
     */
    const CHNPGSYN     = 'CHNPGSYN';
    const CHNPGCODE    = 'CHNPGCODE';
    const PAYEE_ID     = 'PID';
    const BANK_REF_NUM = 'BRN';
    const RETURN_URL   = 'RU';
    const MODE         = 'MODE';
    const AMOUNT       = 'AMT';
    const ACCOUNT_NUM  = 'ACNO';
    const TRAN_REF_NUM = 'TID';
    const DATE_TIME    = 'DT';
    const NARRATION    = 'NAR';

    /**
     * We would be sending the request data in this field
     */
    const POST_DATA    = 'vData';

    /**
     * Custom defined field used only in Mock/Server.php
     */
    const CHECKSUM     = 'CHECKSUM';
}
