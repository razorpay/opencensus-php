<?php

namespace Gateway\Sbiepay;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'payment_id',
        'refund_id',
        'action',
        'method',
        'received',
        'MerchantId',
        'OperatingMode',
        'MerchantCountry',
        'MerchantCurrency',
        'PostingAmount',
        'OtherDetails',
        'AggregatorId',
        'MerchantOrderNo',
        'MerchantCustomerID',
        'Paymode',
        'Accesmedium',
        'TransactionSource',
        'SBIePayReferenceID',
        'Status',
        'Reason',
        'BankCode',
        'BankReferenceNumber',
        'TransactionDate',
        'CIN',
        'refund_id',
    );

    protected $fillable = array(
        'payment_id',
        'refund_id',
        'action',
        'method',
        'received',
        'MerchantId',
        'OperatingMode',
        'MerchantCountry',
        'MerchantCurrency',
        'PostingAmount',
        'OtherDetails',
        'AggregatorId',
        'MerchantOrderNo',
        'MerchantCustomerID',
        'Paymode',
        'Accesmedium',
        'TransactionSource',
        'SBIePayReferenceID',
        'Status',
        'Reason',
        'BankCode',
        'BankReferenceNumber',
        'TransactionDate',
        'CIN',
        'refund_id',
    );

    protected $table = 'sbiepay';

    protected $guarded = array();

    protected $entity = 'sbiepay';
}