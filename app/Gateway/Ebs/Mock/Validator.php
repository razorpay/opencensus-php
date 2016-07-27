<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Models\Base;
use RZP\Gateway\Ebs\Entity as Ebs;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        Ebs::CHANNEL            => 'required|alpha_num',
        Ebs::ACCOUNT_ID         => 'required|alpha_num',
        'return_url'            => 'required|url',
        Ebs::REFUND_REF_NO      => 'required|alpha_num',
        Ebs::AMOUNT             => 'required|',
        Ebs::NAME               => 'required|alpha_num',
        Ebs::ADDRESS            => 'required|',
        Ebs::CITY               => 'required|alpha_num',
        Ebs::COUNTRY            => 'required|alpha_num',
        Ebs::POSTAL_CODE        => 'required|',
        Ebs::PHONE              => 'required|alpha_num',
        Ebs::EMAIL              => 'required|',
        Ebs::DESCRIPTION        => 'required|',
        Ebs::CURRENCY           => 'required|alpha_num',
        Ebs::MODE               => 'required|alpha_num',
        'name_on_card'          => 'sometimes|alpha_num',
        'card_number'           => 'sometimes|alpha_num',
        'card_expiry'           => 'sometimes|alpha_num',
        Ebs::PAYMENT_MODE       => 'required|alpha_num',
        'card_brand'            => 'sometimes|alpha_num',
        'card_cvv'              => 'sometimes|alpha_num',
        'payment_option'        => 'sometimes|alpha_num',
        'secure_hash'           => 'required|alpha_num',
    );

    protected static $verifyRules = array(
        'RequestType'           => 'required|in:0122',
        'secure_hash'           => 'required|alpha_num',
    );

    protected static $refundRules = array(
        'Action'                => 'required|alpha_num',
        'AccountID'             => 'required|alpha_num',
        'SecretKey'             => 'required|alpha_num',
        'Amount'                => 'required|',
        'PaymentID'             => 'required|alpha_num',

    );
}
