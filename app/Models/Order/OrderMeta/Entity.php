<?php

namespace RZP\Models\Order\OrderMeta;

use App;
use RZP\Models\Base;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;

/**
 * Class Entity
 *
 * @package RZP\Models\Order\OrderMeta
 */
class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const ORDER_ID      = 'order_id';
    const TYPE          = 'type';
    const VALUE         = 'value';

    protected $generateIdOnCreate = true;

    protected $entity   = 'order_meta';

    protected $fillable = [
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
    ];

    protected $visible  = [
        self::ID,
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public   = [
        self::ID,
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates     = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts     = [
        self::VALUE => 'array',
    ];

    public function toArrayTrace(): array
    {
        return array_only($this->toArray(), [
            self::ID,
            self::ORDER_ID,
            self::TYPE,
            self::VALUE,
        ]);
    }
    /*************** Setters *******************/
    public function setValue($value)
    {
        if ($this->isOneClickCheckout() === false)
        {
            $this->setAttribute(self::VALUE, $value);

            return;
        }

        $this->setValueForOneClickCheckout($value);
    }

    /*************** Getters *******************/

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getValue()
    {

        if ($this->isOneClickCheckout() === false)
        {
            return $this->getAttribute(self::VALUE);
        }

        return $this->getValueForOneClickCheckout();

    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    protected function isOneClickCheckout()
    {
        return $this->getType() === Type::ONE_CLICK_CHECKOUT;
    }

    protected function getValueForOneClickCheckout()
    {
        $value = $this->getAttribute(self::VALUE);

        $app = App::getFacadeRoot();

        foreach ($value as $key => $val)
        {
            if($key === Fields::CUSTOMER_DETAILS)
            {
                $value[$key] = $app['encrypter']->decrypt($val);
            }
        }
        return $value;
    }

    protected function setValueForOneClickCheckout($value)
    {
        $app = App::getFacadeRoot();
        foreach ($value as $key => $val)
        {
            if($key === Fields::CUSTOMER_DETAILS)
            {
                $value[$key] = $app['encrypter']->encrypt($val);
            }
        }
        $this->setAttribute(self::VALUE, $value);
    }
}

