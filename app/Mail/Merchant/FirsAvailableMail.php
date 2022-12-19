<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class FirsAvailableMail extends Mailable
{
    protected $data;
    protected $email;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::NOREPLY],
            Base\Constants::HEADERS[Base\Constants::NOREPLY]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['contact_email']);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'FIRS is available for '.$this->data["firs_month_year"].' month';

        $this->subject($subject);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => 'payments_dashboard',
            'template_name'      => 'mail.dashboard.firs_available_notification',
            'params'  => [
                'business_name' => $this->data['business_name'],
                'firs_month_year' => $this->data['firs_month_year'],
            ],
        ];
    }

    protected function addHtmlView()
    {
        $this->view('mail.dashboard.firs_available_notification');
        return $this;
    }
}
