<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class HdfcBankAcquiredTerminalOnboarding extends Base
{
    protected static $mailTag     = MailTags::BATCH_HDFC_BANK_ACQUIRED_TERMINAL_ONBOARDING_FILE;

    protected static $sender      = Constants::NOREPLY;

    protected static $subjectLine = 'Razorpay | HDFC Bank Acquired Terminal onboarding file dated %s';

    protected static $body        = 'Please find attached HDFC Bank Acquired Terminal Onboarded File.';

    const RECIPIENTS = [
        'setup@razorpay.com',
    ];

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }
}
