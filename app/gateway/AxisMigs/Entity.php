<?php

namespace Gateway\AxisMigs;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'id',
        'payment_id',
        'vpc_3DSECI',
        'vpc_3DSXID',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_Amount',
        'vpc_BatchNo',
        'vpc_Command',
        'vpc_Currency',
        'vpc_Locale',
        'vpc_MerchTxnRef',
        'vpc_Merchant',
        'vpc_Message',
        'vpc_SecureHash',
        'vpc_TransactionNo',
        'vpc_TxnResponseCode',
        'vpc_VerSecurityLevel',
        'vpc_VerStatus',
        'vpc_VerToken',
        'vpc_VerType',
        'vpc_Version',
    );

    protected $fillable = array(
        'id',
        'vpc_Command',
        'vpc_Amount',
        'vpc_Currency',
        'vpc_3DSECI',
        'vpc_3DSXID',
        'vpc_3DSenrolled',
        'vpc_3DSstatus',
        'vpc_BatchNo',
        'vpc_MerchTxnRef',
        'vpc_Message',
        'vpc_TransactionNo',
        'vpc_TxnResponseCode',
        'vpc_VerToken',
        'vpc_VerType',
        'vpc_VerSecurityLevel',
        'vpc_VerStatus',
    );

    protected $table = 'axis';

    protected $guarded = array();

    protected static $sign = 'pay';

    protected $entity = 'axis_migs';

    public function setPaymentId($paymentId)
    {
        $this->attributes['payment_id'] = $paymentId;
    }
}