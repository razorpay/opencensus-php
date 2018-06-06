<?php

namespace RZP\Gateway\Atom;

use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Processor\Netbanking;

class Bank
{
    const ATOM = '2001';

    // For SBI associated bank,
    // We are using the same bank code as SBIN
    protected static $map = [
        IFSC::ANDB         => 1058,     // Andhra bank
        IFSC::BKID         => 1012,     // Bank of India
        IFSC::MAHB         => 1033,     // Bank of Maharashtra
        IFSC::CNRB         => 1030,     // Canara Bank NB
        IFSC::CSBK         => 1031,     // Catholic Syrian Bank
        IFSC::CBIN         => 1028,     // Central Bank of India
        IFSC::CORP         => 1004,     // Corporation Bank
        IFSC::DCBL         => 1027,     // DCB Bank, Development Credit Bank
        IFSC::DEUT         => 1024,     // Deustche Bank
        IFSC::DLXB         => 1038,     // Dhanlaxmi Bank
        IFSC::ESFB         => 1063,     // Equitas Small Finance Bank
        IFSC::FDRL         => 1019,     // Federal Bank
        IFSC::IBKL         => 1007,     // IDBI Bank
        IFSC::IDIB         => 1026,     // Indian Bank
        IFSC::IOBA         => 1029,     // Indian Overseas Bank
        IFSC::INDB         => 1015,     // IndusInd Bank
        IFSC::JAKA         => 1001,     // Jammu and Kashmir Bank
        IFSC::KARB         => 1008,     // Karnataka Bank
        IFSC::KVBL         => 1018,     // Karur Vysya Bank
        Netbanking::LAVB_R => 1009,     // Lakshmi Vilas Bank
        IFSC::ORBC         => 1035,     // Oriental Bank of Commerce
        IFSC::PMCB         => 1065,     // Punjab & Maharashtra Co-operative Bank
        IFSC::PSIB         => 1055,     // Punjab & Sind Bank
        Netbanking::PUNB_R => 1049,     // Punjab National Bank[Retail]
        IFSC::RATN         => 1066,     // RBL Bank
        IFSC::SRCB         => 1053,     // Saraswat Co-operative Bank
        IFSC::SIBL         => 1022,     // South Indian Bank
        IFSC::SBIN         => 1014,     // State Bank of India
        IFSC::TMBL         => 1044,     // Tamilnadu Mercantile Bank
        IFSC::UCBA         => 1057,     // UCO Bank
        IFSC::UBIN         => 1016,     // Union Bank
        IFSC::UTBI         => 1041,     // United Bank of India
        IFSC::VIJB         => 1039,     // Vijaya Bank
        IFSC::ALLA         => 1056,     // Allahabad Bank
        IFSC::UTIB         => 1003,     // Axis Bank
        Netbanking::BARB_C => 1045,     // Bank of Baroda Corporate
        Netbanking::BARB_R => 1046,     // Bank of Baroda Retail
        IFSC::CNRB         => 1030,     // Canara Bank
        IFSC::SBBJ         => 1014,
        IFSC::SBHY         => 1014,
        IFSC::SBMY         => 1014,
        IFSC::STBP         => 1014,
        IFSC::SBTR         => 1014,
    ];

    public static function getCode(string $ifsc)
    {
        return self::$map[$ifsc];
    }
}

