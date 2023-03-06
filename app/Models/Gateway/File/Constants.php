<?php

namespace RZP\Models\Gateway\File;

use RZP\Mail\Base\Constants as MailConstants;

class Constants
{
    const HDFC             = 'hdfc';
    const AXIS             = 'axis';
    const ICICI            = 'icici';
    const KOTAK            = 'kotak';
    const FEDERAL          = 'federal';
    const EQUITAS          = 'equitas';
    const BOB              = 'bob';
    const IDFC             = 'idfc';
    const VIJAYA           = 'vijaya';
    const INDUSIND         = 'indusind';
    const ONECARD          = 'onecard';
    const RBL              = 'rbl';
    const RBL_CORP         = 'rbl_corp';
    const SCBL             = 'scbl';
    const UPI_ICICI        = 'upi_icici';
    const UPI_MINDGATE     = 'upi_mindgate';
    const UPI_SBI          = 'upi_sbi';
    const UPI_AIRTEL       = 'upi_airtel';
    const UPI_YESBANK      = 'upi_yesbank';
    const AIRTEL_MONEY     = 'airtel_money';
    const CSB              = 'csb';
    const AXIS_MIGS        = 'axis_migs';
    const ICIC_FIRST_DATA  = 'icic_first_data';
    const PAYLATER_ICICI   = 'paylater_icici';
    const HDFC_CYBERSOURCE = 'hdfc_cybersource';
    const AXIS_CYBERSOURCE = 'axis_cybersource';
    const HDFC_EMANDATE    = 'hdfc_emandate';
    const HDFC_FSS         = 'hdfc_fss';
    const ENACH_RBL        = 'enach_rbl';
    const OBC              = 'obc';
    const ALLA             = 'allahabad';
    const CANARA           = 'canara';
    const ISG              = 'isg';
    const SBI              = 'sbi';
    const SBI_NCE          = 'sbi_nce';
    const CORPORATION      = 'corporation';
    const YESB             = 'yesb';
    const CUB              = 'cub';
    const IBK              = 'ibk';
    const IDBI             = 'idbi';
    const CITI             = 'citi';
    const CBI              = 'cbi';
    const SIB              = 'sib';
    const FIRST_DATA       = 'first_data';
    const SBIN             = 'sbin';
    const KVB              = 'kvb';
    const SVC              = 'svc';
    const JSB              = 'jsb';
    const PNB              = 'pnb';
    const IOB              = 'iob';
    const JKB              = 'jkb';
    const FSB              = 'fsb';
    const DCB              = 'dcb';
    const UBI              = 'ubi';
    const AUBL             = 'aubl';
    const AUBL_CORP        = 'aubl_corp';
    const KOTAK_CORP       = 'kotak_corp';
    const DLB              = 'dlb';
    const TMB              = 'tmb';
    const NSDL             = 'nsdl';
    const BDBL             = 'bdbl';
    const UCO              = 'uco';
    const DBS              = 'dbs';
    const ICICI_EMI        = 'icici_emi';
    const HSBC             = 'hsbc';
    const SRCB             = 'srcb';
    const KARB             = 'karb';
    const HDFC_CORP        = 'hdfc_corp';
    const UJVN             = 'ujjivan';
    const INDUS_IND_DEBIT  = 'indusind_debit';

    const AXIS_V2          = 'axis_v2';
    const YESB_EARLY_DEBIT = 'yesb_early_debit';

    const ENACH_NPCI_NETBANKING             = 'enach_npci_netbanking';
    const ENACH_NPCI_NETBANKING_EARLY_DEBIT = 'enach_npci_netbanking_early_debit';
    const ENACH_NB_ICICI                    = 'enach_nb_icici';  // deprecated

    const AXIS_PAYSECURE = 'axis_paysecure';

    const PAPER_NACH_CITI       = 'paper_nach_citi';
    const PAPER_NACH_ICICI      = 'paper_nach_icici';

    const COMBINED_NACH_ICICI   = 'combined_nach_icici';
    const COMBINED_NACH_CITI    = 'combined_nach_citi';

    const COMBINED_NACH_CITI_EARLY_DEBIT = 'combined_nach_citi_early_debit';

    const PAPER_NACH_CITI_V2                   = 'paper_nach_citi_v2';
    const COMBINED_NACH_CITI_EARLY_DEBIT_V2    = 'combined_nach_citi_early_debit_v2';

    /**
     * Scrooge file based refunds related constants
     */
    const QUERY_LIMIT              = 50000;
    const SCROOGE_MAX_ATTEMPTS     = 1;
    const FETCH_FROM_SCROOGE_COUNT = 500;

