<?php

namespace RZP\Mail\BankingAccount;

class Activated extends UpdateNotificationBase
{
    const TEMPLATE_PATH   = 'emails.banking_account.notify_status_activated';

    const SUBJECT         = 'Your RazorpayX Current Account is ready!';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'merchant_name'      => $this->bankingAccount->getBeneficiaryName(),
            'account_number'     => $this->bankingAccount->getAccountNumber(),
            'ifsc_code'          => $this->bankingAccount->getAccountIfsc(),
        ];

        $this->with($data);

        return $this;
    }
}
