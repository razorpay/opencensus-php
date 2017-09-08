<?php

namespace RZP\Gateway\Blade;

class InvalidRequestCode
{
    const R_50 = 'Acquirer not participating in 3-D Secure';
    const R_51 = 'Merchant not participating in 3-D Secure';
    const R_52 = 'Password required, but no password was supplied.';
    const R_53 = 'Supplied password is not valid for combination of Acquirer BIN and Merchant ID.';
    const R_54 = 'ISO code not valid per ISO tables';
    const R_55 = 'Transaction data not valid.';
    const R_56 = 'PAReq sent to wrong ACS';
    const R_57 = 'Serial Number cannot be located.';
    const R_58 = 'Issued only by the Directory Server.';
    const R_98 = 'Transient system failure.';
    const R_99 = 'Permanent system failure.';
}
