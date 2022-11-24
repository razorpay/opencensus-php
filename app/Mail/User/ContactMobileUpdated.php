<?php

namespace RZP\Mail\User;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\User\Entity;

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
    $this->withSymfonyMessage(function (Email $message)
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

  protected function shouldSendEmailViaStork(): bool
  {
      return true;
  }

  protected function getParamsForStork(): array
  {
      return [
          'template_name'      => 'merchant.user.contact_mobile_updated',
          'template_namespace' => 'payments_account',
          'params'             => [
              'user_contact_mobile' => $this->data['user'][Entity::CONTACT_MOBILE],
              'user_email'          => $this->data['user'][Entity::EMAIL] ?? '',
              'user_updated_on'     => $this->data['user']['updated_on'],
              'user_updated_at'     => $this->data['user'][Entity::UPDATED_AT],
              'merchant_id'         => $this->data['merchant'][Entity::ID],
              'merchant_name'       => $this->data['merchant'][Entity::NAME],
          ]
      ];
  }
}
