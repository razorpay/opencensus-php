<?php

namespace Gateway\Billdesk;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'payment_id',
        'refund_id',
        'action',
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

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }

    public function setAction($action)
    {
        $this->setAttribute('action', $action);
    }
}