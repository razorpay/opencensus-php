<?php

namespace Models\Base;

class Entity extends \Razorpay\Spine\Entity
{
    public function build(array $input = array())
    {
        $this->input = $input;

        $this->modify($input);

        $validator = $this->validateInput('create', $input)->messages();

        $this->validator = $validator;

        if ($validator->fails())
        {
            return $validator->messages();
        }

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return array();
    }

    public function edit(array $input = array(), $operation = 'edit')
    {
        $validator = $this->validateInput($operation, $input)->messages();

        if ($validator->fails())
        {
            return $validator->messages();
        }

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return array();
    }

    protected function asDateTime($value)
    {
        //
        // If this value is an integer, we will assume
        // it is a UNIX timestamp's value and return as it is.
        // Otherwise we will call the parent function to handle it.
        //
        if (is_numeric($value))
        {
            return $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return snake_case($this->getEntityName()) . '_id';
    }

    /**
     * Since namespacing is ?/?/Entity
     * This returns the second last segment of the namespace
     * which is also the entity type is.
     *
     * eg: for Models/Transaction/Entity class, it returns Transaction
     *
     * @return string
     */
    public function getEntityName()
    {
        $class = get_class($this);

        $segments = explode('\\',$class);

        if (end($segments) === 'Entity')
        {
            return prev($segments);
        }
        else
        {
            return class_basename($this);
        }
    }
}