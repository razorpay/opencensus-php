<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

class Rejected extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_rejected';

    const SUBJECT       = 'Your RazorpayX Current Account request has not been approved :(';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];

        $this->with($data);

        return $this;
    }
}
