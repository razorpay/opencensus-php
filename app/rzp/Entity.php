<?php

namespace RZP;

use Razorpay;

class Entity extends Razorpay\Api\Entity
{
    protected static function getDefinedEntitiesArray()
    {
        return array(
            'card',
            'collection',
            'key',
            'merchant',
            'payment',
            'pricing',
            'refund',
            'settlement',
            'terminal',
            'transaction'
        );
    }

    protected static function getEntityClass($name)
    {
        if (class_exists(__NAMESPACE__.'\\'.ucfirst($name)))
            return __NAMESPACE__.'\\'.ucfirst($name);

        return parent::getEntityClass($name);
    }

    protected static function buildEntity($data)
    {
        return parent::buildEntity($data);
    }
}