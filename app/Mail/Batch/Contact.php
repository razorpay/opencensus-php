<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Contact extends Base
{
    protected static $mailTag     = MailTags::BATCH_CONTACT_FILE;
    protected static $sender      = Constants::NOREPLY;
    // TODO: Before pg dashboard using contact batch, need to fix subject line.
    protected static $subjectLine = "RazorpayX | Processed contacts file for %s";
    protected static $body        = 'Please find attached processed contacts file.';
}
