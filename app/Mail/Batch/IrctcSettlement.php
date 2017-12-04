<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class IrctcSettlement extends Base
{
    protected static $mailTag     = MailTags::BATCH_IRCTC_SETTLEMENT_FILE;

    protected static $sender      = Constants::IRCTC;

    protected static $subjectLine = 'Razorpay | IRCTC Settlement Validation File for %s';

    protected static $body        = 'IRCTC Settlement Validation File';
}
