<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

/**
 * Class Action
 * @package RZP\Models\BankingAccount\Gateway\Rbl
 * This class contains the list of all functions/api calls
 * that RBL bank will take, these can be used to have
 * different request modifier and response/error handler based on
 * these actions
 */
class Action
{
    const ACCOUNT_BALANCE = 'account_balance';
}
