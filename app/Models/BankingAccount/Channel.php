<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Settlement\Channel as FTAChannel;

class Channel
{
    const YESBANK = FTAChannel::YESBANK;
    const RBL     = FTAChannel::RBL;
}
