<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class BaseParser
{
    // Offer properties on which to run parser methods. Should be defined by each parser
    protected static $properties = [];

    // Delimiter to be used for parsing
    protected static $delimiter = ' ';

    // Prefix to be used by each parser
    protected static $localPrefix = '';

    // Suffix to be used by each parser
    protected static $localSuffix = '';

    /**
     * Parses the offer entity based on values present in the $properties array
     * in each Parser class and calls the associated functions to pass each attribute
     *
     * @param  $offer Offer entity to parse
     *
     * @return string        Result of parsing the offer entity
     */
    public static function parse(Offer\Entity $offer)
    {
        $description = [static::$localPrefix];

        foreach (static::$properties as $prop)
        {
            $methodName = 'static::' . 'get' . studly_case($prop) . 'Description';

            $description[] = call_user_func($methodName, $offer);
        }

        // Removes empty strings from array if any
        $description =  array_filter($description);

        $description = implode(static::$delimiter, $description);

        $description .= static::$localSuffix;

        // Removes extra spaces from string if any
        $description = preg_replace('/\s+/', ' ', $description);

        return $description;
    }
}
