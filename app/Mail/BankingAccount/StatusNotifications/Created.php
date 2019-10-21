<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

class Created extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_created';

    const SUBJECT       = 'Woohoo! We received your request for RazorpayX Current Account';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url']
        ];
        $this->with($data);

        return $this;
    }
}
