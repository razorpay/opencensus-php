<?php

namespace RZP\Mail\BankingAccount;

class Processed extends UpdateNotifications
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_processed';

    const SUBJECT       = 'Your RazorpayX Current Account is Processed';

    protected function addMailData()
    {

        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'merchant_name'      => $this->bankingAccount->getBeneficiaryName(),
            'account_number'     => $this->bankingAccount->getAccountNumber(),
            'ifsc_code'          => $this->bankingAccount->getAccountIfsc()
        ];

        $this->with($data);

        return $this;
    }
}
