<?php

class Utility
{
    private function __construct() {}

    public static function generate_token($len)
    {
        return bin2hex(openssl_random_pseudo_bytes($len/2));
    }

    public static function array_prefix(array $input, $prefix)
    {
        $prefixed_input = array_map(function($val) use($prefix) { return $prefix.$val; }, $input);

        return $prefixed_input;
    }

    public static function array_column_prefix(array $input, $prefix)
    {
        $keys = array();
        $len = strlen($prefix);
        foreach ($input[0] as $key => $value)
        {
            if (stripos($key, $prefix) === 0)
            {
                array_push($keys, $key);
            }
        }
        print_r($input[0][0]);
        print_r($input);die();

        return array_column($input, $keys);
    }

    public static function array_join_conjunction(array $input1, array $input2, $conjunction)
    {
        $output = array();

        $count = count($input1);

        if (count($input1) !== count($input2))
        {
            throw new \InvalidArgumentException("Number of elements in both arrays is not equal");
        }

        for ($i = 0; $i < $count; $i++)
        {
            array_push($output, $input1[$i].$conjunction.$input2[$i]);
        }

        return $output;
    }
}

if (! function_exists('is_assoc_array'))
{
	/**
	 * Checks if the array is assoc or sequential
	 * 
	 * It compares the keys (which for a sequential array are 
	 * always 0,1,2 etc) to the keys of the keys (which 
	 * will always be 0,1,2 etc).
	 *
	 * @param  array  $array
	 * @return bool
	 */
	function array_merge_intersect(array &$array1, $array2, $array3)
	{
		$intersect = array_intersect($array2, $array3);

   		$array1 = array_merge($array1, $intersect);

   		return $array1;
	}
}


if (! function_exists('array_key_values'))
{
	function array_key_values($array, $keys)
	{
		$return = array();

		foreach ($keys as $key)
		{
			$return[$key] = $array[$key];
		}

		return $key;
	}
}

if (! function_exists('validate'))
{
	function validate($rules, $data)
	{
		$invalid_keys = array_diff_key($data, $rules);

        if (count($invalid_keys) !== 0)
        {
            throw new \InvalidKeysException($invalid_keys);
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) 
        {
            throw new \InvalidArgumentException($validation->errors->all());
        }
	}
}

if (! function_exists('break_assoc_arrays'))
{
	function break_assoc_arrays($array, $keys1, $keys2)
	{
		$array1 = array();
		$array2 = array();

		foreach ($array as $key => $value)
		{
			if (in_array($key, $keys1))
			{
				$array1[$key] = $value;
			}
			
			if(in_array($key, $keys2))
			{
				$array2[$key] = $value;
			}
		}

		return array($array1, $array2);
	}
}

if (! function_exists('validate_keys'))
{
	function validate_keys($data, $rules)
	{
		$invalid_keys = array_diff_keys($data, $rules);

		if (count($invalid_keys) > 0)
		{
			throw new \InvalidKeysException($invalid_keys);
		}
	}
}