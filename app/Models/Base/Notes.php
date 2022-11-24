<?php

namespace RZP\Models\Base;

use ArrayObject;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * This class is the encapsulation over Notes attributes set in a payment by a merchant.
 * It is Json Serializable.
 */
class Notes extends ArrayObject implements  Arrayable,  Jsonable, JsonSerializable
{

    public function __construct($data = array())
    {
        if (is_null($data))
        {
            $data = [];
        }

        parent::__construct($data, ArrayObject::STD_PROP_LIST|ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Method of Jsonable interface to return json strings
     *
     * @return string
     */
    public function toJson($options = 0)
	{
        return json_encode($this->jsonSerialize(), JSON_FORCE_OBJECT);
    }

    /**
     * Method of JsonSerializable interface - Choose fields which needs to be serialized.
     * For notes, It is an array.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

	/**
     * Get the instance as an array
     *
     * @return array
     */
    public function toArray()
    {
		return $this->getArrayCopy();
	}

    /**
     * Returns an array of keys used in the notes
     * @return array keys from the notes array
     */
    public function getKeys()
    {
        return array_keys($this->toArray());
    }
}
