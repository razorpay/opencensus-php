<?php

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
            throw new \Exceptions\InvalidKeysException($invalid_keys);
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) 
        {
            throw new \Exceptions\InvalidArgumentException($validation->messages()->all());
        }
	}
}

if (! function_exists('break_assoc_array'))
{
	function break_assoc_array($array, $keys1, $keys2)
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

if (! function_exists('GetTextBetweenTags'))
{
	function getTextBetweenTags($string, $start, $end)
	{
	    $string = " ".$string;

		$ini = strpos($string,$start);
		
		if ($ini === false) return null;

		$ini += strlen($start);
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}
}

if (! function_exists('implode_assoc_array'))
{
	function implode_assoc_array(array $array)
	{
		$str = '';
	    foreach($array as $key => $value)
	    {
	    	$str .= $key . '=>' . $value . ', ';
	    }

	    return $str;
	}
}