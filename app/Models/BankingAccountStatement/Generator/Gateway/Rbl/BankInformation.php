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
    const BRANCH_NAME         = 'Lower Parel Mumbai (0088)';
    const BRANCH_ADDRESS      = 'TOWER 2B, ONE INDIA BULLS CENTRE, 3RD FLOOR, SENAPAT BAPAT MARG';
    const BRANCH_CITY         = 'Mumbai';
    const BRANCH_STATE        = 'MAHARASHTRA';
    const BRANCH_PINCODE      = '400013';
    const SANCTION_LIMIT      = '0';
    const DRAWING_POWER       = '0';
    const BRANCH_TIMINGS      = '10.00 A.M. To 5.00 P.M. (MON - FRI) 10.00 A.M. To 5.00 P.M. (SAT)';
    const CALL_CENTER_NUMBER  = '022-71109111';
    const BRANCH_PHONE_NUMBER = '02243020600/43020603';
    const IFSC_CODE           = 'RATN0000088';
}
