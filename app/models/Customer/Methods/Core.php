<?php

namespace Models\Customer\Methods;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Customer\Methods;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Methods\Repository;
    }
    
    public function create($customer, $input)
    {
        $input[Methods\Entity::CUSTOMER_ID] = $customer->getKey();

        $method = (new Methods\Entity)->build($input);

        $this->validateExistingMethod($method);

        $this->repo->saveOrFail($method);

        return $method;
    }

    public function edit($method, $input)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_METHODS_EDIT,
            [
                'method_id' => $method->getId(),
                'fields' => array_keys($input),
            ]);

        $method->edit($input);

        $this->validateExistingMethod($method);

        $this->repo->saveOrFail($method);
    
        return $method;
    }

    protected function validateExistingMethod($method)
    {
        $params = array(
            Methods\Entity::METHOD      => $method->getMethod(),
            Methods\Entity::CUSTOMER_ID => $method->getCustomerId(),
        );

        $existingMethods = $this->repo->getByParams($params);

        $func = 'validateExistingMethod'.$method->getMethod();

        $this->$func($existingMethods, $method);
    }

    protected function validateExistingMethodCard($existingMethods, $newMethod)
    {
        foreach ($existingMethods as $method) 
        {
            if($method->getCardId() === $newMethod->getCardId())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_CARD_ALREADY_EXISTS);
            }
        }
    }

    protected function validateExistingMethodNetbanking($existingMethods, $newMethod)
    {
        foreach ($existingMethods as $method) 
        {
            if(($method->getBank()  === $newMethod->getBank()) and
                ($method->getAccountKey() === $newMethod->getAccountKey()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_BANK_ALREADY_EXISTS);
            }
        }
    }

    protected function validateExistingMethodWallet($existingMethods, $newMethod)
    {
        foreach ($existingMethods as $method) 
        {
            if(($method->getWallet()  === $newMethod->getWallet()) and
                ($method->getAccountKey() === $newMethod->getAccountKey()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_WALLET_ALREADY_EXISTS);
            }
        }   
    }
}
