<?php

namespace RZP\Mail\Gateway\CaptureFile;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const RECIPIENT_EMAILS_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => ['example@axisbank.com', 'settlements@razorpay.com']
        ],
    ];

    const HEADER_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => 'Axis Paysecure Rupay',
        ],
    ];

    const SUBJECT_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => 'Axis Paysecure Rupay file for ',
        ],
    ];

    const MAILTAG_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => MailTags::AXIS_PAYSECURE_MAIL,
        ],
    ];

    const BODY_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => 'Todays file has been uploaded, please confirm.',
        ],
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::PAYSECURE        => [
            Gateway::ACQUIRER_AXIS => 'emails.message',
        ],
    ];
}
