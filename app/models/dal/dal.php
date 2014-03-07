<?php

namespace Models\DAL;

class DAL extends \Eloquent
{

	const THEDEFAULT = 0x0;
	const FIELDS = 0x1;
	const ALL_FIELDS = 0x2;
	const APPENDS = 0x3;

	/**
	 * Return object properties as array
	 *
	 * @return array
	 */
	public function toArrayEx($flag = 0x0)
	{
		$array = array();

		if (($flag & static::FIELDS) or
			($flag & static::THEDEFAULT))
		{
			$array = $this->getArrayableFields();
		}
		else if ($flag & static::ALL_FIELDS)
		{
			$array = $this->data;
		}

		if (($flag & static::APPENDS) or
			($flag & static::THEDEFAULT))
		{
			$array = array_merge($array, $this->getAppends());
		}

		return $array;
	}

	/**
	 * 
	 */
	public function getAppends()
	{
		$appends = array();

		//
		// Here we will grab all of the appended, calculated fields to this object
		// as these fields are not really in the fields array, but are run
		// when we need to array or JSON the object for convenience to the coder.
		// 

		foreach ($this->appends as $key)
		{
			$appends[$key] = $this->mutateAttribute($key, null);
		}

		return $appends;
	}

	public function getVisible()
	{
		return $this->visible;
	}

	public function getHidden()
	{
		return $this->hidden;
	}

	public function getGuarded()
	{
		return $this->guarded;
	}

}