    /**
     * Stores a mapping of valid banks for each file type
     */
    const SUPPORTED_TARGETS = [
        Type::REFUND => [
            self::HDFC,
            self::ICICI,
            self::PAYLATER_ICICI,
            self::CSB,
            self::ALLA,
            self::ISG,
            self::HDFC_EMANDATE,
            self::UPI_SBI,
            self::ICICI_EMI,
            self::HDFC_CORP,
            self::UPI_AIRTEL,
            self::UPI_YESBANK,
        ],
        Type::CLAIM => [
        ],
        Type::EMI => [
            self::INDUS_IND_DEBIT,
            self::INDUSIND,
            self::KOTAK,
            self::AXIS,
            self::RBL,
            self::SCBL,
            self::SBI,
            self::CITI,
            self::BOB,
            self::HSBC,
            self::ONECARD,
            self::ICICI,
            self::YESB,
            self::SBI_NCE,
            self::FEDERAL,
        ],
        Type::COMBINED => [
            self::KOTAK,
            self::AXIS,
            self::FEDERAL,
            self::BOB,
            self::RBL,
            self::RBL_CORP,
            self::INDUSIND,
            self::OBC,
            self::CSB,
            self::ALLA,
            self::CANARA,
            self::EQUITAS,
            self::YESB,
            self::IDFC,
            self::VIJAYA,
            self::CORPORATION,
            self::SIB,
            self::CUB,
            self::IDBI,
            self::SBIN,
            self::CBI,
            self::KVB,
            self::SCBL,
            self::SVC,
            self::JSB,
            self::PNB,
            self::IOB,
            self::FSB,
            self::JKB,
            self::DCB,
            self::UBI,
            self::IBK,
            self::AUBL,
            self::AUBL_CORP,
            self::KOTAK_CORP,
            self::DLB,
            self::TMB,
            self::NSDL,
            self::BDBL,
            self::SRCB,
            self::KARB,
            self::UCO,
            self::DBS,
            self::HDFC_CORP,
            self::UJVN,
            self::INDUS_IND_DEBIT,
        ],
        Type::CARDSETTLEMENT => [
            self::AXIS
        ],
        Type::EMANDATE_CANCEL => [
            self::ENACH_NPCI_NETBANKING,
            self::ENACH_RBL,
        ],
        Type::EMANDATE_REGISTER => [
            self::HDFC,
            self::ENACH_RBL,
        ],
        Type::EMANDATE_DEBIT => [
            self::HDFC,
            self::AXIS,
            self::ENACH_RBL,
            self::SBI,
            self::ENACH_NPCI_NETBANKING,
            self::ENACH_NPCI_NETBANKING_EARLY_DEBIT,
            self::AXIS_V2,
            self::YESB,
            self::YESB_EARLY_DEBIT
            //self::ENACH_NB_ICICI,  deprecated
        ],
        Type::NACH_DEBIT => [
            self::PAPER_NACH_CITI,
            self::COMBINED_NACH_ICICI,
            self::COMBINED_NACH_CITI_EARLY_DEBIT,
            self::PAPER_NACH_CITI_V2,
            self::COMBINED_NACH_CITI_EARLY_DEBIT_V2
        ],
        Type::NACH_REGISTER => [
            self::PAPER_NACH_CITI,
            self::PAPER_NACH_ICICI,
        ],
        Type::NACH_CANCEL => [
            self::COMBINED_NACH_ICICI,
            self::COMBINED_NACH_CITI,
        ],
        Type::REFUND_FAILED => [
            'All',
            self::UPI_ICICI,
            self::UPI_MINDGATE,
            self::AIRTEL_MONEY,
            self::AXIS_MIGS,
            self::ICIC_FIRST_DATA,
            self::HDFC_CYBERSOURCE,
            self::HDFC_FSS,
            self::AXIS_CYBERSOURCE,
        ],
        Type::PARESDATA => [
           self::FIRST_DATA,
        ],
        Type::CAPTURE => [
            self::AXIS_PAYSECURE,
        ],
    ];

    const TYPE_SENDER_MAPPING = [
        Type::REFUND            => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::CLAIM             => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::COMBINED          => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::EMI               => MailConstants::MAIL_ADDRESSES[MailConstants::EMI],
        Type::EMANDATE_REGISTER => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::EMANDATE_DEBIT    => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::EMANDATE_CANCEL   => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::NACH_DEBIT        => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::NACH_REGISTER     => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::NACH_CANCEL       => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::REFUND_FAILED     => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::PARESDATA         => MailConstants::MAIL_ADDRESSES[MailConstants::GATEWAY_POD],
        Type::CAPTURE           => MailConstants::MAIL_ADDRESSES[MailConstants::CAPTURE],
        Type::CARDSETTLEMENT    => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
    ];

