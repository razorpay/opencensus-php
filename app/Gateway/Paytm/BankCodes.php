<?php

namespace RZP\Gateway\Paytm;

use RZP\Models\Bank\IFSC;

class BankCodes
{
    public static $bankCodeMap = array(
        IFSC::BARB => 'BOB',
        IFSC::CITI => 'CITI',
        IFSC::CIUB => 'CITIUB',
        IFSC::CSBK => 'CSB',
        IFSC::FDRL => 'FDEB',
        IFSC::HDFC => 'HDFC',
        IFSC::ICIC => 'ICICI',
        IFSC::IDIB => 'INDB',
        IFSC::INDB => 'INDS',
        IFSC::IOBA => 'IOB',
        IFSC::JAKA => 'JKB',
        IFSC::KKBK => 'KOTAK',
        IFSC::MAHB => 'BOM',
        IFSC::PUNB => 'PNB',
        IFSC::UBIN => 'UNI',
        IFSC::UTIB => 'AXIS',
        IFSC::VIJB => 'VJYA',
        IFSC::VYSA => 'ING',
        IFSC::YESB => 'YES',
    );
}
