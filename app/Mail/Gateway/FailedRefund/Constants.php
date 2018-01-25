<?php

namespace RZP\Mail\Gateway\FailedRefund;

use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile;

class Constants extends RefundFile\Constants
{
    const HEADER_MAP = [
        'All'                       => 'Failed Refunds',
        Gateway::UPI_ICICI          => 'UPI Icici Failed Refunds',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed Refunds',
    ];

    const SUBJECT_MAP = [
        'All'                       => 'Failed Refunds file for ',
        Gateway::UPI_ICICI          => 'UPI Icici Failed refunds file for ',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed refunds file for ',
    ];

    const BODY_MAP = [
        'All'                       => 'Please find attached failed refunds information.',
        Gateway::UPI_ICICI          => 'Please find attached failed refunds information for UPI ICICI',
        Gateway::WALLET_AIRTELMONEY => 'Please find attached failed refunds information for Airtel Money',
    ];

    const MAIL_TEMPLATE_MAP = [
        'All'                         => 'emails.message',
        Gateway::UPI_ICICI            => 'emails.message',
        Gateway::WALLET_AIRTELMONEY   => 'emails.message',
    ];

    const MAILTAG_MAP = [
        'All'                         => MailTags::FAILED_REFUNDS_MAIL,
        Gateway::UPI_ICICI            => MailTags::ICICI_UPI_FAILED_REFUNDS_MAIL,
        Gateway::WALLET_AIRTELMONEY   => MailTags::AIRTEL_MONEY_FAILED_REFUNDS_MAIL,
    ];
}
