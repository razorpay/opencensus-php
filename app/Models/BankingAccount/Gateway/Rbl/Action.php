<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

/**
 * Class Action
 *
 * This class contains the list of all functions/API calls
 * that RBL bank will take, these can be used to have
 * different request modifier and response/error handler based on
 * these actions
 *
 * @package RZP\Models\BankingAccount\Gateway\Rbl
 */
class Action
{
    const ACCOUNT_BALANCE = 'account_balance';
}
