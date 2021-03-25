<?php


namespace RZP\Models\BankingAccount;


class Constants
{
    const BANKING_ACCOUNT_RESET_WEBHOOK_COMMENT = 'System Comment: Admin is reseting the webhook data and reverting back to the previous state. Bank has to trigger the account opening webhook again.';

    const X_OPS_TEAM = 'ops';

    const BANKING_ACCOUNT_SOURCE_TEAM_OR_TYPE_AS_INTERNAL = 'internal';
}
