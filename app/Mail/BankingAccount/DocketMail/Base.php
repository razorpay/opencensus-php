<?php


namespace RZP\Mail\BankingAccount\DocketMail;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Base extends Mailable {

    const SUBJECT       = '';

    public $viewData;

    /**
     * @param array $viewData
     */
    public function __construct(array $viewData)
    {
        parent::__construct();

        $this->viewData = $viewData;
    }

    protected function getSubject(): string
    {
        return self::SUBJECT;
    }

    protected function addMailData()
    {
        $data = $this->viewData;

        $this->with($data);

        return parent::addMailData();
    }

    protected function getMailTag()
    {
        return MailTags::BANKING_ACCOUNT_REPORT;
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