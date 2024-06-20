<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use RZP\Base;

class Validator extends Base\Validator
{
    public function __construct($entity = null)
    {
        parent::__construct($entity);
    }

    protected static $recordCustomerConsent1ccRules = [
        '1cc_customer_consent'              => 'required|boolean',
        'one_cc_email_customer_consent'     => 'sometimes|boolean',
        'one_cc_whatsapp_customer_consent'  => 'sometimes|boolean',
    ];

    protected static $createRules = [
        'status'            => 'required',
        'merchant_id'       => 'required',
        'contact'           => 'required|contact_syntax',
        'consent_json'      => 'sometimes',
    ];

    protected static $editRules = [
        'id'                => 'sometimes',
        'status'            => 'required',
        'merchant_id'       => 'required',
        'contact'           => 'required|contact_syntax',
        'consent_json'      => 'sometimes',
    ];

    public static function validateRecordCustomerConsent1cc($input)
    {
        (new static)->validateInput('recordCustomerConsent1cc', $input);
    }
}
