<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class MerchantNotAvailable extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_merchant_not_available_to_spoc';

    const SUBJECT       = '[Alert] RX Current Account - Customer is not Available';

    protected $bankingAccount;

    public function __construct(array $bankingAccounts, string $email)
    {
        parent::__construct($bankingAccounts, $email);
    }

    protected function addMailData()
    {
        $bankingAccount = $this->bankingAccounts[0];

        $data = [
            PublicEntity::MERCHANT_ID => $bankingAccount[BankingAccount\Entity::MERCHANT_ID],

            'businessName' => $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Activation\Detail\Entity::BUSINESS_NAME],

            'name' => $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Activation\Detail\Entity::MERCHANT_POC_NAME],

            'phoneNumber' => $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],

            'lmsLink' => 'https://admin-dashboard.razorpay.com/admin/banking-accounts/bacc_' . $bankingAccount[BankingAccount\Entity::ID],
        ];

        $this->with($data);

        return $this;
    }
}
