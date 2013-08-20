<?php

namespace DomainObject;

abstract class DomainObject
{

    public function set($data = null, $keys = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                throw new \InvalidArgumentException('parameter $data not provided.');
            }
            else $data = $this->data;
        }
        else 
            $this->data = $data;

        if ($keys === null)
        {
            foreach ($this->data as $key=>$value)
            {
                if (array_key_exists($key, $this->txn))
                {
                    $this->info[$key] = $value;
                }
                else 
                {
                    $this->set_property($key);
                }
            }
        }
        else
        {
            foreach ($this->data as $value)
        }

        return ERR::SUCCESS;
    }

    public function set_property($key)
    {
        throw new \InvalidArgumentException($key . " is not a valid key.");
    }

}