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

    /**
     * We are matching the Entity\request method, but with an extra
     * timeout being set to 60s
     */
    protected function longRequest($method, $relativeUrl, $data = null)
    {
        // This is an instance of App\RZP\Request now
        // which includes the setOption method
        $request = new Request();
        $request->setOption('timeout', 180);

        $response = $request->request($method, $relativeUrl, $data);

        if ((isset($response['entity'])) and
            ($response['entity'] == $this->getEntity()))
        {
            $this->fill($response);

            return $this;
        }
        else
        {
            return static::buildEntity($response);
        }
    }
}
