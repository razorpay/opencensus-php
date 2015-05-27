<?php

namespace RZP\Mock;

class Merchant extends MockEntity
{
    public function create($params = null)
    {
        $this->mock(self::$mockData['merchant']);

        return $this;
    }

    public function fetch($id)
    {
        $this->mock(self::$mockData['merchant'], array('id' => $id));

        return $this;
    }

    public function edit($params)
    {
        $this->mock(self::$mockData['merchant'], array('id' => $id));

        return $this;
    }
    
    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['merchant']);

        return $this;
    }

    public function keys()
    {
        $entity = new Key;

        $entity->merchant_id = $this->id;

        return $entity;
    }

    public function activate()
    {
        $this->mock(self::$mockData['merchant'], array('activated' => 1));

        return $this;
    }

    // Enables live transactions for merchant
    public function enable()
    {
        $this->mock(self::$mockData['merchant'], array('activated' => 1, 'live' => 1));

        return $this;
    }

    // disable live transactions for merchant
    public function disable()
    {
        $this->mock(self::$mockData['merchant'], array('activated' => 1));

        return $this;
    }

    public function fetchPricing()
    {
        $this->mock(array());

        return $this;
    }

    public function setPricing($params)
    {
        $pricing = self::$mockData['pricing'];

        $this->mock($pricing);

        return $this;
    }

    public function fetchTerminals()
    {
        $this->mockCollection(self::$mockData['terminal']);

        return $this;
    }

    public function setTerminal($params)
    {
        $this->mock(self::$mockData['terminal']);

        return $this;
    }

    public function fetchBanks()
    {
        $this->mock(self::$mockData['banks']);

        return $this;
    }

    public function setBanks($params)
    {
        $this->mock(self::$mockData['banks']);

        return $this;
    }

    public function fetchBankAccount()
    {
        $this->mock(self::$mockData['bankaccount']);

        return $this;
    }

    public function setBankAccount($params)
    {
        $this->mock(self::$mockData['bankaccount']);

        return $this;
    }
}
