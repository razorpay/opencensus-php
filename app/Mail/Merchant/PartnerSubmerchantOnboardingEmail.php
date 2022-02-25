<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class PartnerSubmerchantOnboardingEmail extends Mailable
{
    protected $data;

    protected $template;

    public function __construct(array $data, string $template, string $subject)
    {
        parent::__construct();

        $this->data = $data;
        $this->subject = $subject;
        $this->template = $template;
    }

    public function addSubject() {
      $this->subject = str_replace("{merchantName}",$this->data['merchant']['name'], $this->subject);
      return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    protected function addRecipients()
    {
        $this->to($this->data['partner']['email']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->template);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::PARTNERSHIPS],
            Base\Constants::HEADERS[Base\Constants::PARTNERSHIPS]);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => 'partnerships',
            'org_id'             => $this->data['org']['id'],
            'template_name'      => $this->template,
            'params'  => $this->data,
        ];
    }

}