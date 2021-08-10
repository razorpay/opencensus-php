<?php


namespace RZP\Mail\Batch;


use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Comment;

class IciciLeadAccountActivationComments extends Base
{
    protected static $mailTag     = MailTags::BATCH_ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS_FILE;

    protected static $sender      = Constants::BANKING_ACCOUNT;

    protected static $subjectLine = 'RazorpayX | CA Activation Bank Comments batch file for %s dated %s';

    protected static $body        = 'Please find attached the CA Activation bank comments created.';

    protected function addSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $this->subject(sprintf(static::$subjectLine, $this->batchSettings[BankingAccount\Entity::CHANNEL], $today));

        return $this;
    }

    protected function addRecipients()
    {
        $adminEmail = $this->batchSettings[Comment\Entity::ADMIN_EMAIL];

        $this->to($adminEmail);

        return $this;
    }
}
