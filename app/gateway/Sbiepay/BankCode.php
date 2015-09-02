<?php

namespace Gateway\Sbiepay;

use Models\Bank\IFSC;
use Models\Payment\Processor\Netbanking;

class BankCodes
{
    public static $bankCodeMap = array(
        IFSC::SBTR => "8",
        IFSC::CSBK => "33",
        IFSC::JAKA => "38",
        IFSC::MAHB => "40",
        IFSC::DEUT => "61",
        IFSC::VIJB => "29",
        IFSC::PSIB => "41",
        IFSC::SIBL => "43",
        IFSC::BKID => "71",
        IFSC::SBBJ => "9",
        IFSC::SBHY => "10",
        IFSC::SBMY => "11",
        IFSC::STBP => "12",
        IFSC::UTBI => "13",
        IFSC::IDIB => "35",
        IFSC::CIUB => "36",
        IFSC::DLXB => "53",
        IFSC::ICIC => "56",
        IFSC::YESB => "69",
        IFSC::KVBL => "27",
        IFSC::FDRL => "31",
        IFSC::ORBC => "37",
        IFSC::CORP => "50",
        IFSC::INDB => "51",
        IFSC::HDFC => "57",
        IFSC::BBKM => "68",
        IFSC::KARB => "66",
        IFSC::ANDB => "63",
        IFSC::CNRB => "60",
        IFSC::RATN => "67",
        IFSC::UBIN => "62",
        IFSC::CBIN => "64",
        IFSC::PUNB => "59",
        IFSC::IOBA => "65",
        IFSC::SBIN => "7",
        IFSC::VYSA => "28",
        IFSC::IBKL => "34",
        IFSC::BKDN => "39",
        IFSC::DCBL => "42",
        IFSC::TMBL => "45",
        IFSC::SYNB => "52",
        IFSC::CITI => "58",
        IFSC::LAVB => "72",
    );
}