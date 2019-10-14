<?php

namespace RZP\Mail\BankingAccount;

class Unserviceable extends UpdateNotifications
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_cancelled';

    const SUBJECT = 'Your RazorpayX Current Account is Unserviceable';


    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => App::getFacadeRoot()['config']['applications.banking_service_url']
        ];

        $this->with($data);

        return $this;
    }
}
