<?php

namespace App\RZP;

use Razorpay;

class Entity extends Razorpay\Api\Entity
{
    protected static function getDefinedEntitiesArray()
    {
        return array(
            'card',
            'collection',
            'daily_settlement',
            'key',
            'merchant',
            'payment',
            'pricing',
            'refund',
            'settlement',
            'terminal',
            'transaction',
            'adjustment',
            'webhook',
        );
    }

    protected static function getEntityClass($name)
    {
        $name = studly_case($name);

        $class = __NAMESPACE__.'\\'.$name;

        if (class_exists($class))
        {
            return $class;
        }

        return parent::getEntityClass($name);
    }

    protected static function buildEntity($data)
    {
        return parent::buildEntity($data);
    }
}
