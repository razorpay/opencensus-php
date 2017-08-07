<?php

namespace RZP\Models\Settings;

use Setting;

class Types
{
    const OPENWALLET = 'openwallet';

    protected static $defined = [
        self::OPENWALLET => [
            'type'   => ["Closed", "Semi-closed"],
            'closed' => [
                'max_limit' => 'Closed Wallet - Max Balance'
            ]
        ]
    ];

    public static function getWithDescriptions(string $key = null)
    {
        $data = static::$defined;

        if ($key !== null)
        {
            $data = static::$defined[$key] ?? [];
        }

        return self::dotFlatten($data);
    }

    protected static function dotFlatten($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value)
        {
            if ((is_array($value) === true) and
                (empty($value) === false) and
                (is_associative_array($value)=== true))
            {
                $results = array_merge($results, static::dotFlatten($value, $prepend . $key . '.'));
            }
            else
            {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }
}
