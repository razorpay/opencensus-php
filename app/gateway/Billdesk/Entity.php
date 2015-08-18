<?php

namespace Gateway\Billdesk;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'payment_id',
        'refund_id',
        'action',
        'received',
        'MerchantID',
        'CustomerID',
        'TxnAmount',
        'BankID',
        'CurrencyType',
        'ItemCode',
        'TypeField1',
        'TypeField2',
        'AdditionalInfo1',
        'TxnReferenceNo',
        'BankReferenceNo',
        'BankMerchantID',
        'SecurityType',
        'TxnDate',
        'AuthStatus',
        'SettlementType',
        'ErrorStatus',
        'ErrorDescription',
        'RequestType',
        'RefAmount',
        'RefDateTime',
        'RefStatus',
        'RefundId',
        'ErrorCode',
        'ErrorReason',
        'ProcessStatus',
    );

    protected $fillable = array(
        'payment_id',
        'action',
        'refund_id',
        'received',
        'MerchantID',
        'CustomerID',
        'TxnAmount',
        'BankID',
        'CurrencyType',
        'ItemCode',
        'TypeField1',
        'TypeField2',
        'AdditionalInfo1',
        'TxnReferenceNo',
        'BankReferenceNo',
        'BankMerchantID',
        'SecurityType',
        'TxnDate',
        'AuthStatus',
        'SettlementType',
        'ErrorStatus',
        'ErrorDescription',
        'RequestType',
        'RefAmount',
        'RefDateTime',
        'RefStatus',
        'RefundId',
        'ErrorCode',
        'ErrorReason',
        'ProcessStatus',
    );

    protected $table = 'billdesk';

    protected $guarded = array();

    protected $entity = 'billdesk';
}