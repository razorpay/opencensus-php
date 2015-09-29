<?php

namespace Gateway\AxisMigs;

use Gateway\Base;

class Entity extends Base\Entity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
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

    protected $table = 'axis';

    protected $guarded = array();

    protected $entity = 'axis_migs';

    protected $appends = array('vpc_amount');

    public $incrementing = true;

    public function getGeniusAttribute()
    {
        return (bool) $this->attributes['genius'];
    }

    public function getVpcAmountAttribute()
    {
        return (int) $this->attributes['vpc_Amount'];
    }
}