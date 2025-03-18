<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class HdfcOnboarding extends Base
{
    protected static $mailTag     = MailTags::BATCH_HDFC_MERCHANT_ONBOARING_FILE;

    protected static $sender      = Constants::NOREPLY;

    protected static $subjectLine = 'Razorpay | HDFC Merchant onboarding file dated %s';

    protected static $body        = 'Please find attached HDFC Merchant Onboarded File.';

    const RECIPIENTS = [
        'setup@razorpay.com',
    ];

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }
}
