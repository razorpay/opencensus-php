<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Payout extends Base
{
    protected static $mailTag     = MailTags::BATCH_PAYOUT_FILE;
    protected static $sender      = Constants::NOREPLY;
    protected static $subjectLine = "Razorpay | Processed payouts file for %s";
    protected static $body        = 'Please find attached processed payouts file.';
}
