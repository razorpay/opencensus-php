<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use RZP\Models\BankingAccount\Entity;

class Processed extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_processed';

    const SUBJECT       = 'Congrats ! Your account is created! One last step to go...';

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
