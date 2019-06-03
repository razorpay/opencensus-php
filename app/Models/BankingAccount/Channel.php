<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Settlement\Channel as FTAChannel;

class Channel
{
    public static $supportedChannels = [
        FTAChannel::YESBANK,
        FTAChannel::RBL,
    ];
}
