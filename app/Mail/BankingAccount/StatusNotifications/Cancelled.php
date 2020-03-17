<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

class Cancelled extends Base
{
    const TEMPLATE_PATH     = 'emails.banking_account.notify_status_cancelled';

    const SUBJECT           = 'Your RazorpayX CA request has been cancelled at your request';

    const VIEW_FEEDBACK_URL          = 'https://x.razorpay.com/?support=ticket';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'view_feedback_url'  => self::VIEW_FEEDBACK_URL,
        ];

        $this->with($data);

        return $this;
    }
}
