<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class FundAccount extends Base
{
    protected static $mailTag       = MailTags::BATCH_FUND_ACCOUNT_FILE;
    protected static $sender        = Constants::NOREPLY;
    protected static $subjectLine   = 'Processed fund accounts file for %s';
    protected static $body          = 'Please find attached processed fund accounts file.';

    /**
     * Test Prefix is used only if useTestPrefix is set to true
     *
     * @var bool
     */
    protected $useTestPrefix = true;
}
