<?php

namespace RZP\Models\Notification;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;

class Entity extends Base\PublicEntity
{
    use AsvGetAttribute;

    protected $entity = 'notification';

    const ORDER_ID             = 'order_id';
    const ID                   = 'id';
    const TOKEN_ID             = 'token_id';
    const MERCHANT_ID          = 'merchant_id';
    const STATUS               = 'status';
    const GATEWAY              = 'gateway';
    const BANK_RRN             = 'bank_rrn';
    const NPCI_TXN_ID          = 'npci_txn_id';
    const GATEWAY_REQUEST      = 'gateway_request';
    const GATEWAY_RESPONSE     = 'gateway_response';
    const DELIVERED_AT         = 'delivered_at';
    const PAYMENT_AFTER        = 'payment_after';
    const GATEWAY_MERCHANT_ID  = 'gateway_merchant_id';
    const VPA                  = 'vpa';
    const PROVIDER             = 'provider';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ORDER_ID,
        self::TOKEN_ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::GATEWAY_REQUEST,
        self::GATEWAY_RESPONSE,
        self::GATEWAY,
        self::NPCI_TXN_ID,
        self::BANK_RRN,
        self::DELIVERED_AT,
        self::PAYMENT_AFTER,
        self::GATEWAY_MERCHANT_ID,
        self::VPA,
        self::PROVIDER,
    ];

    protected $public = [
        self::ID,
        self::ORDER_ID,
        self::TOKEN_ID,
        self::STATUS,
        self::DELIVERED_AT,
        self::PAYMENT_AFTER,
        self::ENTITY,
    ];

    protected $webhook = [
        self::ID,
        self::ORDER_ID,
        self::TOKEN_ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::DELIVERED_AT,
        self::PAYMENT_AFTER,
        self::ENTITY,
    ];

    protected $visible = [
        self::ID,
        self::ORDER_ID,
        self::TOKEN_ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::GATEWAY_REQUEST,
        self::GATEWAY_RESPONSE,
        self::GATEWAY,
        self::NPCI_TXN_ID,
        self::BANK_RRN,
        self::DELIVERED_AT,
        self::PAYMENT_AFTER,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::GATEWAY_MERCHANT_ID,
        self::VPA,
        self::PROVIDER,
    ];

    protected $defaults = [
        self::STATUS            => 'created'
    ];

//    protected $appends = [
//        self::SEQUENCE_NUMBER,
//    ];

    protected $dates = [
        self::DELIVERED_AT,
        self::PAYMENT_AFTER,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::GATEWAY_REQUEST      => 'array',
        self::GATEWAY_RESPONSE     => 'array',

    ];

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }


    public function token()
    {
        return $this->belongsTo(Customer\Token\Entity::class);
    }

    public function order()
    {
        return $this->belongsTo(Order\Entity::class);
    }

    // Setters
    public function setTokenId(string $tokenId)
    {
        $this->setAttribute(self::TOKEN_ID, $tokenId);
    }

    public function setStatus(string $status)
    {
        Status::validateNotificationStatus($status);

        if ($status === Status::DELIVERED)
        {
            $this->setAttribute(self::DELIVERED_AT, $this->freshTimestamp());
        }

        $this->setAttribute(self::STATUS, $status);
    }

    public function setNpciTxnId($value)
    {
        return $this->setAttribute(self::NPCI_TXN_ID, $value);
    }

    public function setBankRRN($value)
    {
        return $this->setAttribute(self::BANK_RRN, $value);
    }

    public function setGatewayRequest($value)
    {
        return $this->setAttribute(self::GATEWAY_REQUEST, $value);
    }

    public function setGatewayResponse($value)
    {
        return $this->setAttribute(self::GATEWAY_RESPONSE, $value);
    }

    public function setPaymentAfter($value=null)
    {
        if($value === null)
        {
            $value = Carbon::now(Timezone::IST)->addHours(25);
        }
        return $this->setAttribute(self::PAYMENT_AFTER, $value);
    }

    public function setGateway($value)
    {
        return $this->setAttribute(self::GATEWAY, $value);
    }

    public function setVpa($value)
    {
        return $this->setAttribute(self::VPA, $value);
    }

    public function setGatewayMerchantId($value)
    {
        return $this->setAttribute(self::GATEWAY_MERCHANT_ID, $value);
    }

    public function setProvider($value)
    {
        return $this->setAttribute(self::PROVIDER, $value);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getGatewayRequest()
    {
        return $this->getAttribute(self::GATEWAY_REQUEST);
    }

    public function getPaymentAfter()
    {
        return $this->getAttribute(self::PAYMENT_AFTER);
    }

    public function getDeliveredAt()
    {
        return $this->getAttribute(self::DELIVERED_AT);
    }

    public function getGatewayMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
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

    public function toArrayTrace(): array
    {
        return array_only($this->toArray(), [
            self::ORDER_ID,
            self::TOKEN_ID,
            self::PAYMENT_AFTER,
            self::STATUS,
            self::DELETED_AT,
            self::GATEWAY_REQUEST
        ]);
    }

    public function toArray()
    {
        return [
            'id'            => 'notification_'.$this->getId(),
            'token_id'      => 'token_'.$this->getTokenId(),
            'payment_after' => $this->getPaymentAfter(),
            'status'        => $this->getStatus(),
            'delivered_at'  => $this->getDeliveredAt(),
        ];
    }

    public function toArrayPublic(): array
    {
        $arrayPublic = parent::toArrayPublic();

        if ($arrayPublic != null) {
            $arrayPublic['order_id'] = 'order_' . $arrayPublic['order_id'];
            $arrayPublic['token_id'] = 'token_' . $arrayPublic['token_id'];
            $arrayPublic['id'] = 'notification_' . $arrayPublic['id'];
        }

        return $arrayPublic;
    }

    public function toArrayWebhook()
    {
        $array = parent::toArrayWebhook();

        return $array;
    }
}
