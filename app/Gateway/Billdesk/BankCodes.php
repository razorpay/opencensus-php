<?php

namespace RZP\Gateway\Billdesk;

use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Processor\Netbanking;

class BankCodes
{
    public static $bankCodeMap = [
        IFSC::ABPB => 'ABP',
        IFSC::ALLA => 'ALB',                    // Allahabad Bank
        IFSC::ANDB => 'UBI',                    // Andhra Bank - migrated to UBIN
        IFSC::AUBL => 'AUB',                    // AU Small Finance Bank
        IFSC::BACB => 'BCB',                    // Bassien Catholic Bank
        IFSC::BBKM => 'BBK',                    // Bank of Bahrain and Kuwait
        IFSC::BDBL => 'BDN',                    // Bandhan Bank
        IFSC::BKDN => 'DEN',                    // Dena Bank
        IFSC::BKID => 'BOI',                    // Bank of India
        IFSC::CBIN => 'CBI',                    // Central Bank of India
        IFSC::CIUB => 'CUB',                    // City Union Bank
        IFSC::CNRB => 'CNB',                    // Canara Bank
        IFSC::CORP => 'UBI',                    // Corporation Bank Ltd
        IFSC::COSB => 'COB',                    // Cosmos Coop Bank Ltd
        IFSC::CSBK => 'CSB',                    // Catholic Syrian Bank Ltd
        IFSC::DBSS => 'DBS',                    // DBS Bank
        IFSC::DCBL => 'DCB',                    // Development Credit Bank Ltd
        IFSC::DEUT => 'DBK',                    // Deutsche Bank Ag
        IFSC::DLXB => 'DLB',                    // Dhanlaxmi Bank Ltd
        IFSC::ESAF => 'ESF',                    // ESAF Small Finance Bank
        IFSC::ESFB => 'EQB',                    // Equitas Small Finance Bank
        IFSC::FDRL => 'FBK',                    // Federal Bank Ltd
        IFSC::HDFC => 'HDF',                    // HDFC Bank
        IFSC::HSBC => 'HSB',                    // HSBC Bank
        IFSC::IBKL => 'IDB',                    // Idbi Bank Ltd
        IFSC::ICIC => 'ICI',                    // ICICI Bank
        IFSC::IDFB => 'IDN',                    // IDFC Bank
        IFSC::IDIB => 'INB',                    // Indian Bank
        IFSC::INDB => 'IDS',                    // Indusind Bank Ltd
        IFSC::IOBA => 'IOB',                    // Indian Overseas Bank
        IFSC::JAKA => 'JKB',                    // Jammu And Kashmir Bank Ltd
        IFSC::JSBP => 'JSB',                    // Janata Sahkari Bank Ltd Pune
        IFSC::KARB => 'KBL',                    // Karnataka Bank Ltd
        IFSC::KCCB => 'KLB',                    // The Kalupur Commercial Bank
        IFSC::KJSB => 'KJB',                    // Kalyan Janata Sahakari Bank
        IFSC::KKBK => '162',                    // Kotak Mahindra Bank
        IFSC::KVBL => 'KVB',                    // Karur Vysya Bank
        IFSC::MAHB => 'BOM',                    // Maharashtra Bank
        IFSC::MSNU => 'MSB',                    // Mehsana Urban Bank
        IFSC::NESF => 'NEB',                    // North East Small Finance Bank
        IFSC::NKGS => 'NKB',                    // Nkgsb Co-Op Bank Ltd
        IFSC::ORBC => 'PNB',                    // Oriental Bank Of Commerce
        IFSC::PMCB => 'PMC',                    // Punjab And Maharashtra Co-Op Bank Ltd
        IFSC::PSIB => 'PSB',                    // Punjab And Sind Bank
        IFSC::RATN => 'RBL',                    // Ratnakar Bank Ltd. (RBL Bank)
        IFSC::SBBJ => 'SBI',                    // State Bank of Bikaner and Jaipur - Silent redirect to SBI
        IFSC::SBHY => 'SBI',                    // State Bank of Hyderabad - Silent redirect to SBI
        IFSC::SBIN => 'SBI',                    // State Bank of India
        IFSC::SBMY => 'SBI',                    // State Bank of Mysore - Silent redirect to SBI
        IFSC::SBTR => 'SBI',                    // State Bank of Travancore - Silent redirect to SBI
        IFSC::SCBL => 'SCB',                    // Standard Chartered Bank
        IFSC::SIBL => 'SIB',                    // South Indian Bank
        IFSC::SRCB => 'SWB',                    // Saraswat Co-Op Bank Ltd
        IFSC::STBP => 'SBI',                    // State Bank of Patiala - Silent redirect to SBI
        IFSC::SURY => 'SRB',                    // Suryoday Small Finance Bank
        IFSC::SVCB => 'SVC',                    // Svc Co-Op Bank Ltd
        IFSC::SYNB => 'CNB',                    // Syndicate Bank - migrated to Canara bank
        IFSC::TBSB => 'TBB',                    // Thane Bharat Sahakari Bank Ltd
        IFSC::TJSB => 'TJB',                    // TJSB Bank
        IFSC::TMBL => 'TMB',                    // Tamil Nadu Mercantile Bank
        IFSC::TNSC => 'TNC',                    // Tamilnadu State Apex Co-Op Bank Ltd
        IFSC::UBIN => 'UBI',                    // Union Bank Of India
        IFSC::UCBA => 'UCO',                    // UCO Bank
        IFSC::UTBI => 'PNB',                    // United Bank Of India
        IFSC::UTIB => 'UTI',                    // Axis Bank
        IFSC::VARA => 'VRB',                    // Varachha Co-operative Bank Limited
        IFSC::VIJB => 'VJB',                    // Vijaya Bank
        IFSC::YESB => 'YBK',                    // Yes Bank
        IFSC::ZCBL => 'ZOB',                    // Zoroastrian Bank
       // Netbanking::ANDB_C => 'UBI',            // Andhra Bank Corporate - migrated to UBI
        Netbanking::BARB_C => 'BBC',            // Bank of Baroda - Corporate
        Netbanking::BARB_R => 'BBR',            // Bank of Baroda - Retail
        Netbanking::DLXB_C => 'DL2',            // Dhanlakshmi Bank Corporate
        Netbanking::IBKL_C => 'IDC',            // IDBI Corporate
        Netbanking::ICIC_C => 'ICO',            // ICICI Corporate Banking
        Netbanking::LAVB_C => 'DBS',            // Laxmi Vilas Bank - Corporate -> Redirects to DBS Bank Ltd
        Netbanking::LAVB_R => 'DBS',            // Laxmi Vilas Bank - Retail -> Redirects to DBS Bank Ltd
        Netbanking::PUNB_C => 'CPN',            // Punjab National Bank - Corporate
        Netbanking::PUNB_R => 'PNB',            // Punjab National Bank - Retail
        Netbanking::RATN_C => 'RTC',            // RBL Bank Limited Corporate
        Netbanking::SVCB_C => 'SV2',            // Svc Bank Corporate
        Netbanking::YESB_C => 'YBC',            // Yes Bank Corporate
    ];

    // We are not using Deusctche Bank corporate net-banking currently.
    public static function getBankCode($ifsc, $corporate = false)
    {
        $bankId = self::$bankCodeMap[$ifsc];

        return $bankId;
    }
}
