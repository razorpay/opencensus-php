<?php

namespace Razorpay\Mailers;

use Models\Merchant\Entity as MerchantEntity;

class CompanyMailer extends Mailer
{
    /**
     * Add the payload to the company mailer instance
     *
     * @param array $data
     */
    public function with($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Responsible for sending the contact email
     * Drops the message field, which is passed to us via the
     * razorpay.com website and renames it to content
     *
     * @return \Razorpay\Mailer\UserMailer
     */
    public function contact()
    {
        $this->subject = 'New Contact form submission - '. $this->data['name'];
        $this->data['content'] = $this->data['message'];
        unset($this->data['message']);

        $this->to = 'Razorpay Contact';
        $this->email = $this->getEmailFor('contact');
        $this->view = 'emails.contact';

        return $this;
    }
}
