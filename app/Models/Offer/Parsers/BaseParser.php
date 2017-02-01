<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class BaseParser
{
    protected $offer;

    // Offer properties on which to run parser methods. Should be defined by each parser
    protected $properties = [];

    // Delimiter to be used for parsing
    protected $delimiter = ' ';

    // Prefix to be used by each parser
    protected $localPrefix = '';

    // Suffix to be used by each parser
    protected $localSuffix = '';

    public function __construct(Offer\Entity $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Parses the offer entity based on values present in the $properties array
     * in each Parser class and calls the associated functions to pass each attribute
     *
     * @return string        Result of parsing the offer entity
     */
    public function parse()
    {
        $description = [$this->localPrefix];

        foreach ($this->properties as $prop)
        {
            $methodName = 'get' . studly_case($prop) . 'Description';

            $description[] = $this->$methodName();
        }

        // Removes empty strings from array if any
        $description =  array_filter($description);

        if (empty($description) === false)
        {
            $description = implode($this->delimiter, $description);

            $description .= $this->localSuffix;

            // Removes extra spaces from string if any
            $description = preg_replace('/\s+/', ' ', $description);

            return $description;
        }

        $description = '';

        return $description;
    }
}
