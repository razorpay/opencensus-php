<?php

namespace Gateway\Billdesk;

use Models\Bank\IFSC;

class BankCodes
{
    public static $bankCodeMap = array(
        IFSC::ICIC => 'ICI',
        IFSC::SBIN => 'SBI',
        IFSC::HDFC => 'HDFC',
        IFSC::CITI => 'CITI',
        IFSC::UTIB => 'AXIS',
        IFSC::SBMY => 'SBM',
        IFSC::SBTR => 'SBT',
        IFSC::YESB => 'YES',
        IFSC::STBP => 'SBOP',
        IFSC::SBBJ => 'SBJ',
        IFSC::KKBK => 'KOTAK',
        IFSC::SBHY => 'SBH',
        IFSC::BARB => 'BOB',
        IFSC::MAHB => 'BOM',
        IFSC::CSBK => 'CSB',
        IFSC::CIUB => 'CITIUB',
        IFSC::FDRL => 'FDEB',
        IFSC::IDIB => 'INDB',
        IFSC::IOBA => 'IOB',
        IFSC::INDB => 'INDS',
        IFSC::VYSA => 'ING',
        IFSC::JAKA => 'JKB',
        IFSC::PUNB => 'PNB',
        IFSC::UBIN => 'UNI',
        IFSC::VIJB => 'VJYA',
    );
}