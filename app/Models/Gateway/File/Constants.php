<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Payment\Gateway;
use RZP\Mail\Base\Constants as MailConstants;

class Constants
{
    const HDFC     = 'hdfc';
    const AXIS     = 'axis';
    const ICICI    = 'icici';
    const KOTAK    = 'kotak';
    const FEDERAL  = 'federal';
    const INDUSIND = 'indusind';
    const RBL      = 'rbl';

    /**
     * Stores a mapping of valid bank corresponding to each gateway, and also the list
     * of gateways supported for a particular type. Here the value ALL represents that
     * payments across all gateways need to be considered while generating the file
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
        ],
        Type::COMBINED => [
            self::KOTAK,
            self::AXIS,
            self::FEDERAL,
            self::RBL,
            self::INDUSIND,
        ],
    ];

    const TYPE_SENDER_MAPPING = [
        Type::REFUND   => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::CLAIM    => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::COMBINED => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::EMI      => MailConstants::MAIL_ADDRESSES[MailConstants::EMI],
    ];

    const RECIPIENTS_MAP = [
        Type::REFUND => [
            self::HDFC  => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
            self::ICICI => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        ]
    ];
}
