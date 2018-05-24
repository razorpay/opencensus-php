<?php

namespace RZP\Models\Address;

class Utility
{
    /**
     * Produces text representation for address parts passed
     *
     * Parts contains line1, line2, city, state, country & zipcode etc using which following text is produced:
     * <line1>
     * <line2>
     * <city>, <state>, <country> - <zipcode>
     *
     * @param  array       $parts
     * @param  string      $delimiter
     * @return string|null
     */
    public static function formatAddressAsText(array $parts = [], string $delimiter = PHP_EOL)
    {
        // Filters out null values
        $parts = array_filter($parts);

        $lines = array_only($parts, [Entity::LINE1, Entity::LINE2]);

        // Prepares last line containing city to zipcode
        $cityLine = implode(', ', array_only($parts, [Entity::CITY, Entity::STATE, Entity::COUNTRY]));
        if (isset($parts[Entity::ZIPCODE]) === true)
        {
            $cityLine .= ' - ' . $parts[Entity::ZIPCODE];
        }

        $lines[] = $cityLine;

        if (empty($lines) === true)
        {
            return null;
        }

        return implode($delimiter, $lines);
    }
}
