<?php

namespace RZP\Models\PaymentsUpi\Vpa\BankAccount;

use RZP\Models\PaymentsUpi\Base;

class Entity extends Base\Entity
{
    const VPA_ID                = 'vpa_id';
    const BANK_ACCOUNT_ID       = 'bank_account_id';
    const LAST_USED_AT          = 'last_used_at';

    protected $entity = 'payments_upi_vpa_bank_account';

    protected $generateIdOnCreate = true;
}
