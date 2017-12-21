<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Payment\Gateway;
use RZP\Mail\Base\Constants as MailConstants;

class Constants
{
    const HDFC            = 'hdfc';
    const AXIS            = 'axis';
    const ICICI           = 'icici';
    const KOTAK           = 'kotak';
    const FEDERAL         = 'federal';
    const INDUSIND        = 'indusind';
    const RBL             = 'rbl';
    const SCBL            = 'scbl';
    const UPI_ICICI       = 'upi_icici';

    /**
     * Stores a mapping of valid banks for each file type
     */
    const SUPPORTED_TARGETS = [
        Type::REFUND => [
            self::HDFC,
            self::ICICI
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
            self::RBL,
            self::INDUSIND,
        ],
        Type::EMANDATE_REGISTER => [
            self::HDFC,
        ],
        Type::EMANDATE_DEBIT => [
            self::HDFC,
            self::AXIS,
        ],
        TYPE::REFUND_FAILED => [
            self::UPI_ICICI,
        ]
    ];

    const TYPE_SENDER_MAPPING = [
        Type::REFUND            => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::CLAIM             => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::COMBINED          => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::EMI               => MailConstants::MAIL_ADDRESSES[MailConstants::EMI],
        Type::EMANDATE_REGISTER => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        Type::EMANDATE_DEBIT    => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE],
        TYPE::REFUND_FAILED     => 'tessy.john@razorpay.com',
    ];

    const RECIPIENTS_MAP = [
        Type::REFUND => [
            self::HDFC  => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            self::ICICI => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        ],

        Type::COMBINED => [
            self::AXIS     => ['axis.netbanking.refunds@razorpay.com'],
            self::KOTAK    => ['settlements@razorpay.com'],
            self::RBL      => ['rbl.netbanking.refunds@razorpay.com'],
            self::FEDERAL  => ['federal.netbanking.refunds@razorpay.com'],
            self::INDUSIND => ['indusind.netbanking.refunds@razorpay.com'],
        ],

        Type::EMANDATE_REGISTER => [
            self::HDFC => ['hdfc.emandate@razorpay.com'],
        ],

        Type::EMANDATE_DEBIT => [
            self::HDFC => ['hdfc.emandate@razorpay.com'],
            self::AXIS => ['axis.emandate@razorpay.com'],
        ],

        Type::EMI => [
            self::AXIS     => ['axiscards.emi@razorpay.com'],
            self::INDUSIND => ['indusind.emi@razorpay.com'],
            self::KOTAK    => ['kotakcards.emi@razorpay.com'],
            self::RBL      => ['Rblcards.emi@razorpay.com'],
            self::SCBL     => ['scbl.emi@razorpay.com'],
        ],

        TYPE::REFUND_FAILED => [
            self::UPI_ICICI => ['tessy.john@razorpay.com'],
        ],

    ];
}
