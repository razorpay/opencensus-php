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
        IFSC::ABNA         => 1050,     // Royal Bank Of Scotland
        IFSC::ALLA         => 1026,     // Allahabad Bank -> changed to Indian Bank
        IFSC::ANDB         => 1016,     // Andhra bank
        IFSC::BKID         => 1012,     // Bank of India Retail
        IFSC::CBIN         => 1028,     // Central Bank of India
        IFSC::CIUB         => 1020,     // City Union Bank
        IFSC::CNRB         => 1030,     // Canara Bank
        IFSC::CNRB         => 1030,     // Canara Bank NB
        IFSC::CORP         => 1016,     // Corporation Bank
        IFSC::CSBK         => 1031,     // Catholic Syrian Bank
        IFSC::DBSS         => 1047,     // DBS Bank Ltd
        IFSC::DCBL         => 1027,     // DCB Bank, Development Credit Bank
        IFSC::DEUT         => 1024,     // Deustche Bank
        IFSC::DLXB         => 1038,     // Dhanlaxmi Bank
        IFSC::ESFB         => 1063,     // Equitas Small Finance Bank
        IFSC::FDRL         => 1019,     // Federal Bank
        IFSC::IBKL         => 1007,     // IDBI Bank
        IFSC::IDIB         => 1026,     // Indian Bank
        IFSC::INDB         => 1015,     // IndusInd Bank
        IFSC::IOBA         => 1029,     // Indian Overseas Bank
        IFSC::JAKA         => 1001,     // Jammu and Kashmir Bank
        IFSC::KARB         => 1008,     // Karnataka Bank
        IFSC::KVBL         => 1018,     // Karur Vysya Bank
        IFSC::MAHB         => 1033,     // Bank of Maharashtra
        IFSC::ORBC         => 1035,     // Oriental Bank of Commerce
        IFSC::PMCB         => 1065,     // Punjab & Maharashtra Co-operative Bank
        IFSC::PSIB         => 1055,     // Punjab & Sind Bank
        IFSC::RATN         => 1066,     // RBL Bank
        IFSC::SBBJ         => 1014,
        IFSC::SBHY         => 1014,
        IFSC::SBIN         => 1014,     // State Bank of India
        IFSC::SBMY         => 1014,
        IFSC::SBTR         => 1014,
        IFSC::SCBL         => 1051,
        IFSC::SIBL         => 1022,     // South Indian Bank
        IFSC::SRCB         => 1053,     // Saraswat Co-operative Bank
        IFSC::STBP         => 1014,
        IFSC::TMBL         => 1044,     // Tamilnadu Mercantile Bank
        IFSC::UBIN         => 1016,     // Union Bank
        IFSC::UCBA         => 1057,     // UCO Bank
        IFSC::UTBI         => 1049,     // United Bank of India
        IFSC::UTIB         => 1003,     // Axis Bank
        IFSC::VIJB         => 1039,     // Vijaya Bank
        IFSC::YESB         => 1005,
        IFSC::HDFC         => 1006,
        IFSC::ICIC         => 1002,
        Netbanking::BKID_C => 1012,     // Bank of India Corporate
        Netbanking::LAVB_R => 1047,     // Lakshmi Vilas Bank -> redirects to DBS Bank Ltd
        Netbanking::PUNB_R => 1049,     // Punjab National Bank[Retail]
    ];

    public static function getCode(string $ifsc)
    {
        return self::$map[$ifsc];
    }
}
