<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $recordCustomerConsent1ccRules = [
        '1cc_customer_consent'   => 'required|boolean',
    ];

    protected static $createRules = [
        'status'            => 'required',
        'merchant_id'       => 'required',
        'contact'           => 'required|contact_syntax',
    ];

    protected static $editRules = [
        'id'                => 'sometimes',
        'status'            => 'required',
        'merchant_id'       => 'required',
        'contact'           => 'required|contact_syntax',
    ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);
    }

    public static function validateRecordCustomerConsent1cc($input)
    {
        (new static)->validateInput('recordCustomerConsent1cc', $input);
    }
}
