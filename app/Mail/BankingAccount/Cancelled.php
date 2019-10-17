<?php

namespace RZP\Mail\BankingAccount;

class Cancelled extends UpdateNotificationBase
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_cancelled';

    const SUBJECT       = 'Your RazorpayX Current Account is Cancelled';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];

        $this->with($data);

        return $this;
    }
}
