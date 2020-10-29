<?php


namespace RZP\Mail\BankingAccount;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;

class UpdatesForAuditor extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.updates_for_auditor';

    const SUBJECT       = 'RazorpayX LMS | Daily Updates [%s]';

    protected $toName;

    public function __construct($toEmail, $toName, array $updates)
    {
        $this->toEmail      = $toEmail;
        $this->toName       = $toName;
        $this->fromEmail    = Constants::NOREPLY;
        $this->replyToEmail = Constants::NOREPLY;

        parent::__construct($updates);
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail,
                  $this->toName);

        return $this;
    }

    protected function getSubject()
    {
        return sprintf(self::SUBJECT, Carbon::now(Timezone::IST)->format('d-m-Y'));
    }

    protected function getMailData()
    {
        return $this->data;
    }
}
