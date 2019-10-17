<?php

namespace RZP\Mail\BankingAccount;

class Activated extends UpdateNotificationBase
{
    const TEMPLATE_PATH   = 'emails.banking_account.notify_status_activated';

    const SUBJECT         = 'Your RazorpayX Current Account is ready!';

    const READ_GUIDE_LINK = '';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'merchant_name'      => $this->bankingAccount->getBeneficiaryName(),
            'account_number'     => $this->bankingAccount->getAccountNumber(),
            'ifsc_code'          => $this->bankingAccount->getAccountIfsc(),
            'read_guide_link'    => self::READ_GUIDE_LINK
        ];

        $this->with($data);

        return $this;
    }
}
