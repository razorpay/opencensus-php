<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

use RZP\Models\Base\PublicEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class MerchantNotAvailable extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_merchant_not_available_to_spoc';

    const SUBJECT       = '[Alert] RX Current Account - Customer is not Available
';

    protected $bankingAccount;

    public function __construct(array $bankingAccountStates, string $email)
    {
        parent::__construct($bankingAccountStates, $email);
    }

    protected function addMailData()
    {
        $state = $this->states[0];

        $bankingAccount = $state->bankingAccount;

        $data = [
            PublicEntity::MERCHANT_ID => $state->getMerchantId(),

            'businessName' => $bankingAccount->merchant->merchantDetail->getBusinessName(),

            'name' => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_NAME],

            'phoneNumber' => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],

            'lmsLink' => 'https://admin-dashboard.razorpay.com/admin/banking-accounts/bacc_' . $bankingAccount->getId(),
        ];

        $this->with($data);

        return $this;
    }
}
