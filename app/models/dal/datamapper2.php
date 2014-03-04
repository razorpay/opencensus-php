<?php

namespace DataMapper;

class DataMapper2
{

	protected static $attributes = array();

	protected static $cacheRules = null;

	protected static $primaryKey = 'id';

	protected static $primaryAutoGenerate = false;

	protected static $timestamps = false;

	protected $query = null;

	public function validate($name, $data)
	{
		self::checkCacheRules();

		$keys = array_keys($data);

		$this->checkValidKeys($name, $keys);

		$this->checkRequiredKeys($name, $keys);

		$this->checkDeniedKeys($name, $keys);
	}

	protected static function checkCacheRules()
	{
		if (self::$cacheRules == null)
		{
			self::cacheRules();
		}
	}

	protected static function cacheRules()
	{
		self::$cacheRules = array();

		foreach (self::$attributes as $attr => $rules)
		{
			$rule_array = self::explodeRules($rules);

			self::indexRule($attr, $rule_array);
		}
	}

	protected static function explodeRules($rules)
	{
		foreach ($rules as &$rule)
		{
			if (! is_string($rule))
			{
				throw new \InvalidArgumentException('Rule is not string');
			}

			$rule = explode('|', $rule);
		}

		return $rules;
	}

	protected static function indexRule($attr, $rule_array)
	{
		$keys_to_add = array_diff_key($rule_array, self::$cachedRules);

		foreach ($keys_to_add as $key)
			self::$cachedRules[$key] = array();

		foreach ($rule_array as $rule)
		{
			array_push(self::$cachedRules[$rule], $attr);
		}
	}

	public function insert($do)
	{
		$data = $do->toArray();

		$this->validate(__FUNCTION__, $data);

		if ($timestamps)
		{
			$data['created_at'] = time();
			$data['updated_at'] = time();
		}

		if (self::$primaryAutoGenrate)
		{

			$primary = DB::table(self::table)
    	            	 ->insertGetId($this->row);

            $do->setPrimary($primary);
        }
        else
        {
        	DB::table(self::table)
        	  ->insert($this->row);
        }
	}

	public function update($do, $type = '')
	{
		$data = $do->toArray();

		//
		// Make sure all attrs passed in are allowed
		// 
		$this->validate('db', $data);

		// Get the keys separately
		$keys = array_keys($data);

		// Now we will get the data to be pushed to db
		// in these variables
		$required_keys = array();
		$required_data = array();

		if (array_key_exists(self::$cacheRules, $type.'_update_req'))
		{
			//
			// Make sure all required update attrs are present
			//

			$this->checkRequiredKeys($type.'_update_req', $keys)

			$required_keys = array_intersect($keys, self::$cacheRules[$type.'_update_req']);
		}

		if (array_key_exists(self::$cacheRules, $type.'_update'))
		{
			//
			// Get the attrs which are present in data that are allowed to be modified
			//
			
			array_merge_intersect(
				$required_keys, 
				$keys, 
				self::$cacheRules[$type.'_update']);
		}

		$required_data = array_key_values($data, $required_keys);

		if ($timestamps)
		{
			$requiredData['updated_at'] = time();
		}

		DB::table(self::table)
          ->where($primaryKey, '=', $data[$primaryKey])
          ->update($requiredData);
	}

	public function checkValidKeys($name, $keys)
	{
		if (! array_key_exists(self::$cacheRules, $name)
		{
			return;
		}

		$invalid_keys = array_diff($keys, self::$cacheRules[$name]);

		if (count($invalid_keys) > 0)
		{
			throw new \InvalidKeysException($invalid_keys);
		}
	}

	public function checkRequiredKeys($name, $keys)
	{
		if (! array_key_exists(self::$cacheRules, $name.'_req')
		{
			return;
		}

		$invalid_keys = array_diff(self::$cacheRules[$name.'_req'], $keys);

		if (count($invalid_keys) > 0)
		{
			throw new \InvalidKeysException($invalid_keys);
		}
	}

	public function checkDeniedKeys($name, $keys)
	{
		if (! array_key_exists(self::$cacheRules, $name)
		{
			return;
		}

		$invalid_keys = array_intersect($keys, self::$cacheRules[$name]);

		if (count($invalid_keys) > 0)
		{
			throw new \InvalidKeysException($invalid_keys);
		}
	}

	public static function getAttributes()
	{
		return self::$attributes;
	}

	public static function persist($do)
	{
		$dm = new static();

		if ($do->isBuilt())
		{
			$dm->insert($do);
		}
		else
		{
			$dm->update($do);
		}

		return $dm;
	}

	public static function find($value, $fail = false)
	{
		$row = DB::table(static::table)
		  		 ->where(static::$primaryKey, $value);

		return $row;
	}

	protected static function findBy(array $array, $fail = false)
	{
		$row = DB::table(static::table);

		foreach ($array as $key => $value)
		{
			$row->where($key, $value);
		}
		
	}

	public static function findWith(array $array)
	{

	}
}