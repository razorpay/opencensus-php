<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Preferences;

class PaymentLinkServiceBase extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = $this->getSenderEmail();

        $fromHeader = $this->data[E::MERCHANT][Merchant\Entity::NAME];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function getSenderEmail(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::NOREPLY);
    }

    protected function addRecipients()
    {
        $customerEmail = $this->data['to'];

        $this->to($customerEmail);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->data['subject'];

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {

        $email = $this->getSenderEmail();

        $header = $this->getSenderHeader();

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->data['view']);
        $merchantId = $this->data['merchant']['id'];
        switch ($merchantId) {
            case Preferences::MID_BAGIC_2:
            case Preferences::MID_BAGIC:
                $this->view('emails.invoice.customer.custom.bagic_email');
                break;
        }

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return false;
    }
}
