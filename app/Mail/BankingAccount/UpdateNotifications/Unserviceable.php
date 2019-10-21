<?php

namespace RZP\Mail\BankingAccount\UpdateNotifications;

class Unserviceable extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_unserviceable';

    const SUBJECT = 'Your RazorpayX CA request could not been approved :(';


    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];

        $this->with($data);

        return $this;
    }
}
