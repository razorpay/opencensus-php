<?php

namespace RZP\Mail\Gateway\FailedRefund;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const HEADER_MAP = [
        Gateway::UPI_ICICI     => 'UPI Icici Failed Refunds',
        Gateway::AIRTEL_MONEY  => 'Airtel Money Failed Refunds',
    ];

    const SUBJECT_MAP = [
        Gateway::UPI_ICICI      => 'UPI Icici Failed refunds file for ',
        Gateway::AIRTEL_MONEY   => 'Airtel Money Failed refunds file for ',
    ];

    const BODY_MAP = [
        Gateway::UPI_ICICI    => 'Please find attached failed refunds information for  ICICI UPI',
        Gateway::AIRTEL_MONEY => 'Please find attached failed refunds information for  Airtel Money',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::UPI_ICICI     => 'emails.message',
        Gateway::AIRTEL_MONEY   => 'emails.message',
    ];
}
