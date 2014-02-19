<?php

namespace DomainObject;

abstract class DomainObject
{

    abstract public function build(array $input = null);

    public function set(array $data = null)
    {
        foreach ($this->data as $key=>$value)
        {
            if (array_key_exists($key, $this->attr))
            {
                $this->attr[$key] = $value;
            }
            else 
            {
                $this->set_property($key);
            }
        }
        return ERR::SUCCESS;
    }

    public function set_property($key)
    {
        throw new \InvalidArgumentException($key . " is not a valid key.");
    }

}