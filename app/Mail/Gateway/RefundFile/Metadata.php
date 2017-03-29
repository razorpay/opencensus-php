<?php

namespace RZP\Mail\Gateway\RefundFile;

use RZP\Constants\MailTags;

class Metadata
{
    const NETBANKING_HDFC    = 'NETBANKING_HDFC';
    const NETBANKING_ICICI   = 'NETBANKING_ICICI';
    const UPI_ICICI          = 'UPI_ICICI';
    const WALLET_AIRTELMONEY = 'WALLET_AIRTELMONEY';
    const WALLET_PAYUMONEY   = 'WALLET_PAYUMONEY';

    const RECIPIENT_EMAILS_MAP = [
        self::NETBANKING_HDFC    => ['Directpay.Refunds@hdfcbank.com','settlements@razorpay.com'],
        self::NETBANKING_ICICI   => ['settlements@razorpay.com'],
        self::UPI_ICICI          => ['settlements@razorpay.com'],
        self::WALLET_AIRTELMONEY => ['settlements@razorpay.com'],
        self::WALLET_PAYUMONEY   => ['settlements@razorpay.com']
    ];

    const FROM_HEADER_MAP = [
        self::NETBANKING_HDFC    => 'Hdfc Netbanking refunds',
        self::NETBANKING_ICICI   => 'Icici Netbanking refunds',
        self::UPI_ICICI          => 'UPI Icici Refunds',
        self::WALLET_AIRTELMONEY => 'Wallet Airtelmoney refunds',
        self::WALLET_PAYUMONEY   => 'Wallet Payumoney refunds'
    ];

    const SUBJECT_MAP = [
        self::NETBANKING_HDFC    => 'HDFC Netbanking refunds file for ',
        self::NETBANKING_ICICI   => 'Icici Netbanking refunds file for ',
        self::UPI_ICICI          => 'UPI Icici refunds file for ',
        self::WALLET_AIRTELMONEY => 'Airtelmoney refunds file for ',
        self::WALLET_PAYUMONEY   => 'PayUMoney refunds file for '
    ];

    const MAILTAG_MAP = [
        self::NETBANKING_HDFC    => MailTags::HDFC_NETBANKING_REFUNDS_MAIL,
        self::NETBANKING_ICICI   => MailTags::ICICI_NETBANKING_REFUNDS_MAIL,
        self::UPI_ICICI          => MailTags::ICICI_UPI_REFUNDS_MAIL,
        self::WALLET_AIRTELMONEY => MailTags::AIRTEL_MONEY_REFUNDS_MAIL,
        self::WALLET_PAYUMONEY   => MailTags::PAYU_MONEY_REFUNDS_MAIL,
    ];
}
