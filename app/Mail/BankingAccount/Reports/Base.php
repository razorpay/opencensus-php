<?php


namespace RZP\Mail\BankingAccount\Reports;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Base extends Mailable {

    const SUBJECT       = '';

    protected $reportData;

    /**
     * @param array $reportData
     */
    public function __construct(array $reportData)
    {
        parent::__construct();

        $this->reportData = $reportData;
    }

    protected function getSubject(): string
    {
        return self::SUBJECT;
    }

    protected function addMailData()
    {
        $data = $this->reportData;

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