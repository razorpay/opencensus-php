<?php

namespace App\Base;
use Illuminate\Database\Eloquent;
use Trace;

class Entity extends \Razorpay\Spine\Entity
{
    const ID_LENGTH = 14;

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

    public function getDateFormat()
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

    public static function verifyUniqueId($id)
    {
        $uniqueIdCheckRegex = '/^[0-9a-z]{'. static::ID_LENGTH .'}$/i';

        $res = preg_match($uniqueIdCheckRegex, $id);

        // preg_match() returns int 0 when the pattern does not match
        // and int 1 if a match is found. false (boolean) is returned
        // whenever any error happens.
        if (in_array($res, [0, false], true) === true)
        {
            return 0;
        }

        return $res;
    }
}
