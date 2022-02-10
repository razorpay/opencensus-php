<?php

namespace RZP\Mail\Merchant\InternationalEnablement;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class Reminder extends Mailable
{
    protected $data;
    protected $email;

    const SUBJECT = 'Start Accepting International Payments!';

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
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

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
            'org_id'             => $this->data['merchant']['org_id'],
            'template_name'      => 'mail.dashboard.international_enablement_reminder',
            'params'  => [
                'merchant_name' => $this->data['merchant']['name'],
            ],
        ];
    }

    protected function addHtmlView()
    {
        $this->view('mail.dashboard.international_enablement_reminder');
        return $this;
    }


}
