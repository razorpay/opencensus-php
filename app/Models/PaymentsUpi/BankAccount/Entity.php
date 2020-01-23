<?php

namespace RZP\Models\PaymentsUpi\BankAccount;

use RZP\Models\PaymentsUpi\Base;

class Entity extends Base\Entity
{
    const BANK_CODE             = 'bank_code';
    const IFSC_CODE             = 'ifsc_code';
    const ACCOUNT_NUMBER        = 'account_number';
    const BENEFICIARY_NAME      = 'beneficiary_name';

    protected $entity = 'payments_upi_bank_account';

    protected $generateIdOnCreate = true;
}
