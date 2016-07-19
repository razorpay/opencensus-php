<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'payment_id', //reference_no
        'channel',
        'received', 
        'BankPaymentID',
        'accountId', //account_id
        'TxnAmount', //amount
        'returnUrl', //return_url
        'name',
        'address',
        'city',
        'state',
        'country',
        'postalCode', //postal_code
        'phone',
        'email',
        'shipName', //ship_name
        'shipAddress', //ship_address
        'shipState', //ship_state
        'shipCity', //ship_city
        'shipPostalCode', //ship_postal_code
        'shipCountry', //ship_country
        'shipPhone', //ship_phone
        'description',
        'currency',
        'mode',
        'nameOncard', //name_on_card
        'cardNumber', //card_number
        'cardExpiry', //card_expiry
        'paymentMode', //payment_mode
        'cardBrand', //card_brand
        'cardCvv', //card_cvv
    );

    protected $fillable = array(
        'payment_id', //reference_no
        'channel', 
        'accountId', //account_id
        'TxnAmount', //amount
        'returnUrl', //return_url
        'BankPaymentID',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postalCode', //postal_code
        'phone',
        'email',
        'shipName', //ship_name
        'shipAddress', //ship_address
        'shipState', //ship_state
        'shipCity', //ship_city
        'shipPostalCode', //ship_postal_code
        'shipCountry', //ship_country
        'shipPhone', //ship_phone
        'description',
        'currency',
        'mode',
        'nameOncard', //name_on_card
        'cardNumber', //card_number
        'cardExpiry', //card_expiry
        'paymentMode', //payment_mode
        'cardBrand', //card_brand
        'cardCvv', //card_cvv
    );

    protected $table = 'ebs';

    protected $guarded = array();

    protected $entity = 'ebs';

    protected $appends = array('status', 'refund_status');
}
