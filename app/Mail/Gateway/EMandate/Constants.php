<?php

namespace RZP\Mail\Gateway\EMandate;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const REGISTER  = 'register';
    const DEBIT     = 'debit';

    const RECIPIENT_EMAILS_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => ['hdfc.emandate@razorpay.com'],
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => ['hdfc.emandate@razorpay.com'],
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => ['axis.emandate@razorpay.com'],
    ];

    const HEADER_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => 'HDFC EMandate Register',
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => 'HDFC EMandate Debit',
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => 'AXIS EMandate Debit',
    ];

    const SUBJECT_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => 'HDFC EMandate Register File for ',
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => 'HDFC EMandate Debit File for ',
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => 'AXIS EMandate Debit File for ',
    ];

    const MAILTAG_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => MailTags::HDFC_EMANDATE_REGISTER_MAIL,
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => MailTags::HDFC_EMANDATE_DEBIT_MAIL,
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => MailTags::AXIS_EMANDATE_DEBIT_MAIL,
    ];

    const BODY_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => 'PFA EMandate Register request file.',
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => 'PFA EMandate Debit request file.',
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => 'PFA EMandate Debit request file.',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::NETBANKING_HDFC . '_' . self::REGISTER     => 'emails.message',
        Gateway::NETBANKING_HDFC . '_' . self::DEBIT        => 'emails.message',
        Gateway::NETBANKING_AXIS . '_' . self::DEBIT        => 'emails.message',
    ];
}
