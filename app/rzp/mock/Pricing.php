<?php

namespace RZP\Mock;

class Pricing extends MockEntity{   

    public function create($params = null)
    {
        $this->mock(self::$mockData['pricing_plan']);

        return $this;
    }

    public function fetch($id)
    {
        $this->mock(self::$mockData['pricing_plan'], array('id' => $id));

        return $this;
    }

    public function all($options = array())
    {
        $this->mockCollection(self::$mockData['pricing_plan']);

        return $this;
    }

    public function merchants()
    {
        return $this->all();
    }

    public function gateways()
    {
        return $this->all();
    }

    public function fetchRule($id)
    {
        $this->mock(self::$mockData['pricing_plan_rule'], array('id' => $id));

        return $this;
    }

    public function createRule($params)
    {
        $this->mock(self::$mockData['pricing_plan_rule']);

        return $this;
    }
}