<?php

namespace RZP\Mail\BankingAccount;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

abstract class Base extends Mailable
{
    const TEMPLATE_PATH = '';

    const SUBJECT       = '';

    const MAIL_TAG      = '';

    protected $data;

    protected $fromEmail;

    protected $toEmail;

    protected $replyToEmail;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to(Constants::MAIL_ADDRESSES[$this->toEmail],
                  Constants::HEADERS[$this->toEmail]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(static::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return static::MAIL_TAG;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[$this->fromEmail],
                    Constants::HEADERS[$this->fromEmail]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[$this->replyToEmail],
                       Constants::HEADERS[$this->replyToEmail]);

        return $this;
    }

    protected function addMailData()
    {
        $data = $this->getMailData();

        $this->with($data);

        return $this;
    }

    abstract protected function getSubject();

    abstract protected function getMailData();
}
