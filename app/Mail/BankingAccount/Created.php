<?php

namespace RZP\Mail\BankingAccount;

class Created extends UpdateNotifications
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_created';

    const SUBJECT = 'Your request for RazorpayX Current Account has been received';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];
        $this->with($data);

        return $this;
    }
}
