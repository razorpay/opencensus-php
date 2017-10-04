<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class IrctcRefund extends Base
{
    protected static $mailTag     = MailTags::BATCH_IRCTC_REFUNDS_FILE;

    protected static $sender      = Constants::REFUNDS;

    protected static $subjectLine = "Razorpay | IRCTC Refunds File";

    protected static $body        = 'Please upload IRCTC refund file on portal';
}
