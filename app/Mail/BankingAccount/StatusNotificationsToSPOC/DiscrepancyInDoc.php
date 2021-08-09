<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

class DiscrepancyInDoc extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_discrepancy_in_doc_to_spoc';

    const SUBJECT       = ' [Alert] RX Current Account - Customers have discrepancy in docs more than 5 days';

    protected $bankingAccount;

    public function __construct(array $bankingAccountStates, string $email)
    {
        parent::__construct($bankingAccountStates, $email);
    }
}
