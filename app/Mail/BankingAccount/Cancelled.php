<?php

namespace RZP\Mail\BankingAccount;

class Cancelled extends UpdateNotificationBase
{
    const TEMPLATE_PATH     = 'emails.banking_account.notify_status_cancelled';

    const SUBJECT           = 'Your RazorpayX CA request has been cancelled at your request';

    const GIVE_FEEDBACK_URL = '';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'give_feedback_url' => self::GIVE_FEEDBACK_URL
        ];

        $this->with($data);

        return $this;
    }
}
