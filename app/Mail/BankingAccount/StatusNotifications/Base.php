<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;

class Base extends Mailable
{
    const TEMPLATE_PATH = '';

    const SUBJECT       = '';

    protected $bankingAccount;

    protected $config;

    /**
     * @param string $bankingAccountId
     */
    public function __construct(string $bankingAccountId)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $this->config = $app['config'];

        $this->bankingAccount = $repo->banking_account->find($bankingAccountId);
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