    const RECIPIENTS_MAP = [
        Type::REFUND => [
            self::HDFC          => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            // todo: Fix the receipients
            self::HDFC_EMANDATE => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            self::ICICI         => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::PAYLATER_ICICI=> ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::ISG           => ['settlements@razorpay.com'],
            self::UPI_SBI       => ['refunds@razorpay.com'],
            self::CBI           => ['cbi.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::AUBL          => ['ausf-netbanking-refunds@razorpay.com'],
            self::AUBL_CORP     => ['ausf-corp-netbanking-refunds@razorpay.com'],
            self::ICICI_EMI     => ['icicicards.emi@razorpay.com'],
            self::HDFC_CORP     => [],
        ],

        Type::COMBINED => [
            self::CANARA      => ['canara.netbanking.refunds@razorpay.com'],
            self::AXIS        => ['axis.netbanking.refunds@razorpay.com'],
            self::KOTAK       => ['settlements@razorpay.com'],
            self::RBL         => ['rbl.netbanking.refunds@razorpay.com'],
            self::FEDERAL     => ['federal.netbanking.refunds@razorpay.com'],
            self::BOB         => ['bob.netbanking.refunds@razorpay.com'],
            self::INDUSIND    => ['indusind.netbanking.refunds@razorpay.com'],
            self::OBC         => ['obc.netbanking.refunds@razorpay.com'],
            self::CSB         => ['csb.netbanking.refunds@razorpay.com'],
            self::EQUITAS     => ['equitas.netbanking.refunds@razorpay.com'],
            self::IDFC        => ['idfc.netbanking.refunds@razorpay.com'],
            self::ALLA        => ['settlements@razorpay.com','imps.recon@allahabadbank.in'],
            self::CORPORATION => ['corporation.netbanking.refunds@razorpay.com'],
            self::VIJAYA      => ['vijaya.netbanking.refunds@razorpay.com'],
            //TODO add this value
            self::YESB        => ['yesb.netbanking.refunds@razorpay.com'],
            self::SIB         => ['sib.netbanking.refunds@razorpay.com'],
            self::CUB         => ['cub.netbanking.refunds@razorpay.com'],
            self::IDBI        => ['idbi.netbanking.refunds@razorpay.com'],
            self::SBIN        => ['sbi.netbanking.refunds@razorpay.com'],
            self::CBI         => ['cbi.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::KVB         => ['kvb.netbanking.refunds@razorpay.com'],
            self::SCBL        => ['scb.netbanking.claims@razorpay.com'],
            self::SVC         => ['svc-netbanking-refunds@razorpay.com'],
            self::JSB         => ['jsb-netbanking.refunds@razorpay.com'],
            self::PNB         => ['pnb-netbanking-claims@razorpay.com'],
            self::IOB         => ['iob-netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::JKB         => ['jkb-netbanking-claims@razorpay.com'],
            self::FSB         => ['fsb-netbanking-refunds@razorpay.com'],
            self::DCB         => ['dcb-netbanking-refunds@razorpay.com'],
            self::UBI         => ['ubi-netbanking-refunds@razorpay.com'],
            self::IBK         => ['ibk-netbanking-refunds@razorpay.com'],
            self::AUBL        => ['ausf-netbanking-refunds@razorpay.com'],
            self::AUBL_CORP   => ['ausf-corp-netbanking-refunds@razorpay.com'],
            self::KOTAK_CORP  => ['kotak-corp-netbanking-refunds@razorpay.com'],
            self::DLB         => ['dlb-netbanking-refunds@razorpay.com'],
            self::TMB         => ['tmb-netbanking-refunds@razorpay.com'],
            self::NSDL        => ['nsdl-netbanking-refunds@razorpay.com'],
            self::BDBL        => ['bdbl-netbanking-refunds@razorpay.com'],
            self::SRCB        => ['srcb-netbanking-refunds@razorpay.com'],
            self::KARB        => [],
            self::UCO         => ['uco-netbanking-refunds@razorpay.com'],
            self::DBS         => ['dbs-netbanking-claims@razorpay.com'],
            self::HDFC_CORP   => [],
            self::UJVN        => [],
            self::RBL_CORP    => [],
        ],

        Type::EMANDATE_REGISTER => [
            self::HDFC      => ['hdfc.emandate@razorpay.com', 'amit.salvi@hdfcbank.com'],
            self::ENACH_RBL => ['rbl.emandate@razorpay.com'],
        ],

        Type::EMANDATE_CANCEL => [
            self::ENACH_NPCI_NETBANKING => [''],
            self::ENACH_RBL => [''],
        ],

        Type::EMANDATE_DEBIT => [
            self::HDFC                  => ['hdfc.emandate@razorpay.com', 'amit.salvi@hdfcbank.com'],
            self::AXIS                  => ['axis.emandate@razorpay.com'],
            self::AXIS_V2               => ['axis.emandate@razorpay.com'],
            self::ENACH_RBL             => ['rbl.emandate@razorpay.com'],
            self::ENACH_NPCI_NETBANKING => [''],
            self::YESB                  => [''],
            self::YESB_EARLY_DEBIT      => [''],
            self::ENACH_NB_ICICI        => [''],
            self::SBI                   => [''],
        ],

        Type::NACH_DEBIT => [
            self::PAPER_NACH_CITI                  => [''],
            self::COMBINED_NACH_ICICI              => [''],
            self::COMBINED_NACH_CITI_EARLY_DEBIT   => [''],
            self::PAPER_NACH_CITI_V2               => [''],
        ],

        Type::NACH_REGISTER => [
            self::PAPER_NACH_CITI                  => [''],
            self::PAPER_NACH_ICICI                 => [''],
        ],

        Type::NACH_CANCEL => [
            self::COMBINED_NACH_ICICI => [''],
            self::COMBINED_NACH_CITI  => [''],
        ],


        Type::EMI => [
            self::AXIS     => ['axiscards.emi@razorpay.com'],
            self::HSBC     => ['hsbc-cards.emi@razorpay.com'],
            self::INDUSIND => ['indusind.emi@razorpay.com'],
            self::KOTAK    => ['kotakcards.emi@razorpay.com'],
            self::RBL      => ['Rblcards.emi@razorpay.com'],
            self::SCBL     => ['scbl.emi@razorpay.com'],
            self::SBI      => ['emi.ops@sbicard.com', 'deepak.semwal@sbicard.com', 'settlements@razorpay.com', 'Divya.Verma@sbicard.com', 'albin.george@razorpay.com'],
            self::SBI_NCE  => ['emi.ops@sbicard.com', 'deepak.semwal@sbicard.com', 'settlements@razorpay.com', 'Divya.Verma@sbicard.com'],
            self::CITI     => ['emi-citibank@razorpay.com'],
            self::BOB      => ['bob.cc.emi@razorpay.com'],
            self::ICICI    => ['icicicards.emi@razorpay.com'],
            self::YESB     => ['yesbcards.emi@razorpay.com'],
        ],

        Type::REFUND_FAILED => [
            self::UPI_ICICI        => ['supportteam@razorpay.com'],
            self::UPI_MINDGATE     => ['supportteam@razorpay.com'],
            self::AIRTEL_MONEY     => ['supportteam@razorpay.com'],
            self::AXIS_MIGS        => ['supportteam@razorpay.com'],
            self::ICIC_FIRST_DATA  => ['supportteam@razorpay.com'],
            self::HDFC_CYBERSOURCE => ['supportteam@razorpay.com'],
            self::AXIS_CYBERSOURCE => ['supportteam@razorpay.com'],
            self::HDFC_FSS         => ['supportteam@razorpay.com'],
        ],

        Type::PARESDATA => [
            self::FIRST_DATA    => [''],
        ],

        Type::CAPTURE => [
            self::AXIS_PAYSECURE    => ['example@axisbank.com'],
        ],
    ];

    const ASYNC_GATEWAYS = [
        self::SBIN
    ];

    // Upload Refund file for SBI related changes
    const MANUAL_FILE_SUPPORTED_GATEWAYS = [
        self::UPI_SBI
    ];

    const ID                 = 'id';
    const FILE_ID            = 'file_id';
    const GATEWAY            = 'gateway';
    const FILE               = 'file';
    const MANUAL_REFUND_FILE = 'manual_refund_file';
    const STORAGE_FILE_PATH  = [
        self::UPI_SBI => 'sbi/sbi_upi_refund/outgoing'
    ];
    const SUCCESS            = 'success';

    // UPS authorize entity columns
    const CUSTOMER_REFERENCE    = 'customer_reference';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const MERCHANT_REFERENCE    = 'merchant_reference';
    const GATEWAY_DATA          = 'gateway_data';
    const PAYMENT_ID            = 'payment_id';
    const GATEWAY_REFERENCE     = 'gateway_reference';
    const NPCI_TXN_ID           = 'npci_txn_id';

    // Models in UPS
    const AUTHORIZE = 'authorize';

    // Entity fetch request paramenters
    const MODEL             = 'model';
    const REQUIRED_FIELDS   = 'required_fields';
    const COLUMN_NAME       = 'column_name';
    const VALUES            = 'values';

    // Actions
    const MULTIPLE_ENTITY_FETCH = 'multiple_entity_fetch';

    // file constants
    const FILE_SENT          = 'file_sent';
    const FILE_FAILED        = 'file_failed';
    const FILE_TIMEOUT       = 'file_timeout';
    const FILE_UNKNOWN       = 'file_unknown';
}
