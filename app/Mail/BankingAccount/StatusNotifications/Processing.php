<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

class Processing extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_processing';

    const SUBJECT       = 'Your RazorpayX Current Account is under processing';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];

        $this->with($data);

        return $this;
    }
}
