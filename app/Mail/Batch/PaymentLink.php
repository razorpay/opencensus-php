<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class PaymentLink extends Base
{
    protected static $mailTag     = MailTags::BATCH_PAYMENT_LINK_FILE;

    protected static $sender      = Constants::INVOICES;

    protected static $subjectLine = "Razorpay | Processed payment link file for %s";

    protected static $body        = 'Please find attached processed payment link file';
}
