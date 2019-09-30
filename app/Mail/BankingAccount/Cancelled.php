<?php

namespace RZP\Mail\BankingAccount;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;

class Cancelled extends Mailable
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_cancelled';

    protected $bankAccount;

    protected $config;

    /**
     * Cancelled constructor.
     * @param Entity $bankAccount
     */
    public function __construct(Entity $bankAccount)
    {
        parent::__construct();

        $this->bankAccount = $bankAccount;

        $this->config = App::getFacadeRoot()['config'];
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
        $subject = 'Your RazorpayX Current Account is Cancelled';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.razorx.url']
        ];

        $this->with($data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ACCOUNT_STATUS_UPDATED;
    }
}
