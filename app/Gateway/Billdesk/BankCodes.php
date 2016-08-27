<?php

namespace RZP\Gateway\Billdesk;

use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Processor\Netbanking;

class BankCodes
{
    public static $bankCodeMap = array(
        IFSC::ALLA => 'ALB',
        IFSC::BKID => 'BOI',
        IFSC::CIUB => 'CUB',
        IFSC::UTIB => 'UTI',
        IFSC::ICIC => 'ICI',
        IFSC::ANDB => 'ADB',
        IFSC::BBKM => 'BBK',
        IFSC::MAHB => 'BOM',
        IFSC::CBIN => 'CBI',
        IFSC::CNRB => 'CNB',
        IFSC::COSB => 'COB',
        IFSC::CORP => 'CRP',
        IFSC::CSBK => 'CSB',
        IFSC::DEUT => 'DBK',
        IFSC::DCBL => 'DCB',
        IFSC::BKDN => 'DEN',
        IFSC::DLXB => 'DLB',
        IFSC::FDRL => 'FBK',
        IFSC::IBKL => 'IDB',
        IFSC::INDB => 'IDS',
        IFSC::IDIB => 'INB',
        IFSC::VYSA => 'ING',
        IFSC::IOBA => 'IOB',
        IFSC::JAKA => 'JKB',
        IFSC::KARB => 'KBL',
        IFSC::KKBK => '162',
        IFSC::KVBL => 'KVB',
        IFSC::ORBC => 'OBC',
        IFSC::PMCB => 'PMC',
        IFSC::PSIB => 'PSB',
        IFSC::ABNA => 'RBS',
        IFSC::RATN => 'RTN',
        IFSC::SIBL => 'SIB',
        IFSC::SVCB => 'SVC',
        IFSC::SRCB => 'SWB',
        IFSC::SYNB => 'SYD',
        IFSC::TMBL => 'TMB',
        IFSC::TNSC => 'TNC',
        IFSC::UBIN => 'UBI',
        IFSC::UCBA => 'UCO',
        IFSC::UTBI => 'UNI',
        IFSC::VIJB => 'VJB',
        IFSC::YESB => 'YBK',
        IFSC::JSBP => 'JSB',
        IFSC::NKGS => 'NKB',
        IFSC::SBBJ => 'SBJ',
        IFSC::SBHY => 'SBH',
        IFSC::SBIN => 'SBI',
        IFSC::SBMY => 'SBM',
        IFSC::SCBL => 'SCB',
        IFSC::STBP => 'SBP',
        IFSC::SBTR => 'SBT',
        IFSC::DBSS => 'DBS',
        Netbanking::BARB_C => 'BBC',
        Netbanking::BARB_R => 'BBR',
        Netbanking::PUNB_C => 'CPN',
        Netbanking::PUNB_R => 'PNB',
        Netbanking::LAVB_C => 'LVC',
        Netbanking::LAVB_R => 'LVR',
    );

    // We are not using Deusctche Bank corporate net-banking currently.
}
