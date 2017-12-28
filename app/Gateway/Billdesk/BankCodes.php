<?php

namespace RZP\Gateway\Billdesk;

use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Processor\Netbanking;

class BankCodes
{
    public static $bankCodeMap = [
        IFSC::ALLA => 'ALB',                    // Allahabad Bank
        IFSC::BKID => 'BOI',                    // Bank of India
        IFSC::CIUB => 'CUB',                    // City Union Bank
        IFSC::UTIB => 'UTI',                    // Axis Bank
        IFSC::ICIC => 'ICI',                    // ICICI Bank
        IFSC::ANDB => 'ADB',                    // Andhra Bank
        IFSC::BBKM => 'BBK',                    // Bank of Bahrain and Kuwait
        IFSC::MAHB => 'BOM',                    // Maharashtra Bank
        IFSC::CBIN => 'CBI',                    // Central Bank of India
        IFSC::CNRB => 'CNB',                    // Canara Bank
        IFSC::COSB => 'COB',                    // Cosmos Coop Bank Ltd
        IFSC::CORP => 'CRP',                    // Corporation Bank Ltd
        IFSC::CSBK => 'CSB',                    // Catholic Syrian Bank Ltd
        IFSC::DEUT => 'DBK',                    // Deutsche Bank Ag
        IFSC::DCBL => 'DCB',                    // Development Credit Bank Ltd
        IFSC::BKDN => 'DEN',                    // Dena Bank
        IFSC::DLXB => 'DLB',                    // Dhanlaxmi Bank Ltd
        IFSC::FDRL => 'FBK',                    // Federal Bank Ltd
        IFSC::IBKL => 'IDB',                    // Idbi Bank Ltd
        IFSC::INDB => 'IDS',                    // Indusind Bank Ltd
        IFSC::IDIB => 'INB',                    // Indian Bank
        IFSC::IOBA => 'IOB',                    // Indian Overseas Bank
        IFSC::JAKA => 'JKB',                    // Jammu And Kashmir Bank Ltd
        IFSC::KARB => 'KBL',                    // Karnataka Bank Ltd
        IFSC::KKBK => '162',                    // Kotak Mahindra Bank
        IFSC::KVBL => 'KVB',                    // Karur Vysya Bank
        IFSC::ORBC => 'OBC',                    // Oriental Bank Of Commerce
        IFSC::PMCB => 'PMC',                    // Punjab And Maharashtra Co-Op Bank Ltd
        IFSC::PSIB => 'PSB',                    // Punjab And Sind Bank
        IFSC::RATN => 'RBL',                    // Ratnakar Bank Ltd. (RBL Bank)
        IFSC::SIBL => 'SIB',                    // South Indian Bank
        IFSC::SVCB => 'SVC',                    // Shamrao Vithal Co-Op Bank Ltd
        IFSC::SRCB => 'SWB',                    // Saraswat Co-Op Bank Ltd
        IFSC::SYNB => 'SYD',                    // Syndicate Bank
        IFSC::TMBL => 'TMB',                    // Tamil Nadu Mercantile Bank
        IFSC::TNSC => 'TNC',                    // Tamilnadu State Apex Co-Op Bank Ltd
        IFSC::UBIN => 'UBI',                    // Union Bank Of India
        IFSC::UCBA => 'UCO',                    // UCO Bank
        IFSC::UTBI => 'UNI',                    // United Bank Of India
        IFSC::VIJB => 'VJB',                    // Vijaya Bank
        IFSC::YESB => 'YBK',                    // Yes Bank
        IFSC::JSBP => 'JSB',                    // Janata Sahkari Bank Ltd Pune
        IFSC::NKGS => 'NKB',                    // Nkgsb Co-Op Bank Ltd
        IFSC::SBBJ => 'SBI',                    // State Bank of Bikaner and Jaipur - Silent redirect to SBI
        IFSC::SBHY => 'SBI',                    // State Bank of Hyderabad - Silent redirect to SBI
        IFSC::SBIN => 'SBI',                    // State Bank of India
        IFSC::SBMY => 'SBI',                    // State Bank of Mysore - Silent redirect to SBI
        IFSC::SCBL => 'SCB',                    // Standard Chartered Bank
        IFSC::STBP => 'SBI',                    // State Bank of Patiala - Silent redirect to SBI
        IFSC::SBTR => 'SBI',                    // State Bank of Travancore - Silent redirect to SBI
        IFSC::DBSS => 'DBS',                    // DBS Bank
        IFSC::IDFB => 'IDN',                    // IDFC Bank
        Netbanking::BARB_R => 'BBR',            // Bank of Baroda - Retail
        Netbanking::PUNB_R => 'PNB',            // Punjab National Bank - Retail
        Netbanking::LAVB_R => 'LVR',            // Laxmi Vilas Bank - Retail
        Netbanking::BARB_C => 'BBC',            // Bank of Baroda - Corporate
        Netbanking::PUNB_C => 'CPN',            // Punjab National Bank - Corporate
        Netbanking::LAVB_C => 'LVC',            // Laxmi Vilas Bank - Corporate
        Netbanking::ICIC_C => 'ICO',            // ICICI Corporate Banking
    ];

    // We are not using Deusctche Bank corporate net-banking currently.
    public static function getBankCode($ifsc, $corporate = false)
    {
        $bankId = self::$bankCodeMap[$ifsc];

        return $bankId;
    }
}
