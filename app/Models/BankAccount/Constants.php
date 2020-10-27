<?php

namespace RZP\Models\BankAccount;

class Constants
{
    const BANK_ACCOUNT_UPDATE_PENNY_TESTING_TTL       = 180; // in minutes
    const BANK_ACCOUNT_UPDATE_PENNY_TESTING_CACHE_KEY = 'bank_account_update_penny_testing_%s';
    const BANK_ACCOUNT_UPDATE_MUTEX_RESOURCE          = 'bank_account_update_mutex_resource_%s';

    const BANK_ACCOUNT_CHANGE_REQUEST_EMAIL                 = 'RZP\Mail\Merchant\AccountChangeRequest';
    const BANK_ACCOUNT_CHANGED_EMAIL                        = 'RZP\Mail\Merchant\AccountChange';
    const BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE_EMAIL   = 'RZP\Mail\Merchant\AccountChangePennyTestingFailure';
}
