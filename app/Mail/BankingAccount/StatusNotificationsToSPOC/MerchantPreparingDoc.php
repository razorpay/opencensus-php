<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

class MerchantPreparingDoc extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_merchant_preparing_doc_to_spoc';

    const SUBJECT       = '[Alert] RX Current Account - Customers are preparing docs for more than 5 days';

    protected $bankingAccounts;

    public function __construct(array $bankingAccounts, string $email)
    {
        parent::__construct($bankingAccounts, $email);
    }
}
