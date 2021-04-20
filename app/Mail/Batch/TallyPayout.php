<?php

namespace RZP\Mail\Batch;

use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class TallyPayout extends Base
{
    protected static $mailTag       = MailTags::BATCH_TALLY_PAYOUT_FILE;
    protected static $sender        = Constants::NOREPLY;
    protected static $subjectLine   = 'Processed payouts file for %s';
    protected static $body          = 'Please find attached processed payouts file.';

    /**
     * Test Prefix is used only if useTestPrefix is set to true
     *
     * @var bool
     */
    protected $useTestPrefix = true;

    /**
     * {@inheritDoc}
     */
    protected function addRecipients()
    {
        // Processed file should be sent to user who uploaded batch file.
        $email = $this->batchSettings['user']['email'] ?? $this->merchant[Merchant\Entity::TRANSACTION_REPORT_EMAIL];

        $name  = $this->batchSettings['user']['name'] ?? $this->merchant[Merchant\Entity::NAME];

        return $this->to($email, $name);
    }
}
