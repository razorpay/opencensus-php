<?php

namespace RZP\Mail\Settlement;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\Channel;

class Constants extends Base\Constants
{
    const HEADER_MAP = [
        Channel::KOTAK   => 'Kotak Settlement',
        Channel::ICICI   => 'ICICI Settlement',
        Channel::AXIS    => 'AXIS Settlement',
        Channel::AXIS2   => 'AXIS2 Settlement',
        Channel::HDFC    => 'HDFC Settlement',
        Channel::RBL     => 'RBL Settlement',
        Channel::YESBANK => 'YesBank Settlement',
    ];

    const MAILTAG_MAP = [
        Channel::KOTAK   => MailTags::KOTAK_SETTLEMENT_FILES,
        Channel::ICICI   => MailTags::ICICI_SETTLEMENT_FILES,
        Channel::AXIS    => MailTags::AXIS_SETTLEMENT_FILES,
        Channel::AXIS2   => MailTags::AXIS_SETTLEMENT_FILES,
        Channel::HDFC    => MailTags::HDFC_SETTLEMENT_FILES,
        Channel::RBL     => MailTags::RBL_SETTLEMENT,
        Channel::YESBANK => MailTags::YESBANK_SETTLEMENT,
    ];
}
