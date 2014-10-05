<?php

namespace Models\Api;

use Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        $merchantId = \Auth::merchant()->id();

        $this->setApiCredentials($merchantId, $mode);
    }

    public function fetchEntity($id, $mode, $entity)
    {
        $error = (new Validator)->validateInput(array('id' => $id), 'fetch');

        if (empty($error) === false)
        {
            return [$error, null];
        }

        try
        {
            $data = $this->api->payment->fetch($id)->toArray();

            $collection = array(
                'count' => 1,
                'entity' => 'collection',
                'data' => array($data));

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getCode();
        }

        return array($error, $collection);
    }

    public function fetchCollection(array $input, $mode, $entity)
    {
        $method = 'fetchCollection' . $entity;
        if (method_exists($this, $method))
        {
            return $this->$method($input, $mode);
        }

        return $this->fetchEntityCollection($input, $mode, $entity);
    }

    protected function fetchEntityCollection(array $input, $mode, $entity)
    {
        $data = array();

        $error = (new Validator)->validateInput($input, 'fetch');

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $collection = array();

        try
        {
            $collection = $this->api->$entity->all($input)->toArray();

            $this->mapKeys($collection);
        }
        catch(\Exception $e)
        {
            $error[] = $e->getCode();
        }

        return array([], $collection);
    }

    protected function mapKeys(array & $collection)
    {
        $entity = $collection['entity'];
        $mapVar = $entity . 'Mappings';

        if (property_exists(get_class(), $mapVar) === false)
        {
            return;
        }

        foreach ($collection['data'] as $entity)
        {
            foreach (static::$$mapVar as $apiKey => $mapKey)
            {
                $entity[$mapKey] = $entity[$apiKey];

                unset($entity[$apiKey]);
            }
        }
    }

    public function fetchPaymentRefunds($id, $mode)
    {
        $data = array();

        $error = (new Validator)->validateInput(array('id' => $id), 'fetch');

        if (empty($error))
        {
            try
            {
                $collection = $this->api->payment
                                        ->fetch($id)
                                        ->refunds()
                                        ->all()
                                        ->toArray();

                $data = $collection['data'];
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getCode();
            }
        }

        return array($error, $data);
    }

    public function capturePayment($id, $amount, $mode)
    {
        $error = array();

        try
        {
            $data = $this->api->payment
                                ->fetch($id)
                                ->capture(array('amount' => $amount))
                                ->toArray();
        }
        catch(\Exception $e)
        {
            $error[] = $e->getCode();
            return $error;
        }

        if (isset($data['error']) === true or isset($data['status']) === false or $data['status'] !== "captured")
            $error[] = "Capture Failed";

        return $error;
    }

    public function refundPayment($id, $amount, $mode)
    {
        $error = array();

        try
        {
            $data = $this->api->payment
                              ->fetch($id)
                              ->refund(array('amount' => $amount))
                              ->toArray();
        }
        catch(\Exception $e)
        {
            $error[] = $e->getCode();
            return $error;
        }

        if ($data['entity'] !== "refund" or $data['amount'] !== $amount)
        {
            $error[] = "Refund Failed";
        }

        return $error;
    }
}