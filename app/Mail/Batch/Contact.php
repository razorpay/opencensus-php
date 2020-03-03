<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Contact extends Base
{
    protected static $mailTag       = MailTags::BATCH_CONTACT_FILE;
    protected static $sender        = Constants::NOREPLY;
    protected static $subjectLine   = 'Processed contacts file for %s';
    protected static $body          = 'Please find attached processed contacts file.';

    /**
     * Test Prefix is used only if useTestPrefix is set to true
     *
     * @var bool
     */
    protected $useTestPrefix = true;
}
