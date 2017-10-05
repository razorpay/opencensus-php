<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class IrctcSettlement extends Base
{
    protected static $mailTag     = MailTags::BATCH_IRCTC_SETTLEMENT_FILE;

    protected static $sender      = Constants::SETTLEMENTS;

    protected static $subjectLine = 'Razorpay | IRCTC Settlement Validation File';

    protected static $body        = 'IRCTC Settlement Validation File';


    protected function addRecipients()
    {
        $emails =  ['irctc.refunds@razorpay.com'];

        $this->to($emails);

        return $this;
    }
}
