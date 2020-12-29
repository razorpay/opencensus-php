<?php

namespace RZP\Mail\VendorPayments;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class GenericVendorPaymentEmail extends Mailable
{
    protected $data;

    protected $templateName;

    protected $merchantEmail;

    protected $customSubject;

    public function __construct(array $merchantEmail, string $subject, string $templateName, array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->customSubject = $subject;

        $this->templateName = $templateName;

        $this->merchantEmail = $merchantEmail;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail);

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
