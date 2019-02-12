<?php

namespace RZP\Mail\Batch;

use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Payout extends Base
{
    protected static $mailTag     = MailTags::BATCH_PAYOUT_FILE;
    protected static $sender      = Constants::NOREPLY;
    protected static $subjectLine = 'Processed payouts file for %s';
    protected static $body        = 'Please find attached processed payouts file.';

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
