<?php

namespace RZP\Mail\Gateway\Nach;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;

class Constants extends Base\Constants
{
    const REGISTER  = 'register';
    const DEBIT     = 'debit';
    const CANCEL    = 'cancel';

    const HEADER_MAP = [
        Gateway::NACH_CITI                        . '_' . self::REGISTER     => 'Citi NACH Register',
        Gateway::NACH_CITI                        . '_' . self::DEBIT        => 'Citi NACH Debit',
        Gateway::NACH_ICICI                       . '_' . self::REGISTER     => 'Icici Nach Register',
        Gateway::NACH_ICICI                       . '_' . self::CANCEL       => 'ICICI NACH CANCEL',
        GatewayFileConstants::COMBINED_NACH_ICICI . '_' . self::DEBIT        => 'ICICI Nach Debit',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::NACH_CITI                        . '_' . self::REGISTER     => 'emails.message',
        Gateway::NACH_CITI                        . '_' . self::DEBIT        => 'emails.message',
        Gateway::NACH_ICICI                       . '_' . self::REGISTER     => 'emails.admin.icici_nach_npci_register',
        Gateway::NACH_ICICI                       . '_' . self::CANCEL       => 'emails.admin.icici_nach_npci_cancel',
        GatewayFileConstants::COMBINED_NACH_ICICI . '_' . self::DEBIT        => 'emails.admin.icici_enach_npci',
    ];

    const SUBJECT_MAP = [
        Gateway::NACH_CITI                        . '_' . self::REGISTER     => 'Citi NACH Register File for ',
        Gateway::NACH_CITI                        . '_' . self::DEBIT        => 'Citi NACH Debit File for ',
        Gateway::NACH_ICICI                       . '_' . self::REGISTER     => 'Razorpay MMS File for ',
        Gateway::NACH_ICICI                       . '_' . self::CANCEL       => 'Razorpay Cancellation File for ',
        GatewayFileConstants::COMBINED_NACH_ICICI . '_' . self::DEBIT        => 'Razorpay Transaction File For Settlement Date ',
    ];

    const BODY_MAP = [
        Gateway::NACH_CITI                        . '_' . self::REGISTER     => 'PFA NACH Register request file.',
        Gateway::NACH_CITI                        . '_' . self::DEBIT        => 'PFA NACH Debit request file.',
        Gateway::NACH_ICICI                       . '_' . self::REGISTER     => 'PFA Mandate MMS request file.',
        Gateway::NACH_ICICI                       . '_' . self::CANCEL       => 'PFA Cancellation request file ',
        GatewayFileConstants::COMBINED_NACH_ICICI . '_' . self::DEBIT        => 'Nach Debit request file',
    ];

    const MAILTAG_MAP = [
        Gateway::NACH_CITI                        . '_' . self::REGISTER     => MailTags::CITI_NACH_REGISTER_MAIL,
        Gateway::NACH_CITI                        . '_' . self::DEBIT        => MailTags::CITI_NACH_DEBIT_MAIL,
        Gateway::NACH_ICICI                       . '_' . self::REGISTER     => MailTags::ICICI_NACH_REGISTER_MAIL,
        Gateway::NACH_ICICI                       . '_' . self::CANCEL       => MailTags::ICICI_NACH_CANCEL_MAIL,
        GatewayFileConstants::COMBINED_NACH_ICICI . '_' . self::DEBIT        => MailTags::ICICI_ENACH_DEBIT_MAIL,
    ];
}
