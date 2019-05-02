<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class LinkedAccountReversal extends Base
{
    protected static $mailTag     = MailTags::BATCH_REFUNDS_FILE;

    protected static $sender      = Constants::LINKED_ACCOUNT_REVERSAL;

    protected static $subjectLine = "Razorpay | Processed Linked Account Refunds file for %s";

    protected static $body        = 'Please find attached processed Linked Account Refunds File';
}
