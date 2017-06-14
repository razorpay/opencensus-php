<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Refund extends Base
{
    protected static $mailTag     = MailTags::BATCH_REFUNDS_FILE;

    protected static $sender      = Constants::REFUNDS;

    protected static $subjectLine = "Razorpay | Processed Refunds file for %s";

    protected static $body        = 'Please find attached processed Refunds File';
}
