<?php

namespace RZP\Mail\User;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class ContactMobileUpdated extends Mailable
{
    protected $data;

  public function __construct($data)
  {
    parent::__construct();

    parent::addMailData();

    $this->data = array_merge($this->data, $data);
  }

  protected function addRecipients()
  {
    $user = $this->data['user'];

    $this->to($user['email'], $user['name']);

    return $this;
  }

  protected function addSubject()
  {
    $this->subject('Mobile Number for your Razorpay account is updated');

    return $this;
  }

  protected function addMailData()
  {
      $this->with($this->data);

      return $this;
  }

  protected function addHeaders()
  {
    $this->withSwiftMessage(function ($message)
    {
      $headers = $message->getHeaders();

      $headers->addTextHeader(MailTags::HEADER, MailTags::USER_CONTACT_MOBILE_UPDATED);
    });

    return $this;
  }

  protected function addHtmlView()
  {
    $this->view('emails.mjml.merchant.user.contact_mobile_updated');

    return $this;
  }
}
