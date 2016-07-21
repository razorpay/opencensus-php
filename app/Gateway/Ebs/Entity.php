<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'payment_id', //reference_no
        'channel',
        'received',
        'accountId', //account_id
        'TxnAmount', //amount
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
        'paymentMode', //payment_mode
        'RequestID',
        'TransactionID',
        'TxnAmount',
        'RefAmount',
        'ebs_payment_id',
        'ErrorDescription',
        'ErrorCode',
    );

    protected $fillable = array(
        'payment_id',
        'channel',
        'received',
        'accountId',
        'TxnAmount',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postalCode',
        'phone',
        'email',
        'shipName',
        'shipAddress',
        'shipState',
        'shipCity',
        'shipPostalCode',
        'shipCountry',
        'shipPhone',
        'description',
        'currency',
        'mode',
        'paymentMode',
        'RequestID',
        'TransactionID',
        'TxnAmount',
        'RefAmount',
        'ebs_payment_id',
        'ErrorDescription',
        'ErrorCode',
    );

    protected $table = 'ebs';

    protected $guarded = array();

    protected $entity = 'ebs';

    protected $appends = array('status', 'refund_status');

    protected function getStatusAttribute()
    {
        $code = $this->attributes['AuthStatus'];

        if ($code === null)
        {
            return null;
        }

        return AuthStatus::$statusMap[$code];
    }
    protected function getRefundStatusAttribute()
    {
        $code = $this->attributes['RefStatus'];

        if ($code === null)
        {
            return null;
        }

        return RefundStatus::$statusMap[$code];
    }
}
