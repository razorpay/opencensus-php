<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class InstantActivation extends Mailable
{
    protected $data;

    protected $org;

    public function __construct(array $data, array $org)
    {
        parent::__construct();

        $this->data = $data;

        $this->org = $org;
    }

    protected function addRecipients()
    {
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    /**
     * For Instant Activations Mail
     * 1) Other Org Merchants should receive old activations mail with out razorpayX content.
     * 2) When a Merchant submit's form on banking product then he should receive mail with X content in the first fold.
     * 3) When a Merchant submit's form on primary product then he should receive mail with primary content first.
     *
     * @return $this
     */
    protected function addHtmlView()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->view('emails.merchant.instant_activation_heimdall');
        }
        else
        {
            $this->view('emails.merchant.instant_activation');
        }

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Start accepting payments with ' . $this->data['merchant']['org']['business_name'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::INSTANT_ACTIVATION);
        });

        return $this;
    }

}
