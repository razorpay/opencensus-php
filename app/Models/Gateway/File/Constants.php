<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Base\Constants as MailConstants;

class Constants
{
    const ALL = 'ALL';

    /**
     * Stores a mapping of valid bank corresponding to each gateway, and also the list
     * of gateways supported for a particular type. Here the value ALL represents that
     * payments across all gateways need to be considered while generating the file
     */
    const GATEWAY_SUPPORTED_BANKS = [
        Type::REFUND => [
            Gateway::NETBANKING_ICICI    => [IFSC::ICIC],
            Gateway::NETBANKING_HDFC     => [IFSC::HDFC],
            Gateway::NETBANKING_KOTAK    => [IFSC::KKBK],
            Gateway::NETBANKING_AXIS     => [IFSC::UTIB],
            Gateway::NETBANKING_FEDERAL  => [IFSC::FDRL],
            Gateway::NETBANKING_RBL      => [IFSC::RATN],
            Gateway::NETBANKING_INDUSIND => [IFSC::INDB]
        ],
        Type::CLAIM => [
            Gateway::NETBANKING_KOTAK   => [IFSC::KKBK],
            Gateway::NETBANKING_AXIS    => [IFSC::UTIB],
            Gateway::NETBANKING_FEDERAL => [IFSC::FDRL],
            Gateway::NETBANKING_RBL     => [IFSC::RATN],
        ],
        Type::EMI => [
            self::ALL => [
                IFSC::INDB,
                IFSC::KKBK,
                IFSC::RATN,
                IFSC::UTIB,
            ]
        ],
        Type::COMBINED => [
            Gateway::NETBANKING_KOTAK   => [IFSC::KKBK],
            Gateway::NETBANKING_AXIS    => [IFSC::UTIB],
            Gateway::NETBANKING_FEDERAL => [IFSC::FDRL],
            Gateway::NETBANKING_RBL     => [IFSC::RATN],
        ],
    ];

    const TYPE_SENDER_MAPPING = [
        Type::REFUND   => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::CLAIM    => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::COMBINED => MailConstants::MAIL_ADDRESSES[MailConstants::REFUNDS],
        Type::EMI      => MailConstants::MAIL_ADDRESSES[MailConstants::EMI],
    ];
}
