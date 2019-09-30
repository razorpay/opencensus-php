<?php

namespace RZP\Mail\BankingAccount;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;

class Created extends Mailable
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_created';

    protected $bankAccount;

    /**
     * Cancelled constructor.
     * @param Entity $bankAccount
     */
    public function __construct(Entity $bankAccount)
    {
        parent::__construct();

        $this->bankAccount = $bankAccount;
    }

    protected function addRecipients()
    {
        $toEmail = $this->bankAccount->merchant->getEmail();

        $toName = $this->bankAccount->merchant->getName();

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::RAZORPAY_X];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Your request for RazorpayX Current Account has been received';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'contact_name'   => $this->bankAccount->merchant->getName(),
            'contact_email'  => $this->bankAccount->merchant->getEmail(),
        ];

        $this->with($data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ACCOUNT_STATUS_UPDATED;
    }
}
