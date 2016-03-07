<?php

namespace Models\Customer\Methods;

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
        $input['customer_id'] = $customer->getKey();

        $method = (new Methods\Entity)->build($input);

        //$this->validateExistingMethod($method);

        $this->repo->saveOrFail($method);

        return $method;
    }

    public function edit($method, $input)
    {
        //$this->validateExistingMethod($method);

        $this->trace->info(
            TraceCode::CUSTOMER_METHODS_EDIT,
            [
                'method_id' => $method->getId(),
                'fields' => array_keys($input),
            ]);

        $method->edit($input);

        $this->repo->saveOrFail($method);
    
        return $method;
    }

    protected function validateExistingMethod($method)
    {
        $params = array(
            Methods\Entity::CUSTOMER_ID => $method->getCustomerId(),
            Methods\Entity::METHOD      => $method->getMethod()
        );

        $existingMethods = $this->repo->getByParams($params);

        $func = 'validateExistingMethod'.$method->getId();

        $this->$func($existingMethods, $method);
    }

    protected function validateExistingMethodCard($existingMethods, $method)
    {

    }

    protected function validateExistingMethodNetbanking($existingMethods, $method)
    {
        
    }

    protected function validateExistingMethodWallet($existingMethods, $method)
    {
        
    }
}