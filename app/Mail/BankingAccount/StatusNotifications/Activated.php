<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use RZP\Models\BankingAccount\Entity;

class Activated extends Base
{
    const TEMPLATE_PATH   = 'emails.banking_account.notify_status_activated';

    const SUBJECT         = 'Your RazorpayX Current Account is ready!';

    protected function addMailData()
    {
        $data = [
            'view_dashboard_url' => $this->config['applications.banking_service_url'],
            'merchant_name'      => $this->bankingAccount[Entity::BENEFICIARY_NAME],
            'account_number'     => $this->bankingAccount[Entity::ACCOUNT_NUMBER],
            'ifsc_code'          => $this->bankingAccount[Entity::ACCOUNT_IFSC],
        ];

        $this->with($data);

        return $this;
    }
}
