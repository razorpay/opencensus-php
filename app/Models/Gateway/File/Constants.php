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
    const BOB              = 'bob';
    const INDUSIND         = 'indusind';
    const RBL              = 'rbl';
    const SCBL             = 'scbl';
    const UPI_ICICI        = 'upi_icici';
    const AIRTEL_MONEY     = 'airtel_money';
    const CSB              = 'csbk';
    const AXIS_MIGS        = 'axis_migs';
    const ICIC_FIRST_DATA  = 'icic_first_data';
    const HDFC_CYBERSOURCE = 'hdfc_cybersource';
    const AXIS_CYBERSOURCE = 'axis_cybersource';
    const HDFC_FSS         = 'hdfc_fss';
    const ENACH_RBL        = 'enach_rbl';

    /**
     * Stores a mapping of valid banks for each file type
     */
    const SUPPORTED_TARGETS = [
        Type::REFUND => [
            self::HDFC,
            self::ICICI,
            self::CSB,
        ],
        Type::CLAIM => [
        ],
        Type::EMI => [
            self::INDUSIND,
            self::KOTAK,
            self::AXIS,
            self::RBL,
            self::SCBL,
        ],
        Type::COMBINED => [
            self::KOTAK,
            self::AXIS,
            self::FEDERAL,
            self::BOB,
            self::RBL,
            self::INDUSIND,
        ],
        Type::EMANDATE_REGISTER => [
            self::HDFC,
        ],
        Type::EMANDATE_DEBIT => [
            self::HDFC,
            self::AXIS,
            self::ENACH_RBL,
        ],
        Type::REFUND_FAILED => [
            'All',
            self::UPI_ICICI,
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
            self::HDFC  => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            self::ICICI => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
            self::CSB   => ['csb.netbanking.refunds@razorpay.com'],
        ],

        Type::COMBINED => [
            self::AXIS     => ['axis.netbanking.refunds@razorpay.com'],
            self::KOTAK    => ['settlements@razorpay.com'],
            self::RBL      => ['rbl.netbanking.refunds@razorpay.com'],
            self::FEDERAL  => ['federal.netbanking.refunds@razorpay.com'],
            self::BOB      => ['bob.netbanking.refunds@razorpay.com'],
            self::INDUSIND => ['indusind.netbanking.refunds@razorpay.com'],
        ],

        Type::EMANDATE_REGISTER => [
            self::HDFC => ['hdfc.emandate@razorpay.com'],
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
        ],

        Type::REFUND_FAILED => [
            self::UPI_ICICI        => ['supportteam@razorpay.com'],
            self::AIRTEL_MONEY     => ['supportteam@razorpay.com'],
            self::AXIS_MIGS        => ['supportteam@razorpay.com'],
            self::ICIC_FIRST_DATA  => ['supportteam@razorpay.com'],
            self::HDFC_CYBERSOURCE => ['supportteam@razorpay.com'],
            self::AXIS_CYBERSOURCE => ['supportteam@razorpay.com'],
            self::HDFC_FSS         => ['supportteam@razorpay.com'],
        ],
    ];
}
