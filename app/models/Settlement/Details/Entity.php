<?php

namespace Models\Settlement\Details;

use Models\Base;
use EE\Exception;

class Entity extends Base\PublicEntity
{
    const ID                    =>      'id';
    const MERCHANT_ID           =>      'merchant_id';
    const PAYMENT_COUNT         =>      'payment_count';
    const PAYMENT_AMOUNT        =>      'payment_amount';
    const REFUND_COUNT          =>      'refund_count';
    const REFUND_AMOUNT         =>      'refund_amount';
    const ADJUSTMENT_COUNT      =>      'adjustment_count';
    const ADJUSTMENT_AMOUNT     =>      'adjustment_amount';
    const TOTAL_AMOUNT          =>      'total_amount';
    const PLAN_FEE              =>      'plan_fee';
    const SERVICE_TAX           =>      'service_tax';
    const TOTAL_FEE             =>      'total_fee';
    const SETTLEMENT_AMOUNT     =>      'settlement_amount';
    const CREATED_AT            =>      'created_at';
    const UPDATED_AT            =>      'updated_at';

    protected $table = \Constants\Table::SETTLEMENT_DETAIL;

    protected $entity = 'settlement_detail';

    protected $entity = 'merchant';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_COUNT,
        self::PAYMENT_AMOUNT,
        self::REFUND_COUNT,
        self::REFUND_AMOUNT,
        self::ADJUSTMENT_COUNT,
        self::ADJUSTMENT_AMOUNT,
        self::TOTAL_AMOUNT,
        self::PLAN_FEE,
        self::SERVICE_TAX,
        self::TOTAL_FEE,
        self::SETTLEMENT_AMOUNT,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_COUNT,
        self::PAYMENT_AMOUNT,
        self::REFUND_COUNT,
        self::REFUND_AMOUNT,
        self::ADJUSTMENT_COUNT,
        self::ADJUSTMENT_AMOUNT,
        self::TOTAL_AMOUNT,
        self::PLAN_FEE,
        self::SERVICE_TAX,
        self::TOTAL_FEE,
        self::SETTLEMENT_AMOUNT,
        self::CREATED_AT,
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function getPaymentCount()
    {
        return (int) $this->getAttribute(self::PAYMENT_COUNT);
    }

    public function getPaymentAmount()
    {
        return (int) $this->getAttribute(self::PAYMENT_AMOUNT);
    }

    public function getRefundCount()
    {
        return (int) $this->getAttribute(self::REFUND_COUNT);
    }

    public function getRefundAmount()
    {
        return (int) $this->getAttribute(self::REFUND_AMOUNT);
    }

    public function getAdjustmentCount()
    {
        return (int) $this->getAttribute(self::ADJUSTMENT_COUNT);
    }

    public function getAdjustmentAmount()
    {
        return (int) $this->getAttribute(self::ADJUSTMENT_AMOUNT);
    }

    public function getTotalAmount()
    {
        return (int) $this->getAttribute(self::TOTAL_AMOUNT);
    }

    public function getPlanFee()
    {
        return (int) $this->getAttribute(self::PLAN_FEE);
    }

    public function getServiceTax()
    {
        return (int) $this->getAttribute(self::SERVICE_TAX);
    }

    public function getTotalFee()
    {
        return (int) $this->getAttribute(self::TOTAL_FEE);
    }

    public function getSettlementAmount()
    {
        return (int) $this->getAttribute(self::SETTLEMENT_AMOUNT);
    }


    //get Attribute
    public function getPaymentCountAttribute()
    {
        return (int) $this->attributes[self::PAYMENT_COUNT];
    }

    public function getPaymentAmountAttribute()
    {
        return (int) $this->attributes[self::PAYMENT_AMOUNT];
    }

    public function getRefundCountAttribute()
    {
        return (int) $this->attributes[self::REFUND_COUNT];
    }

    public function getRefundAmountAttribute()
    {
        return (int) $this->attributes[self::REFUND_AMOUNT];
    }

    public function getAdjustmentCountAttribute()
    {
        return (int) $this->attributes[self::ADJUSTMENT_COUNT];
    }

    public function getAdjustmentAmountAttribute()
    {
        return (int) $this->attributes[self::ADJUSTMENT_AMOUNT];
    }

    public function getTotalAmountAttribute()
    {
        return (int) $this->attributes[self::TOTAL_AMOUNT];
    }

    public function getPlanFeeAttribute()
    {
        return (int) $this->attributes[self::PLAN_FEE];
    }

    public function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    public function getTotalFeeAttribute()
    {
        return (int) $this->attributes[self::TOTAL_FEE];
    }

    public function getSettlementAmountAttribute()
    {
        return (int) $this->attributes[self::SETTLEMENT_AMOUNT];
    }

    //setter
    public function setPaymentCount($count)
    {
        $this->setAttribute(self::PAYMENT_COUNT, $count);
    }

    public function setPaymentAmount($amount)
    {
        $this->setAttribute(self::PAYMENT_AMOUNT, $amount);
    }

    public function setRefundCount($count)
    {
        $this->setAttribute(self::REFUND_COUNT, $count);
    }

    public function setRefundAmount($amount)
    {
        $this->setAttribute(self::REFUND_AMOUNT, $amount);
    }

    public function setAdjustmentCount($count)
    {
        $this->setAttribute(self::ADJUSTMENT_COUNT, $count);
    }

    public function setAdjustmentAmount($amount)
    {
        $this->setAttribute(self::ADJUSTMENT_AMOUNT, $amount);
    }

    public function setTotalAmount($amount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT, $amount);
    }

    public function setPlanFee($fee)
    {
        $this->setAttribute(self::PLAN_FEE, $fee);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setTotalFee($fee)
    {
        $this->setAttribute(self::TOTAL_FEE, $fee);
    }

    public function setSettlementAmount($amount)
    {
        $this->setAttribute(self::SETTLEMENT_AMOUNT, $amount);
    }    
}