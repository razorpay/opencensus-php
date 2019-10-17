<?php

namespace RZP\Mail\BankingAccount;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;

class UpdateNotificationBase extends Mailable
{
    const TEMPLATE_PATH = '';

    const SUBJECT       = '';

    protected $bankingAccount;

    protected $config;

    /**
     * @param Entity $bankingAccount
     */
    public function __construct(Entity $bankingAccount)
    {
        parent::__construct();

        $this->bankingAccount = $bankingAccount;

        $this->config = App::getFacadeRoot()['config'];
    }

    protected function addRecipients()
    {
        $toEmail = $this->bankingAccount->merchant->getEmail();

        $toName = $this->bankingAccount->merchant->getName();

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(static::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(static::SUBJECT);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::BANKING_ACCOUNT_STATUS_UPDATED;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }
}