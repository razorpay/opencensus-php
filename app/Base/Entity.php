<?php

namespace App\Base;
use Illuminate\Database\Eloquent;
use Trace;

class Entity extends \Razorpay\Spine\Entity
{
    public function build(array $input = array())
    {
        $this->input = $input;

        $this->modify($input);

        $validator = $this->validateInput('create', $input);

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

    /**
     * Does a soft-fail, which is where we want
     * to throw a 404 error instead of blanket failing
     * @param  string $id
     * @param  array  $columns
     * @return Entity
     */
    public static function findOrSoftFail($id, $columns = ['*'])
    {
        $result = self::find($id, $columns);

        if (!is_null($result))
        {
            return $result;
        }

        throw (new Eloquent\ModelNotFoundException);
    }

    public function edit(array $input = array(), $operation = 'edit')
    {
        $validator = $this->validateInput($operation, $input);

        if ($validator->fails())
        {
            return $validator->messages();
        }

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return array();
    }

    protected function getDateFormat()
    {
        return 'U';
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
