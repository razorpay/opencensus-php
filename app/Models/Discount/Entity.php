<?php

namespace RZP\Models\Discount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Offer;

/**
 * @property Payment\Entity  $payment
 * @property Order\Entity    $order
 * @property Offer\Entity    $offer
 */
class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const ORDER_ID           = 'order_id';
    const OFFER_ID           = 'offer_id';
    const AMOUNT             = 'amount';

    protected $public = [
        self::ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::OFFER_ID,
        self::AMOUNT,
    ];

    protected $fillable = [
        self::AMOUNT,
    ];

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::OFFER_ID,
        self::AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::OFFER_ID,
    ];

    protected static $sign = 'disc';

    protected $entity = Constants\Entity::DISCOUNT;

    protected $generateIdOnCreate = true;

    protected $ignoredRelations = [
        "order"
    ];


    // ----------------------- Associations ------------------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function offer()
    {
        return $this->belongsTo('RZP\Models\Offer\Entity');
    }

    // ----------------------- Public Setters ----------------------------------

    public function setPublicPaymentIdAttribute(array & $array)
    {
        if (isset($array[self::PAYMENT_ID]) === true)
        {
            $paymentId = $array[self::PAYMENT_ID];

            $array[self::PAYMENT_ID] = Payment\Entity::getSignedId($paymentId);
        }
    }

    public function setPublicOrderIdAttribute(array & $array)
    {
        if (isset($array[self::ORDER_ID]) === true)
        {
            $orderId = $array[self::ORDER_ID];

            $array[self::ORDER_ID] = Order\Entity::getSignedId($orderId);
        }
    }

    public function setPublicOfferIdAttribute(array & $array)
    {
        if (isset($array[self::OFFER_ID]) === true)
        {
            $offerId = $array[self::OFFER_ID];

            $array[self::OFFER_ID] = Offer\Entity::getSignedId($offerId);
        }
    }

    // -------------------------- Getters --------------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getOrderAttribute()
    {
        $order = null;

        if ($this->relationLoaded('order') === true)
        {
            $order = $this->getRelation('order');
        }

        if ($order !== null)
        {
            return $order;
        }

        $order = $this->order()->with('offers')->first();

        if (empty($order) === false)
        {
            return $order;
        }

        if (empty($this[self::ORDER_ID]) === true)
        {
            return null;
        }

        $order = (new Order\Repository)->findOrFailPublic('order_'.$this[self::ORDER_ID]);

        $this->order()->associate($order);

        return $order;
    }

    // -------------------------- Setters --------------------------------------

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }
}
