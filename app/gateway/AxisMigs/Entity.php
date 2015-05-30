<?php

namespace Gateway\AxisMigs;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'id',
        'payment_id',
        'refund_id',
        'vpc_3DSECI',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_3DSXID',
        'vpc_AcqResponseCode',
        'vpc_Amount',
        'vpc_AuthorizeId',
        'vpc_BatchNo',
        'vpc_Card',
        'vpc_Command',
        'vpc_Currency',
        'vpc_MerchTxnRef',
        'vpc_Message',
        'vpc_ReceiptNo',
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
        'vpc_3DSECI',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_3DSXID',
        'vpc_AcqResponseCode',
        'vpc_Amount',
        'vpc_AuthorizeId',
        'vpc_BatchNo',
        'vpc_Card',
        'vpc_Command',
        'vpc_Currency',
        'vpc_MerchTxnRef',
        'vpc_Message',
        'vpc_ReceiptNo',
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

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }

    public function getGeniusAttribute()
    {
        return (bool) $this->attributes['genius'];
    }
}