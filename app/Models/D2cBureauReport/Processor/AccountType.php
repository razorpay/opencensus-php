<?php

namespace RZP\Models\D2cBureauReport\Processor;

class AccountType
{
    const LOAN_TYPE = array(
        '1'  => 'AUTO LOAN' ,
        '2'  => 'HOUSING LOAN ',
        '3'  => 'PROPERTY LOAN',
        '4'  => 'CORPORATE CREDIT CARD',
        '5'  => 'PERSONAL LOAN',
        '6'  => 'CONSUMER LOAN',
        '7'  => 'GOLD LOAN',
        '8'  => 'EDUCATIONAL LOAN',
        '9'  => 'LOAN TO PROFESSIONAL',
        '10' => 'CREDIT CARD',
        '11' => 'LEASING',
        '12' => 'OVERDRAFT',
        '13' => 'TWO-WHEELER LOAN',
        '14' => 'NON-FUNDED CREDIT FACILITY',
        '15' => 'LOAN AGAINST BANK DEPOSITS',
        '16' => 'FLEET CARD',
        '17' => 'Commercial Vehicle Loan',
        '18' => 'Telco – Wireless',
        '19' => 'Telco – Broadband',
        '20' => 'Telco – Landline',
        '31' => 'Secured Credit Card',
        '32' => 'Used Car Loan',
        '33' => 'Construction Equipment Loan',
        '34' => 'Tractor Loan',
        '35' => 'CORPORATE CREDIT CARD',
        '36' => 'Kisan Credit Card',
        '37' => 'Loan on Credit Card',
        '38' => 'Prime Minister Jaan Dhan Yojana - Overdraft',
        '39' => 'Mudra Loans – Shishu / Kishor / Tarun',
        '43' => 'Microfinance – Others',
        '51' => 'BUSINESS LOAN – GENERAL',
        '52' => 'BUSINESS LOAN –PRIORITY SECTOR – SMALL BUSINESS',
        '53' => 'BUSINESS LOAN –PRIORITY SECTOR – AGRICULTURE',
        '54' => 'BUSINESS LOAN –PRIORITY SECTOR – OTHERS',
        '55' => 'BUSINESS NON-FUNDED CREDIT FACILITY – GENERAL',
        '56' => 'BUSINESS NON-FUNDED CREDIT FACILITY – PRIORITY SECTOR – SMALL BUSINESS',
        '57' => 'BUSINESS NON-FUNDED CREDIT FACILITY – PRIORITY SECTOR – AGRICULTURE',
        '58' => 'BUSINESS NON-FUNDED CREDIT FACILITY – PRIORITY SECTOR – OTHERS',
        '59' => 'BUSINESS LOANS AGAINST BANK DEPOSITS',
        '60' => 'Staff Loan' ,
        '61' => 'Business Loan - Unsecured ',
        '0'  => 'Other',
    );

    const DEFAULT_ACCOUNT_TYPE = 'Other';

    public static function getAccountType($accountTypeNumber)
    {
        if(array_key_exists($accountTypeNumber, self::LOAN_TYPE) === true)
        {
            return self::LOAN_TYPE[$accountTypeNumber];
        }

        return self::DEFAULT_ACCOUNT_TYPE;

    }
}
