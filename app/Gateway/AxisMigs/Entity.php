<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const VPC_ACQ_RESPONSE_CODE     = 'vpc_AcqResponseCode';
    const VPC_BATCH_NO              = 'vpc_BatchNo';
    const VPC_MERCH_TXN_REF         = 'vpc_MerchTxnRef';
    const VPC_MERCHANT              = 'vpc_Merchant';
    const VPC_MESSAGE               = 'vpc_Message';
    const VPC_SHOP_TRANSACTION_NO   = 'vpc_ShopTransactionNo';
    const VPC_TRANSACTION_NO        = 'vpc_TransactionNo';
    const VPC_TXN_RESPONSE_CODE     = 'vpc_TxnResponseCode';

    const TERMINAL_ID = 'terminal_id';

    protected $fields = array(
        'id',
        'payment_id',
        'action',
        'refund_id',
        'terminal_id',
        'received',
        'vpc_3DSECI',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_3DSXID',
        'vpc_AcqCSCRespCode',
        'vpc_AcqResponseCode',
        'vpc_Amount',
        'vpc_AuthorizeId',
        'vpc_AuthorisedAmount',
        'vpc_BatchNo',
        'vpc_Card',
        'vpc_CapturedAmount',
        'vpc_Command',
        'vpc_CSCResultCode',
        'vpc_Currency',
        'vpc_MerchTxnRef',
        'vpc_Message',
        'vpc_ReceiptNo',
        'vpc_RefundedAmount',
        'vpc_SecureHash',
        'vpc_ShopTransactionNo',
        'vpc_TransactionNo',
        'vpc_TxnResponseCode',
        'vpc_VerSecurityLevel',
        'vpc_VerStatus',
        'vpc_VerToken',
        'vpc_VerType',
    );

    protected $fillable = array(
        'id',
        'refund_id',
        'terminal_id',
        'received',
        'vpc_3DSECI',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_3DSXID',
        'vpc_AcqCSCRespCode',
        'vpc_AcqResponseCode',
        'vpc_Amount',
        'vpc_AuthorisedAmount',
        'vpc_AuthorizeId',
        'vpc_BatchNo',
        'vpc_Card',
        'vpc_CapturedAmount',
        'vpc_Command',
        'vpc_CSCResultCode',
        'vpc_Currency',
        'vpc_MerchTxnRef',
        'vpc_Message',
        'vpc_ReceiptNo',
        'vpc_RefundedAmount',
        'vpc_ShopTransactionNo',
        'vpc_TransactionNo',
        'vpc_TxnResponseCode',
        'vpc_VerSecurityLevel',
        'vpc_VerStatus',
        'vpc_VerToken',
        'vpc_VerType',
    );

    protected $guarded = [];

    protected $entity = 'axis_migs';

    public $incrementing = true;

    protected $appends = [
        'vpc_amount'
    ];

    protected $casts = [
        'amex'       => 'bool',
        'genius'     => 'bool',
        'vpc_Amount' => 'int',
        'vpc_amount' => 'int',
    ];

    public function getVpcAmountAttribute()
    {
        return (int) $this->attributes['vpc_Amount'];
    }

    public function getAuthCode()
    {
        return $this->getAttribute('vpc_AuthorizeId');
    }

    public function getTransactionId()
    {
        return $this->getAttribute('vpc_TransactionNo');
    }

    public function getReceiptNo()
    {
        return $this->getAttribute('vpc_ReceiptNo');
    }

    public function setVpcTransactionNo($txnNo)
    {
        $this->setAttribute('vpc_TransactionNo', $txnNo);
    }

    public function getVpcTransactionCode()
    {
        return $this->getAttribute('vpc_TxnResponseCode');
    }

    /**
     * Under any circumstance we should not reset
     * vpc_TransactionNo. It is updated in two cases:
     * callback, and one of the cases of verify
     * If the verify request goes before the callback
     * is received, there is a chance that the verify
     * response doesn't have vpc_TransactionNo set,
     * which will set its value to NULL/0.
     * This happened couple of times in the past so
     * now we check for empty explicitly before setting it.
     * @param string $txnNo
     */
    public function setVpcTransactionNoAttribute($txnNo)
    {
        $oldTxnNo = null;

        if (isset($this->attributes['vpc_TransactionNo']))
        {
            $oldTxnNo = $this->attributes['vpc_TransactionNo'];
        }

        if (empty($oldTxnNo))
        {
            $this->attributes['vpc_TransactionNo'] = $txnNo;
        }
    }

    public function setArn($arn)
    {
        $this->setAttribute('arn', $arn);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
