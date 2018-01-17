<?php

namespace RZP\Mail\Settlement;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\Channel;

class Constants extends Base\Constants
{
    const HEADER_MAP = [
        Channel::KOTAK  => 'Kotak Settlement',
        Channel::ICICI  => 'ICICI Settlement',
    ];

    const MAILTAG_MAP = [
        Channel::KOTAK  => MailTags::KOTAK_SETTLEMENT_FILES,
        Channel::ICICI  => MailTags::ICICI_SETTLEMENT_FILES,
    ];
}