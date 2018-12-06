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
    const RBL              = 'rbl';
    const SCBL             = 'scbl';
    const UPI_ICICI        = 'upi_icici';
    const UPI_MINDGATE     = 'upi_mindgate';
    const AIRTEL_MONEY     = 'airtel_money';
    const CSB              = 'csb';
    const AXIS_MIGS        = 'axis_migs';
    const ICIC_FIRST_DATA  = 'icic_first_data';
    const HDFC_CYBERSOURCE = 'hdfc_cybersource';
    const AXIS_CYBERSOURCE = 'axis_cybersource';
    const HDFC_EMANDATE    = 'hdfc_emandate';
    const HDFC_FSS         = 'hdfc_fss';
    const ENACH_RBL        = 'enach_rbl';
    const OBC              = 'obc';
    const CANARA           = 'canara';
    const ISG              = 'isg';
    const SBI              = 'sbi';
    const CORPORATION      = 'corporation';

    /**
     * Stores a mapping of valid banks for each file type
     */
    const SUPPORTED_TARGETS = [
        Type::REFUND => [
            self::HDFC,
            self::ICICI,
            self::CSB,
            self::ISG,
            self::HDFC_EMANDATE,
        ],
        Type::CLAIM => [
        ],
        Type::EMI => [
            self::INDUSIND,
            self::KOTAK,
            self::AXIS,
            self::RBL,
            self::SCBL,
            self::SBI,
        ],
        Type::COMBINED => [
            self::KOTAK,
            self::AXIS,
            self::FEDERAL,
            self::BOB,
            self::RBL,
            self::INDUSIND,
            self::OBC,
            self::CSB,
            self::CANARA,
            self::EQUITAS,
            self::IDFC,
            self::VIJAYA,
            self::CORPORATION,
        ],
        Type::EMANDATE_REGISTER => [
            self::HDFC,
            self::ENACH_RBL,
        ],
        Type::EMANDATE_DEBIT => [
            self::HDFC,
            self::AXIS,
            self::ENACH_RBL,
        ],
        Type::REFUND_FAILED => [
            self::UPI_ICICI,
            self::UPI_MINDGATE,
            self::AIRTEL_MONEY,
            self::AXIS_MIGS,
            self::ICIC_FIRST_DATA,
            self::HDFC_CYBERSOURCE,
            self::HDFC_FSS,
            self::AXIS_CYBERSOURCE,
        ],
    ];

    const TYPE_SENDER_MAPPING = [
        Type::REFUND            => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::CLAIM             => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::COMBINED          => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::EMI               => MailConstants::MAIL_ADDRESSES[MailConstants::EMI],
        Type::EMANDATE_REGISTER => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::EMANDATE_DEBIT    => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::REFUND_FAILED     => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
    ];

    const RECIPIENTS_MAP = [
        Type::REFUND => [
            self::HDFC          => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            // todo: Fix the receipients
            self::HDFC_EMANDATE => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            self::ICICI         => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::ISG           => ['settlements@razorpay.com'],
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
            self::CORPORATION => ['corporation.netbanking.refunds@razorpay.com'],
            self::VIJAYA      => ['vijaya.netbanking.refunds@razorpay.com']
        ],

        Type::EMANDATE_REGISTER => [
            self::HDFC      => ['hdfc.emandate@razorpay.com'],
            self::ENACH_RBL => ['rbl.emandate@razorpay.com'],
        ],

        Type::EMANDATE_DEBIT => [
            self::HDFC      => ['hdfc.emandate@razorpay.com'],
            self::AXIS      => ['axis.emandate@razorpay.com'],
            self::ENACH_RBL => ['rbl.emandate@razorpay.com'],
        ],

        Type::EMI => [
            self::AXIS     => ['axiscards.emi@razorpay.com'],
            self::INDUSIND => ['indusind.emi@razorpay.com'],
            self::KOTAK    => ['kotakcards.emi@razorpay.com'],
            self::RBL      => ['Rblcards.emi@razorpay.com'],
            self::SCBL     => ['scbl.emi@razorpay.com'],
            self::SBI      => [''],
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
    ];
}
