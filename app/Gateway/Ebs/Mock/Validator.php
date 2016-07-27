<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'channel'           => 'required|alpha_num',
        'account_id'        => 'required|alpha_num',
        'return_url'        => 'required|url',
        'reference_no'      => 'required|alpha_num',
        'amount'            => 'required|',
        'name'              => 'required|alpha_num',
        'address'           => 'required|',
        'city'              => 'required|alpha_num',
        'country'           => 'required|alpha_num',
        'postal_code'       => 'required|',
        'phone'             => 'required|alpha_num',
        'email'             => 'required|',
        'description'       => 'required|',
        'currency'          => 'required|alpha_num',
        'mode'              => 'required|alpha_num',
        'name_on_card'      => 'sometimes|alpha_num',
        'card_number'       => 'sometimes|alpha_num',
        'card_expiry'       => 'sometimes|alpha_num',
        'payment_mode'      => 'required|alpha_num',
        'card_brand'        => 'sometimes|alpha_num',
        'card_cvv'          => 'sometimes|alpha_num',
        'payment_option'    => 'sometimes|alpha_num',
        'secure_hash'       => 'required|alpha_num',
    );

    protected static $verifyRules = array(
        'RequestType'       => 'required|in:0122',
        'secure_hash'       => 'required|alpha_num',
    );

    protected static $refundRules = array(
        'Action'            => 'required|alpha_num',
        'AccountID'         => 'required|alpha_num',
        'SecretKey'         => 'required|alpha_num',
        'Amount'            => 'required|',
        'PaymentID'         => 'required|alpha_num',

    );
}
