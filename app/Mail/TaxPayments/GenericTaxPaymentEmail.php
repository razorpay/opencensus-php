<?php

namespace RZP\Mail\TaxPayments;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class GenericTaxPaymentEmail extends Mailable
{
    const ATTACHMENT_FILE_URL = 'attachment_file_url';
    const FILE_NAME           = 'file_name';
    const MIME_TYPE           = 'mime_type';

    protected $data;

    protected $templateName;

    protected $merchantEmail;

    protected $customSubject;

    protected $ccEmails;

    public function __construct(string $merchantEmail, string $subject, string $templateName, array $data, array $ccEmails = [])
    {
        parent::__construct();

        $this->data = $data;

        $this->customSubject = $subject;

        $this->templateName = $templateName;

        $this->merchantEmail = $merchantEmail;

        $this->ccEmails = $ccEmails;
    }

    protected function addAttachments()
    {
        if ((isset($this->data[self::ATTACHMENT_FILE_URL]) === true) and
            (isset($this->data[self::FILE_NAME]) === true) and
            (isset($this->data[self::MIME_TYPE]) === true)) {

            $this->attach($this->data[self::ATTACHMENT_FILE_URL],
                [
                    'as' => $this->data[self::FILE_NAME],
                    'mime' => $this->data[self::MIME_TYPE]
                ]
            );
        }

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail);

        return $this;
    }

    protected function addCc()
    {
        if(!empty($this->ccEmails))
        {
            $this->cc($this->ccEmails);
        }
        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                           Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view($this->templateName);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->customSubject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }
}
