<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Mail\Base\Mailable;

class MerchantInvoiceReminderMail extends Mailable
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
        $subject = 'Invoice reminders - Settlements pending';

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
            'template_name'      => 'mail.dashboard.invoice_reminder_notification',
            'params'  => [
                'business_name'     => $this->data['business_name'],
                'transaction_count' => $this->data['count'],
                'total_credit'      => $this->data['total_credit'],
            ],
        ];
    }

    protected function addHtmlView()
    {
        $this->view('mail.dashboard.invoice_reminder_notification');
        return $this;
    }
}
