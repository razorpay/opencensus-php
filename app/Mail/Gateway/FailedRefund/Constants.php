<?php

namespace RZP\Mail\Gateway\FailedRefund;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile;

class Constants extends RefundFile\Constants
{
    const HEADER_MAP = [
        Gateway::UPI_ICICI          => 'UPI Icici Failed Refunds',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed Refunds',
        Gateway::AMEX               => 'Amex Failed Refunds',
        Gateway::FIRST_DATA         => 'FirstData Failed Refunds',
    ];

    const SUBJECT_MAP = [
        Gateway::UPI_ICICI          => 'UPI Icici Failed refunds file for ',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed refunds file for ',
        Gateway::AMEX               => 'Amex Failed refunds for',
        Gateway::FIRST_DATA         => 'FirstData Failed Refunds for',

    ];

    const BODY_MAP = [
        Gateway::UPI_ICICI          => 'Please find attached failed refunds information for  ICICI UPI',
        Gateway::WALLET_AIRTELMONEY => 'Please find attached failed refunds information for  Airtel Money',
        Gateway::AMEX               => 'Please find attached failed refunds information for  Amex',
        Gateway::FIRST_DATA         => 'Please find attached failed refunds information for  FirstData',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::UPI_ICICI            => 'emails.message',
        Gateway::WALLET_AIRTELMONEY   => 'emails.message',
        Gateway::AMEX                 => 'emails.message',
        Gateway::FIRST_DATA           => 'emails.message',
    ];

    const MAILTAG_MAP = [
        Gateway::UPI_ICICI            => MailTags::ICICI_UPI_FAILED_REFUNDS_MAIL,
        Gateway::WALLET_AIRTELMONEY   => MailTags::AIRTEL_MONEY_FAILED_REFUNDS_MAIL,
        Gateway::AMEX                 => MailTags::AMEX_FAILED_REFUNDS_MAIL,
        Gateway::FIRST_DATA           => MailTags::FIRST_DATA_FAILED_REFUNDS_MAIL,
    ];
}
