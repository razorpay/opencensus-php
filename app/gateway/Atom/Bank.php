<?php

namespace Gateway\Atom;

use Models\Bank\IFSC;

class Bank
{
    const ATOM = '2001';

    protected static $map = array(
        IFSC::UTIB => 1003,     // Axis Bank
        IFSC::BKID => 1012,     // Bank of India
        IFSC::MAHB => 1033,     // Bank of Maharashtra
        IFSC::CNRB => 1030,     // Canara Bank NB
        IFSC::CSBK => 1031,     // Catholic Syrian Bank
        IFSC::CBIN => 1028,     // Central Bank of India
        IFSC::CITI => 1010,     // Citi Bank
        IFSC::CIUB => 1020,     // City Union Bank
        IFSC::CORP => 1004,     // Corporation Bank
        IFSC::DCBL => 1027,     // DCB Bank, Development Credit Bank
        IFSC::DEUT => 1024,     // Deustche Bank
        IFSC::DLXB => 1038,     // Dhanlaxmi Bank
        IFSC::FDRL => 1019,     // Federal Bank
        IFSC::HDFC => 1006,     // Hdfc Bank
        IFSC::ICIC => 1002,     // ICICI Bank
        IFSC::IBKL => 1007,     // IDBI Bank
        IFSC::IDIB => 1026,     // Indian Bank
        IFSC::IOBA => 1029,     // Indian Overseas Bank
        IFSC::INDB => 1015,     // IndusInd Bank
        IFSC::JAKA => 1001,     // Jammu and Kashmir Bank
        IFSC::KARB => 1008,     // Karnataka Bank
        IFSC::KVBL => 1018,     // Karur Vysya Bank
        IFSC::KKBK => 1013,     // Kotak Mahindra Bank
        IFSC::LAVB => 1009,     // Lakshmi Vilas Bank
        IFSC::SIBL => 1022,     // South Indian Bank
        IFSC::SBBJ => 1023,     // State Bank of Bikaner and Jaipur
        IFSC::SBHY => 1017,     // State Bank of Hyderabad
        IFSC::SBIN => 1014,     // State Bank of India
        IFSC::SBMY => 1021,     // State Bank of Mysore
        IFSC::STBP => 1036,     // State Bank of Patiala
        IFSC::SBTR => 1025,     // State Bank of Travencore
        IFSC::UCBA => 1016,     // UCO Bank
        IFSC::UBIN => 1039,     // Union Bank
        IFSC::VIJB => 1005,     // Vijaya Bank
        IFSC::YESB => 1005,     // Yes Bank

// Custom
        IFSC::BARB => 2000,
        IFSC::PUNB => 2001,
        IFSC::VYSA => 2002,
    );

    public static function getAtomBankCode($ifsc)
    {
        return self::$map[$ifsc];
    }
}