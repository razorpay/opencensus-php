<?php

namespace RZP\Mail\VendorPayments;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Unpaid extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.vendor_payments.unpaid';

    const SUBJECT = '[Warning] Invoice payment to %s could not be completed due to error';

    protected $input;

    public function __construct(array $input)
    {
        parent::__construct();

        $this->input = $input;
    }

    protected function addRecipients()
    {
        $this->to($this->input['to_email']);

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
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(sprintf(self::SUBJECT, $this->input['payout']['to']['name']));

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->input);

        return $this;
    }
}
