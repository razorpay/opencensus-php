<?php

namespace RZP\Mail\Gateway\Nach;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const REGISTER  = 'register';
    const DEBIT     = 'debit';

    const HEADER_MAP = [
        Gateway::NACH_CITI . '_' . self::REGISTER     => 'Citi NACH Register',
        Gateway::NACH_CITI . '_' . self::DEBIT        => 'Citi NACH Debit',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::NACH_CITI . '_' . self::REGISTER     => 'emails.message',
        Gateway::NACH_CITI . '_' . self::DEBIT        => 'emails.message',
    ];

    const SUBJECT_MAP = [
        Gateway::NACH_CITI . '_' . self::REGISTER     => 'Citi NACH Register File for ',
        Gateway::NACH_CITI . '_' . self::DEBIT        => 'Citi NACH Debit File for ',
    ];

    const BODY_MAP = [
        Gateway::NACH_CITI . '_' . self::REGISTER     => 'PFA NACH Register request file.',
        Gateway::NACH_CITI . '_' . self::DEBIT        => 'PFA NACH Debit request file.',
    ];

    const MAILTAG_MAP = [
        Gateway::NACH_CITI . '_' . self::REGISTER     => MailTags::CITI_NACH_REGISTER_MAIL,
        Gateway::NACH_CITI . '_' . self::DEBIT        => MailTags::CITI_NACH_DEBIT_MAIL,
    ];
}
