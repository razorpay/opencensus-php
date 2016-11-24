<?php

namespace RZP\Gateway\Billdesk;

use RZP\Gateway\Base;

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

    protected $guarded = array();

    protected $entity = 'billdesk';

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