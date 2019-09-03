<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

/**
 * Class BankInformation
 * @package RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl
 * Some of the below contents will be taken from IFSC Service
 * Rest will remain hardcoded in this file and used for Statement Generation
 */
class BankInformation
{
    const SANCTION_LIMIT      = '0';
    const DRAWING_POWER       = '0';
    const BRANCH_TIMINGS      = '10.00 A.M. To 5.00 P.M. (MON - FRI) 10.00 A.M. To 5.00 P.M. (SAT)';
    const CALL_CENTER_NUMBER  = '022-71109111';
}
