<?php

namespace App\Mailers;

use App\Merchant\Entity as MerchantEntity;

class ContactFormMailer extends Mailer
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

        $this->fromEmail = $this->data['email'];
        $this->fromName = $this->data['name'];

        $this->to = 'Razorpay Contact';
        $this->email = $this->getEmailFor('contact');
        $this->view = 'emails.contact';

        return $this;
    }
}
