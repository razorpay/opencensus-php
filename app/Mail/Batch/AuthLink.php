<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class AuthLink extends Base
{
    protected static $mailTag     = MailTags::BATCH_AUTH_LINK_FILE;

    protected static $sender      = Constants::INVOICES;

    protected static $subjectLine = "Razorpay | Processed auth link file for %s";

    protected static $body        = 'Please find attached processed Auth link file';
}
