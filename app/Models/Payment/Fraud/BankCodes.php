<?php

namespace RZP\Models\Payment\Fraud;

class BankCodes
{
    // Fraud Types for Visa(Also used for other card networks except MasterCard)
    const FRAUD_CODE_0 = '0';
    const FRAUD_CODE_1 = '1';
    const FRAUD_CODE_2 = '2';
    const FRAUD_CODE_3 = '3';
    const FRAUD_CODE_4 = '4';
    const FRAUD_CODE_5 = '5';
    const FRAUD_CODE_6 = '6';
    const FRAUD_CODE_9 = '9';
    const FRAUD_CODE_A = 'A';
    const FRAUD_CODE_B = 'B';

    // Fraud Types for MasterCard
    const FRAUD_CODE_00 = '00';
    const FRAUD_CODE_01 = '01';
    const FRAUD_CODE_02 = '02';
    const FRAUD_CODE_03 = '03';
    const FRAUD_CODE_04 = '04';
    const FRAUD_CODE_05 = '05';
    const FRAUD_CODE_06 = '06';
    const FRAUD_CODE_07 = '07';
    const FRAUD_CODE_51 = '51';

    // Fraud Sub-types for MasterCard
    const FRAUD_CODE_K = 'K';
    const FRAUD_CODE_N = 'N';
    const FRAUD_CODE_P = 'P';
    const FRAUD_CODE_U = 'U';
}
